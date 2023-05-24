<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\EventSubscriber;

use RevisionTen\Calendar\Event\EventCreateEvent;
use RevisionTen\Calendar\Event\EventDeleteEvent;
use RevisionTen\Calendar\Event\EventDeviationCreateEvent;
use RevisionTen\Calendar\Event\EventDeviationDeleteEvent;
use RevisionTen\Calendar\Event\EventDeviationEditEvent;
use RevisionTen\Calendar\Event\EventEditEvent;
use RevisionTen\Calendar\Event\EventPublishEvent;
use RevisionTen\Calendar\Event\EventRuleCreateEvent;
use RevisionTen\Calendar\Event\EventRuleDeleteEvent;
use RevisionTen\Calendar\Event\EventRuleDuplicateEvent;
use RevisionTen\Calendar\Event\EventRuleEditEvent;
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
            EventCreateEvent::class => 'updateReadModel',
            EventEditEvent::class => 'updateReadModel',
            EventDeleteEvent::class => 'deleteEvent',
            EventPublishEvent::class => 'publishEvent',
            EventRuleCreateEvent::class => 'updateReadModel',
            EventRuleEditEvent::class => 'updateReadModel',
            EventRuleDeleteEvent::class => 'updateReadModel',
            EventRuleDuplicateEvent::class => 'updateReadModel',
            EventDeviationCreateEvent::class => 'updateReadModel',
            EventDeviationEditEvent::class => 'updateReadModel',
            EventDeviationDeleteEvent::class => 'updateReadModel',
        ];
    }

    public function publishEvent(EventInterface $event): void
    {
        $this->calendarService->updateStreamReadModel($event->getAggregateUuid());
        $this->calendarService->updateReadModel($event->getAggregateUuid());
        $this->calendarService->indexEvent($event->getAggregateUuid());
    }

    public function deleteEvent(EventInterface $event): void
    {
        $this->calendarService->updateStreamReadModel($event->getAggregateUuid());
        $this->calendarService->updateReadModel($event->getAggregateUuid());
        $this->calendarService->indexEvent($event->getAggregateUuid());
    }

    public function updateReadModel(EventInterface $event): void
    {
        $this->calendarService->updateStreamReadModel($event->getAggregateUuid());
    }
}
