<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Command;

use RevisionTen\Calendar\Handler\EventCreateRuleHandler;
use RevisionTen\Calendar\Entity\Event;
use RevisionTen\CQRS\Command\Command;
use RevisionTen\CQRS\Interfaces\CommandInterface;

final class EventCreateRuleCommand extends Command implements CommandInterface
{
    public static function getHandlerClass(): string
    {
        return EventCreateRuleHandler::class;
    }

    public static function getAggregateClass(): string
    {
        return Event::class;
    }
}
