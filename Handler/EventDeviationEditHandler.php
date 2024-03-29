<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Handler;

use DateTime;
use RevisionTen\Calendar\Event\EventDeviationEditEvent;
use RevisionTen\Calendar\Entity\Event;
use RevisionTen\CQRS\Exception\CommandValidationException;
use RevisionTen\CQRS\Interfaces\AggregateInterface;
use RevisionTen\CQRS\Interfaces\CommandInterface;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\CQRS\Interfaces\HandlerInterface;

final class EventDeviationEditHandler implements HandlerInterface
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

        $deviation->participants = !empty($payload['participants']) ? (int) $payload['participants'] : null;
        $deviation->salesStatus = $payload['salesStatus'] ?? null;
        $deviation->venue = $payload['venue'] ?? null;
        $deviation->extra = $payload['extra'] ?? null;
        $deviation->keywords = $payload['keywords'] ?? null;
        $deviation->genres = $payload['genres'] ?? null;

        if (!empty($payload['startDate'])) {
            $startDate = new DateTime();
            $startDate->setTimestamp($payload['startDate']);
            $deviation->startDate = $startDate;
        }
        if (!empty($payload['endDate'])) {
            $endDate = new DateTime();
            $endDate->setTimestamp($payload['endDate']);
            $deviation->endDate = $endDate;
        }

        $aggregate->updateDeviation($deviation);

        return $aggregate;
    }

    public function createEvent(CommandInterface $command): EventInterface
    {
        return new EventDeviationEditEvent(
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
