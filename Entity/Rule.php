<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Entity;

use DateTime;
use DateTimeInterface;

class Rule
{
    public string $uuid;

    public ?string $title = null;

    public ?int $participants = null;

    public DateTimeInterface $startDate;

    public DateTimeInterface $endDate;

    public ?string $frequency = null;

    public ?DateTimeInterface $repeatEndDate = null;

    public ?int $frequencyDays = null;

    public ?int $frequencyWeeks = null;

    public ?string $frequencyWeeksOn = null;

    public ?int $frequencyMonths = null;

    public ?string $frequencyMonthsOn = null;

    public function __construct(string $uuid)
    {
        $this->uuid = $uuid;
        $this->startDate = new DateTime();
        $this->endDate = new DateTime();
    }
}
