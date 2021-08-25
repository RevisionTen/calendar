<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Command;

use RevisionTen\Calendar\Handler\EventDeviationCreateHandler;
use RevisionTen\Calendar\Entity\Event;
use RevisionTen\CQRS\Command\Command;
use RevisionTen\CQRS\Interfaces\CommandInterface;

final class EventDeviationCreateCommand extends Command implements CommandInterface
{
    public static function getHandlerClass(): string
    {
        return EventDeviationCreateHandler::class;
    }

    public static function getAggregateClass(): string
    {
        return Event::class;
    }
}
