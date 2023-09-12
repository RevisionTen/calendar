<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Handler;

use DateTime;
use ReflectionObject;
use ReflectionProperty;
use RevisionTen\Calendar\Event\EventCreateEvent;
use RevisionTen\Calendar\Entity\Event;
use RevisionTen\CQRS\Exception\CommandValidationException;
use RevisionTen\CQRS\Interfaces\AggregateInterface;
use RevisionTen\CQRS\Interfaces\CommandInterface;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\CQRS\Interfaces\HandlerInterface;
use function array_key_exists;

final class EventCreateHandler implements HandlerInterface
{
    /**
     * {@inheritdoc}
     *
     * @var Event $aggregate
     */
    public function execute(EventInterface $event, AggregateInterface $aggregate): AggregateInterface
    {
        $payload = $event->getPayload();

        // Change Aggregate state.
        // Get each public property from the aggregate and update it If a new value exists in the payload.
        $reflect = new ReflectionObject($aggregate);
        foreach ($reflect->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $propertyName = $property->getName();

            if ('startDate' === $propertyName) {
                if (!empty($payload['startDate'])) {
                    $startDate = $payload['startDate'];
                    $aggregate->startDate = new DateTime();
                    $aggregate->startDate->setTimestamp($startDate);
                } else {
                    $aggregate->startDate = null;
                }
                continue;
            }
            if ('endDate' === $propertyName) {
                if (!empty($payload['endDate'])) {
                    $endDate = $payload['endDate'];
                    $aggregate->endDate = new DateTime();
                    $aggregate->endDate->setTimestamp($endDate);
                } else {
                    $aggregate->endDate = null;
                }
                continue;
            }

            if (array_key_exists($propertyName, $payload)) {
                $aggregate->{$propertyName} = $payload[$propertyName];
            }
        }

        return $aggregate;
    }

    public function createEvent(CommandInterface $command): EventInterface
    {
        return new EventCreateEvent(
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

        if (0 !== $aggregate->getVersion()) {
            throw new CommandValidationException(
                'Aggregate already exists',
                CODE_CONFLICT,
                NULL,
                $command
            );
        }

        if (empty($payload['title'])) {
            throw new CommandValidationException(
                'You must enter a title',
                CODE_BAD_REQUEST,
                NULL,
                $command
            );
        }

        return true;
    }
}
