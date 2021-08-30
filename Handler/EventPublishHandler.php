<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Handler;

use RevisionTen\Calendar\Event\EventPublishEvent;
use RevisionTen\Calendar\Entity\Event;
use RevisionTen\CQRS\Interfaces\AggregateInterface;
use RevisionTen\CQRS\Interfaces\CommandInterface;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\CQRS\Interfaces\HandlerInterface;

final class EventPublishHandler implements HandlerInterface
{
    /**
     * {@inheritdoc}
     *
     * @var Event $aggregate
     */
    public function execute(EventInterface $event, AggregateInterface $aggregate): AggregateInterface
    {
        $aggregate->publishedVersion = $event->getVersion();

        return $aggregate;
    }

    public function createEvent(CommandInterface $command): EventInterface
    {
        return new EventPublishEvent(
            $command->getAggregateUuid(),
            $command->getUuid(),
            $command->getOnVersion() + 1,
            $command->getUser(),
            $command->getPayload()
        );
    }

    public function validateCommand(CommandInterface $command, AggregateInterface $aggregate): bool
    {
        return true;
    }
}
