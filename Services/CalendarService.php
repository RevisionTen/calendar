<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Services;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use RevisionTen\Calendar\Entity\Event;
use RevisionTen\Calendar\Entity\EventRead;
use RevisionTen\Calendar\Serializer\EventSerializer;
use RevisionTen\Calendar\Serializer\SolrSerializerInterface;
use RevisionTen\CMS\Entity\Website;
use RevisionTen\CMS\Services\IndexService;
use RevisionTen\CMS\Services\PageService;
use RevisionTen\CQRS\Services\AggregateFactory;
use RevisionTen\CQRS\Services\SnapshotStore;
use Solarium\Core\Client\Adapter\Curl;
use Solarium\Core\Client\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\EventDispatcher\EventDispatcher;

class CalendarService extends IndexService
{
    protected AggregateFactory $aggregateFactory;

    protected SnapshotStore $snapshotStore;

    protected array $calendarConfig;

    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container, LoggerInterface $logger, EntityManagerInterface $entityManager, PageService $pageService, array $config, AggregateFactory $aggregateFactory, SnapshotStore $snapshotStore, array $calendarConfig)
    {
        parent::__construct($container, $logger, $entityManager, $pageService, $config);

        $this->aggregateFactory = $aggregateFactory;
        $this->snapshotStore = $snapshotStore;
        $this->calendarConfig = $calendarConfig;
        $this->container = $container;
    }

    public function indexEvent(string $aggregateUuid): void
    {
        if (null === $this->solrConfig) {
            // Do nothing if solr is not configured.
            return;
        }

        /**
         * @var Event $event
         */
        $event = $this->aggregateFactory->build($aggregateUuid, Event::class);

        /**
         * @var EventRead $eventRead
         */
        $eventRead = $this->entityManager->getRepository(EventRead::class)->findOneBy([
            'uuid' => $aggregateUuid,
        ]);

        $adapter = new Curl();
        $eventDispatcher = new EventDispatcher();
        $client = new Client($adapter, $eventDispatcher, $this->solrConfig);

        $update = $client->createUpdate();

        $serializer = $this->calendarConfig['event_solr_serializer'] ?? false;
        if ($serializer && class_exists($serializer) && in_array(SolrSerializerInterface::class, class_implements($serializer), true)) {
            try {
                // Get the serializer as a service.
                $serializer = $this->container->get($serializer);
            } catch (ServiceNotFoundException $e) {
                /**
                 * @var SolrSerializerInterface $serializer
                 */
                $serializer = new $serializer();
            }
        } else {
            $serializer = new EventSerializer();
        }

        $documents = $serializer->serialize($update, $event, $eventRead);

        $update->addDocuments($documents);
        $update->addCommit();
        $update->addOptimize();

        $client->update($update);
    }

    public function updateReadModel(string $aggregateUuid): void
    {
        /**
         * @var Event $aggregate
         */
        $aggregate = $this->aggregateFactory->build($aggregateUuid, Event::class);

        /**
         * @var Website|null $website
         */
        $website = $aggregate->website ? $this->entityManager->getRepository(Website::class)->find($aggregate->website) : null;

        $eventRead = $this->entityManager->getRepository(EventRead::class)->findOneBy(['uuid' => $aggregateUuid]) ?? new EventRead();
        $eventRead->setVersion($aggregate->getStreamVersion());
        $eventRead->setUuid($aggregateUuid);
        $fileData = json_decode(json_encode($aggregate), true);
        $eventRead->setPayload($fileData);
        $eventRead->setWebsite($website);
        $eventRead->setLanguage($aggregate->language);

        $eventRead->title = $aggregate->title;
        $eventRead->deleted = $aggregate->deleted;
        $eventRead->salesStatus = $aggregate->salesStatus;
        $eventRead->created = $aggregate->created;
        $eventRead->modified = $aggregate->modified;

        $this->entityManager->persist($eventRead);
        $this->entityManager->flush();

        // Save snapshot.
        if ($aggregate->getSnapshotVersion() <= ($aggregate->getVersion() - 10)) {
            $this->snapshotStore->save($aggregate);
        }
    }
}
