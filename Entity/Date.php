<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Entity;

use DateTimeInterface;

class Date
{
    public ?int $website = null;

    public ?string $language = null;

    public ?string $salesStatus = null;

    public ?array $image = null;

    public ?array $venue = null;

    public ?array $genres = null;

    public ?array $keywords = null;

    public ?array $partners = null;

    public ?array $eventExtra = null;

    public ?array $ruleExtra = null;

    public ?array $deviationExtra = null;

    public ?string $ruleUuid = null;

    public ?string $eventUuid = null;

    public ?string $ruleTitle = null;

    public ?string $title = null;

    public ?int $participants = null;

    public ?string $artist = null;

    public ?string $organizer = null;

    public ?string $description = null;

    public ?string $bookingInfo = null;

    public ?DateTimeInterface $startDate = null;

    public ?DateTimeInterface $endDate = null;

    public ?Deviation $deviation = null;

    public function getDateWithDeviation(): Date
    {
        $date = clone $this;

        $deviation = $this->deviation;
        if ($deviation) {
            $date->deviationExtra = $deviation->extra;

            if ($deviation->startDate) {
                $date->startDate = $deviation->startDate;
            }
            if ($deviation->endDate) {
                $date->endDate = $deviation->endDate;
            }
            if ($deviation->venue) {
                $date->venue = $deviation->venue;
            }
            if ($deviation->participants) {
                $date->participants = $deviation->participants;
            }
            if ($deviation->salesStatus) {
                $date->salesStatus = $deviation->salesStatus;
            }
        }

        return $date;
    }
}
