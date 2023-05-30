<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Entity;

use DateTimeInterface;

class Deviation
{
    public string $uuid;

    public DateTimeInterface $deviationStartDate;

    public DateTimeInterface $deviationEndDate;

    public ?DateTimeInterface $startDate = null;

    public ?DateTimeInterface $endDate = null;

    public ?string $salesStatus = null;

    public ?int $participants = null;

    public ?array $venue = null;

    public ?array $extra = null;

    public ?array $keywords = null;

    public ?array $genres = null;

    public function __construct(string $deviationUuid, DateTimeInterface $deviationStartDate, DateTimeInterface $deviationEndDate)
    {
        $this->uuid = $deviationUuid;
        $this->deviationStartDate = $deviationStartDate;
        $this->deviationEndDate = $deviationEndDate;
    }
}
