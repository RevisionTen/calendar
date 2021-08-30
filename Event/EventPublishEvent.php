<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Event;

use RevisionTen\Calendar\Entity\Event;
use RevisionTen\Calendar\Handler\EventPublishHandler;
use RevisionTen\CQRS\Event\AggregateEvent;
use RevisionTen\CQRS\Interfaces\EventInterface;

final class EventPublishEvent extends AggregateEvent implements EventInterface
{
    public static function getAggregateClass(): string
    {
        return Event::class;
    }

    public static function getHandlerClass(): string
    {
        return EventPublishHandler::class;
    }

    public function getMessage(): string
    {
        return 'Event published';
    }
}
