<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Handler;

use DateTime;
use RevisionTen\Calendar\Entity\Deviation;
use RevisionTen\Calendar\Event\EventDeviationCreateEvent;
use RevisionTen\Calendar\Entity\Event;
use RevisionTen\CQRS\Exception\CommandValidationException;
use RevisionTen\CQRS\Interfaces\AggregateInterface;
use RevisionTen\CQRS\Interfaces\CommandInterface;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\CQRS\Interfaces\HandlerInterface;

final class EventDeviationCreateHandler implements HandlerInterface
{
    /**
     * {@inheritdoc}
     *
     * @var Event $aggregate
     */
    public function execute(EventInterface $event, AggregateInterface $aggregate): AggregateInterface
    {
        $payload = $event->getPayload();

        $deviationUuid = $event->getCommandUuid();

        $deviationStartDate = new DateTime();
        $deviationStartDate->setTimestamp($payload['deviationStartDate']);

        $deviationEndDate = new DateTime();
        $deviationEndDate->setTimestamp($payload['deviationEndDate']);

        $deviation = new Deviation($deviationUuid, $deviationStartDate, $deviationEndDate);

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

        $aggregate->addDeviation($deviation);

        return $aggregate;
    }

    public function createEvent(CommandInterface $command): EventInterface
    {
        return new EventDeviationCreateEvent(
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

        if (empty($payload['deviationStartDate'])) {
            throw new CommandValidationException(
                'You must provide a deviationStartDate',
                CODE_BAD_REQUEST,
                NULL,
                $command
            );
        }

        if (empty($payload['deviationEndDate'])) {
            throw new CommandValidationException(
                'You must provide a deviationEndDate',
                CODE_BAD_REQUEST,
                NULL,
                $command
            );
        }

        return true;
    }
}
