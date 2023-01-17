<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Handler;

use DateTime;
use RevisionTen\Calendar\Event\EventRuleEditEvent;
use RevisionTen\Calendar\Entity\Event;
use RevisionTen\CQRS\Exception\CommandValidationException;
use RevisionTen\CQRS\Interfaces\AggregateInterface;
use RevisionTen\CQRS\Interfaces\CommandInterface;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\CQRS\Interfaces\HandlerInterface;

final class EventRuleEditHandler implements HandlerInterface
{
    /**
     * {@inheritdoc}
     *
     * @var Event $aggregate
     */
    public function execute(EventInterface $event, AggregateInterface $aggregate): AggregateInterface
    {
        $payload = $event->getPayload();

        $ruleUuid = $payload['uuid'];

        $rule = $aggregate->getRule($ruleUuid);
        $rule->ruleTitle = $payload['ruleTitle'] ?? null;
        $rule->title = $payload['title'] ?? null;
        $rule->participants = !empty($payload['participants']) ? (int) $payload['participants'] : null;
        $rule->description = $payload['description'] ?? null;
        $rule->bookingInfo = $payload['bookingInfo'] ?? null;
        $rule->venue = $payload['venue'] ?? null;
        $rule->artist = $payload['artist'] ?? null;
        $rule->organizer = $payload['organizer'] ?? null;
        $rule->image = $payload['image'] ?? null;
        $rule->extra = $payload['extra'] ?? null;

        $repeatEndDate = $payload['repeatEndDate'] ?? null;
        if (!empty($repeatEndDate)) {
            $repeatEnd = new DateTime();
            $repeatEnd->setTimestamp($repeatEndDate);
            $rule->repeatEndDate = $repeatEnd;
        } else {
            $rule->repeatEndDate = null;
        }

        $rule->frequency = $payload['frequency'] ?? null;
        $rule->frequencyHours = !empty($payload['frequencyHours']) ? (int) $payload['frequencyHours'] : null;
        $rule->frequencyDays = !empty($payload['frequencyDays']) ? (int) $payload['frequencyDays'] : null;
        $rule->frequencyMonths = !empty($payload['frequencyMonths']) ? (int) $payload['frequencyMonths'] : null;
        $rule->frequencyMonthsOn = $payload['frequencyMonthsOn'] ?? null;
        $rule->frequencyWeeks = !empty($payload['frequencyWeeks']) ? (int) $payload['frequencyWeeks'] : null;
        $rule->frequencyWeeksOn = $payload['frequencyWeeksOn'] ?? null;

        $rule->salesStatus = $payload['salesStatus'] ?? null;

        $startDate = $payload['startDate'];
        $endDate = $payload['endDate'];
        $rule->startDate->setTimestamp($startDate);
        $rule->endDate->setTimestamp($endDate);

        $aggregate->updateRule($rule);

        return $aggregate;
    }

    public function createEvent(CommandInterface $command): EventInterface
    {
        return new EventRuleEditEvent(
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

        if (empty($payload['startDate'])) {
            throw new CommandValidationException(
                'You must provide a startDate',
                CODE_BAD_REQUEST,
                NULL,
                $command
            );
        }

        if (empty($payload['endDate'])) {
            throw new CommandValidationException(
                'You must provide a endDate',
                CODE_BAD_REQUEST,
                NULL,
                $command
            );
        }

        if (empty($payload['uuid'])) {
            throw new CommandValidationException(
                'You must provide a rule uuid',
                CODE_BAD_REQUEST,
                NULL,
                $command
            );
        }

        return true;
    }
}
