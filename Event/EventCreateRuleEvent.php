<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Event;

use RevisionTen\Calendar\Entity\Event;
use RevisionTen\Calendar\Handler\EventCreateRuleHandler;
use RevisionTen\CQRS\Event\AggregateEvent;
use RevisionTen\CQRS\Interfaces\EventInterface;

final class EventCreateRuleEvent extends AggregateEvent implements EventInterface
{
    public static function getAggregateClass(): string
    {
        return Event::class;
    }

    public static function getHandlerClass(): string
    {
        return EventCreateRuleHandler::class;
    }

    public function getMessage(): string
    {
        return 'Rule created';
    }
}
