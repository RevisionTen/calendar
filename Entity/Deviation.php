<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Entity;

use DateTimeInterface;

class Deviation
{
    public DateTimeInterface $deviationStartDate;

    public DateTimeInterface $deviationEndDate;


    public DateTimeInterface $startDate;

    public DateTimeInterface $endDate;

    public ?string $salesStatus = null;

    public ?int $participants = null;

    public ?array $venue = null;

    public ?array $extra = null;

    public function __construct(DateTimeInterface $deviationStartDate, DateTimeInterface $deviationEndDate)
    {
        $this->deviationStartDate = $deviationStartDate;
        $this->deviationEndDate = $deviationEndDate;
    }

    public function getKey(): string
    {
        return $this->deviationStartDate->getTimestamp() . '_' . $this->deviationEndDate->getTimestamp();
    }
}
