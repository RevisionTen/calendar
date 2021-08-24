<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Handler;

use RevisionTen\Calendar\Event\EventRuleDeleteEvent;
use RevisionTen\Calendar\Entity\Event;
use RevisionTen\CQRS\Exception\CommandValidationException;
use RevisionTen\CQRS\Interfaces\AggregateInterface;
use RevisionTen\CQRS\Interfaces\CommandInterface;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\CQRS\Interfaces\HandlerInterface;

final class EventRuleDeleteHandler implements HandlerInterface
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

        $aggregate->deleteRule($rule);

        return $aggregate;
    }

    public function createEvent(CommandInterface $command): EventInterface
    {
        return new EventRuleDeleteEvent(
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
                'You must provide a rule uuid',
                CODE_BAD_REQUEST,
                NULL,
                $command
            );
        }

        return true;
    }
}
