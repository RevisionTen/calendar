<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Entity;

use DateTimeInterface;
use Exception;
use RevisionTen\CQRS\Model\Aggregate;

class Event extends Aggregate
{
    public const STATE_PRE_SALE = 'preSale';
    public const STATE_SALE = 'sale';
    public const STATE_SOLD = 'sold';
    public const STATE_POSTPONED = 'postponed';
    public const STATE_CANCELLED = 'cancelled';

    public ?int $website = null;

    public ?string $language = null;

    public bool $deleted = false;

    public ?string $title = null;

    public ?string $description = null;

    public ?string $bookingInfo = null;

    public ?string $artist = null;

    public ?string $organizer = null;

    public string $salesStatus = self::STATE_SALE;

    public ?array $image = null;

    public ?array $venue = null;

    public array $genres = [];

    public array $keywords = [];

    public array $partners = [];

    /**
     * @var Rule[]
     */
    public array $rules = [];

    /**
     * @var Deviation[]
     */
    public array $deviations = [];

    public array $extra = [];

    public function getRule(string $ruleUuid): Rule
    {
        return $this->rules[$ruleUuid];
    }

    public function addRule(Rule $rule): void
    {
        $this->rules[$rule->uuid] = $rule;
    }

    public function updateRule(Rule $rule): void
    {
        $this->rules[$rule->uuid] = $rule;
    }

    public function deleteRule(Rule $rule): void
    {
        unset($this->rules[$rule->uuid]);
    }

    public function getDeviation(DateTimeInterface $ruleStartDate, DateTimeInterface $ruleEndDate): Deviation
    {
        $proxyDeviation = new Deviation($ruleStartDate, $ruleEndDate);

        return $this->deviations[$proxyDeviation->getKey()];
    }

    public function addDeviation(Deviation $deviation): void
    {
        $this->deviations[$deviation->getKey()] = $deviation;
    }

    public function updateDeviation(Deviation $deviation): void
    {
        $this->deviations[$deviation->getKey()] = $deviation;
    }

    public function deleteDeviation(Deviation $deviation): void
    {
        unset($this->deviations[$deviation->getKey()]);
    }

    /**
     * @throws Exception
     */
    public function getDates(): array
    {
        $dates = [];

        foreach ($this->rules as $rule) {
            array_push($dates, ...$rule->getDates($this));
        }

        usort($dates, static function(Date $a, Date $b) {
            return $a->startDate->getTimestamp() >= $b->startDate->getTimestamp();
        });

        return $dates;
    }
}
