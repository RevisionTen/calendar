<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Handler;

use DateTime;
use RevisionTen\Calendar\Entity\Rule;
use RevisionTen\Calendar\Event\EventRuleCreateEvent;
use RevisionTen\Calendar\Entity\Event;
use RevisionTen\CQRS\Exception\CommandValidationException;
use RevisionTen\CQRS\Interfaces\AggregateInterface;
use RevisionTen\CQRS\Interfaces\CommandInterface;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\CQRS\Interfaces\HandlerInterface;

final class EventRuleCreateHandler implements HandlerInterface
{
    /**
     * {@inheritdoc}
     *
     * @var Event $aggregate
     */
    public function execute(EventInterface $event, AggregateInterface $aggregate): AggregateInterface
    {
        $payload = $event->getPayload();

        $ruleUuid = $event->getCommandUuid();

        $rule = new Rule($ruleUuid);
        $rule->uuid = $ruleUuid;
        $rule->ruleTitle = $payload['ruleTitle'];
        $rule->title = $payload['title'];
        $rule->participants = !empty($payload['participants']) ? (int) $payload['participants'] : null;
        $rule->description = $payload['description'] ?? null;
        $rule->bookingInfo = $payload['bookingInfo'] ?? null;
        $rule->venue = $payload['venue'] ?? null;
        $rule->artist = $payload['artist'] ?? null;
        $rule->organizer = $payload['organizer'] ?? null;
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
        $rule->frequencyDays = !empty($payload['frequencyDays']) ? (int) $payload['frequencyDays'] : null;
        $rule->frequencyMonths = !empty($payload['frequencyMonths']) ? (int) $payload['frequencyMonths'] : null;
        $rule->frequencyMonthsOn = $payload['frequencyMonthsOn'] ?? null;
        $rule->frequencyWeeks = !empty($payload['frequencyWeeks']) ? (int) $payload['frequencyWeeks'] : null;
        $rule->frequencyWeeksOn = $payload['frequencyWeeksOn'] ?? null;

        $startDate = $payload['startDate'];
        $endDate = $payload['endDate'];
        $rule->startDate->setTimestamp($startDate);
        $rule->endDate->setTimestamp($endDate);

        $aggregate->addRule($rule);

        return $aggregate;
    }

    public function createEvent(CommandInterface $command): EventInterface
    {
        return new EventRuleCreateEvent(
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

        if (empty($payload['ruleTitle'])) {
            throw new CommandValidationException(
                'You must enter a rule title',
                CODE_BAD_REQUEST,
                NULL,
                $command
            );
        }

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

        return true;
    }
}
