<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Entity;

use DateTime;
use DateTimeInterface;

class Rule
{
    public ?string $title = null;

    public ?int $participants = null;

    public DateTimeInterface $startDate;

    public DateTimeInterface $endDate;

    public function __construct()
    {
        $this->startDate = new DateTime();
        $this->endDate = new DateTime();
    }


}
