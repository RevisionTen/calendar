<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Services;

use Doctrine\ORM\EntityManagerInterface;
use RevisionTen\Calendar\Entity\Event;
use RevisionTen\Calendar\Entity\EventRead;
use RevisionTen\CMS\Entity\Website;
use RevisionTen\CQRS\Services\AggregateFactory;

class CalendarService
{
    protected EntityManagerInterface $entityManager;

    protected AggregateFactory $aggregateFactory;

    public function __construct(EntityManagerInterface $entityManager, AggregateFactory $aggregateFactory)
    {
        $this->entityManager = $entityManager;
        $this->aggregateFactory = $aggregateFactory;
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
        $eventRead->setCreated($aggregate->created);
        $eventRead->setModified($aggregate->modified);

        $eventRead->title = $aggregate->title;
        $eventRead->deleted = $aggregate->deleted;
        $eventRead->salesStatus = $aggregate->salesStatus;

        $this->entityManager->persist($eventRead);
        $this->entityManager->flush();
    }
}
