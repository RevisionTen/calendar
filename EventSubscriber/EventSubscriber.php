<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\EventSubscriber;

use RevisionTen\Calendar\Event\EventCreateEvent;
use RevisionTen\Calendar\Event\EventDeleteEvent;
use RevisionTen\Calendar\Event\EventEditEvent;
use RevisionTen\Calendar\Services\CalendarService;
use RevisionTen\CQRS\Interfaces\EventInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EventSubscriber implements EventSubscriberInterface
{
    private CalendarService $calendarService;

    public function __construct(CalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EventCreateEvent::class => 'create',
            EventEditEvent::class => 'edit',
            EventDeleteEvent::class => 'delete',
        ];
    }

    public function updateReadModel(EventInterface $event): void
    {
        $this->calendarService->updateReadModel($event->getAggregateUuid());
        $this->calendarService->indexEvent($event->getAggregateUuid());
    }

    public function create(EventCreateEvent $event): void
    {
        $this->updateReadModel($event);
    }

    public function edit(EventEditEvent $event): void
    {
        $this->updateReadModel($event);
    }

    public function delete(EventDeleteEvent $event): void
    {
        $this->updateReadModel($event);
    }
}
