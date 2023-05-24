<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Event;

use RevisionTen\Calendar\Entity\Event;
use RevisionTen\Calendar\Handler\EventRuleDuplicateHandler;
use RevisionTen\CQRS\Event\AggregateEvent;
use RevisionTen\CQRS\Interfaces\EventInterface;

final class EventRuleDuplicateEvent extends AggregateEvent implements EventInterface
{
    public static function getAggregateClass(): string
    {
        return Event::class;
    }

    public static function getHandlerClass(): string
    {
        return EventRuleDuplicateHandler::class;
    }

    public function getMessage(): string
    {
        return 'Rule duplicated';
    }
}
