<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Handler;

use RevisionTen\Calendar\Event\EventDeviationDeleteEvent;
use RevisionTen\Calendar\Entity\Event;
use RevisionTen\CQRS\Exception\CommandValidationException;
use RevisionTen\CQRS\Interfaces\AggregateInterface;
use RevisionTen\CQRS\Interfaces\CommandInterface;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\CQRS\Interfaces\HandlerInterface;

final class EventDeviationDeleteHandler implements HandlerInterface
{
    /**
     * {@inheritdoc}
     *
     * @var Event $aggregate
     */
    public function execute(EventInterface $event, AggregateInterface $aggregate): AggregateInterface
    {
        $payload = $event->getPayload();

        $deviationUuid = $payload['uuid'];

        $deviation = $aggregate->getDeviation($deviationUuid);

        $aggregate->deleteDeviation($deviation);

        return $aggregate;
    }

    public function createEvent(CommandInterface $command): EventInterface
    {
        return new EventDeviationDeleteEvent(
            $command->getAggregateUuid(),
            $command->getUuid(),
            $command->getOnVersion() + 1,
            $command->getUser(),
            $command->getPayload()
        );
    }

    public function validateCommand(CommandInterface $command, AggregateInterface $aggregate): bool
    {
        $payload = $command->getPayload();

        if (empty($payload['uuid'])) {
            throw new CommandValidationException(
                'You must provide a deviation uuid',
                CODE_BAD_REQUEST,
                NULL,
                $command
            );
        }

        return true;
    }
}
