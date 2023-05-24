<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Command;

use RevisionTen\Calendar\Entity\Event;
use RevisionTen\Calendar\Handler\EventRuleDuplicateHandler;
use RevisionTen\CQRS\Command\Command;
use RevisionTen\CQRS\Interfaces\CommandInterface;

final class EventRuleDuplicateCommand extends Command implements CommandInterface
{
    public static function getHandlerClass(): string
    {
        return EventRuleDuplicateHandler::class;
    }

    public static function getAggregateClass(): string
    {
        return Event::class;
    }
}
