<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Handler;

use Ramsey\Uuid\Uuid;
use RevisionTen\Calendar\Event\EventRuleDeleteEvent;
use RevisionTen\Calendar\Entity\Event;
use RevisionTen\Calendar\Event\EventRuleDuplicateEvent;
use RevisionTen\CQRS\Exception\CommandValidationException;
use RevisionTen\CQRS\Interfaces\AggregateInterface;
use RevisionTen\CQRS\Interfaces\CommandInterface;
use RevisionTen\CQRS\Interfaces\EventInterface;
use RevisionTen\CQRS\Interfaces\HandlerInterface;

final class EventRuleDuplicateHandler implements HandlerInterface
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
        $originalRule = $aggregate->getRule($ruleUuid);

        $rule = clone $originalRule;
        // Convert old uuid to hash (in 16char bytes) and use it as a seed.
        $commandUuid = $event->getCommandUuid();
        $seed = $commandUuid.'-'.$ruleUuid;
        $seed = md5($seed, true);
        $newUuid = Uuid::fromBytes($seed)->toString();

        $rule->uuid = $newUuid;

        $aggregate->addRuleAfter($rule, $originalRule);

        return $aggregate;
    }

    public function createEvent(CommandInterface $command): EventInterface
    {
        return new EventRuleDuplicateEvent(
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
