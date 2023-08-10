<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Services;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use RevisionTen\Calendar\Entity\Event;
use RevisionTen\Calendar\Entity\EventRead;
use RevisionTen\Calendar\Entity\EventStreamRead;
use RevisionTen\Calendar\Serializer\EventSerializer;
use RevisionTen\Calendar\Serializer\SolrSerializerInterface;
use RevisionTen\CMS\Entity\Alias;
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
use Symfony\Component\String\Slugger\AsciiSlugger;

class CalendarService extends IndexService
{
    protected AggregateFactory $aggregateFactory;

    protected SnapshotStore $snapshotStore;

    protected array $calendarConfig;

    protected ContainerInterface $container;

    private AsciiSlugger $slugger;

    public function __construct(ContainerInterface $container, LoggerInterface $logger, EntityManagerInterface $entityManager, PageService $pageService, array $config, AggregateFactory $aggregateFactory, SnapshotStore $snapshotStore, array $calendarConfig)
    {
        parent::__construct($container, $logger, $entityManager, $pageService, $config);

        $this->aggregateFactory = $aggregateFactory;
        $this->snapshotStore = $snapshotStore;
        $this->calendarConfig = $calendarConfig;
        $this->container = $container;
        $this->slugger = new AsciiSlugger($config['slugger_locale'] ?? 'de');
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
         * @var EventRead|null $eventRead
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

    public function updateStreamReadModel(string $aggregateUuid): void
    {
        /**
         * @var Event $aggregate
         */
        $aggregate = $this->aggregateFactory->build($aggregateUuid, Event::class);

        /**
         * @var Website|null $website
         */
        $website = $aggregate->website ? $this->entityManager->getRepository(Website::class)->find($aggregate->website) : null;

        $eventStreamRead = $this->entityManager->getRepository(EventStreamRead::class)->findOneBy(['uuid' => $aggregateUuid]) ?? new EventStreamRead();
        $eventStreamRead->setVersion($aggregate->getStreamVersion());
        $eventStreamRead->setUuid($aggregateUuid);

        $fileData = json_decode(json_encode($aggregate), true);
        $eventStreamRead->setPayload($fileData);

        $eventStreamRead->setDates($aggregate);
        $eventStreamRead->setWebsite($website);
        $eventStreamRead->setLanguage($aggregate->language);

        $eventStreamRead->publishedVersion = $aggregate->publishedVersion;
        $eventStreamRead->title = $aggregate->title;
        $eventStreamRead->deleted = $aggregate->deleted;
        $eventStreamRead->salesStatus = $aggregate->salesStatus;
        $eventStreamRead->created = $aggregate->created;
        $eventStreamRead->modified = $aggregate->modified;

        $this->entityManager->persist($eventStreamRead);
        $this->entityManager->flush();
        $this->entityManager->clear();

        // Save snapshot.
        if ($aggregate->getSnapshotVersion() <= ($aggregate->getVersion() - 10)) {
            $this->snapshotStore->save($aggregate);
        }
    }

    public function updateReadModel(string $aggregateUuid): void
    {
        /**
         * @var Event $aggregate
         */
        $aggregate = $this->aggregateFactory->build($aggregateUuid, Event::class);

        if ($aggregate->deleted) {
            // Delete EventRead and Alias.
            $oldEventRead = $this->entityManager->getRepository(EventRead::class)->findOneBy(['uuid' => $aggregateUuid]);
            if ($oldEventRead) {
                $oldAlias = $oldEventRead->getAlias();
                if (null !== $oldAlias) {
                    $oldEventRead->setAlias(null);
                    $this->entityManager->remove($oldAlias);
                }
                $this->entityManager->remove($oldEventRead);
                $this->entityManager->flush();
                $this->entityManager->clear();

                return;
            }
        }

        /**
         * @var Website|null $website
         */
        $website = $aggregate->website ? $this->entityManager->getRepository(Website::class)->find($aggregate->website) : null;

        $eventRead = $this->entityManager->getRepository(EventRead::class)->findOneBy(['uuid' => $aggregateUuid]) ?? new EventRead();
        $eventRead->setVersion($aggregate->getStreamVersion());
        $eventRead->setUuid($aggregateUuid);
        $eventRead->setDates($aggregate);
        $eventRead->setWebsite($website);
        $eventRead->setLanguage($aggregate->language);

        $fileData = json_decode(json_encode($aggregate), true);
        $eventRead->setPayload($fileData);

        // Update alias.
        $hash = $this->slugger->slug(hash('crc32', $aggregate->uuid))->lower()->toString();
        if (!empty($aggregate->genres) && is_array($aggregate->genres)) {
            $slug = '/' . $this->slugger->slug(implode('-', $aggregate->genres))->lower()->toString() . '/' . $this->slugger->slug($aggregate->title)->lower()->toString() . '-' . $hash;
        } else {
            $slug = '/' . $this->slugger->slug($aggregate->title)->lower()->toString() . '-' . $hash;
        }
        $alias = $eventRead->getAlias();
        if (null === $alias) {
            $alias = new Alias();
        }
        $controller = $this->calendarConfig['event_frontend_controller'] ?? null;
        $alias->setController($controller);
        $alias->setWebsite($website);
        $alias->setLanguage($aggregate->language);
        $alias->setPath($slug);
        $eventRead->setAlias($alias);

        $this->entityManager->persist($eventRead);
        $this->entityManager->flush();
        $this->entityManager->clear();
    }
}
