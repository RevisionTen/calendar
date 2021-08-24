<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Event;

use RevisionTen\Calendar\Entity\Event;
use RevisionTen\Calendar\Handler\EventRuleEditHandler;
use RevisionTen\CQRS\Event\AggregateEvent;
use RevisionTen\CQRS\Interfaces\EventInterface;

final class EventRuleEditEvent extends AggregateEvent implements EventInterface
{
    public static function getAggregateClass(): string
    {
        return Event::class;
    }

    public static function getHandlerClass(): string
    {
        return EventRuleEditHandler::class;
    }

    public function getMessage(): string
    {
        return 'Rule edited';
    }
}
