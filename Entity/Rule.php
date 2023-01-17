<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Entity;

use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeInterface;
use Exception;

class Rule
{
    public string $uuid;

    public ?string $ruleTitle = null;

    public ?string $title = null;

    public ?int $participants = null;

    public ?array $venue = null;

    public ?string $artist = null;

    public ?string $organizer = null;

    public ?string $description = null;

    public ?string $bookingInfo = null;

    public ?array $image = null;

    public ?array $extra = null;

    public DateTimeInterface $startDate;

    public DateTimeInterface $endDate;

    public ?string $frequency = null;

    public ?DateTimeInterface $repeatEndDate = null;

    public ?int $frequencyHours = null;

    public ?int $frequencyDays = null;

    public ?int $frequencyWeeks = null;

    public ?string $frequencyWeeksOn = null;

    public ?int $frequencyMonths = null;

    public ?string $frequencyMonthsOn = null;

    public ?string $salesStatus = null;

    public function __construct(string $uuid)
    {
        $this->uuid = $uuid;
        $this->startDate = new DateTime();
        $this->endDate = new DateTime();
    }

    public function getRepeatEndDate(): DateTime
    {
        $repeatEndDate = $this->repeatEndDate ? clone $this->repeatEndDate : null;
        if (null === $repeatEndDate) {
            // Repeats one year if no end date is set.
            $repeatEndDate = clone $this->startDate;
            $repeatEndDate->setTime(0, 0, 0);
            $repeatEndDate->add(new DateInterval('P365D'));
        }

        // Repeats until the end of the last day.
        $repeatEndDate->add(new DateInterval('P1D'));

        return $repeatEndDate;
    }

    /**
     * @throws Exception
     */
    public function getDates(Event $event): array
    {
        switch ($this->frequency) {
            case 'hourly':
                $dates = $this->getHourlyRepeats($event);
                break;
            case 'daily':
                $dates = $this->getDailyRepeats($event);
                break;
            case 'weekly':
                $dates = $this->getWeeklyRepeats($event);
                break;
            case 'monthly':
                $dates = $this->getMonthlyRepeats($event);
                break;
            default:
                // Just once.
                $dates = [$this->getDate($this->startDate, $this->endDate, $event)];
                break;
        }

        return $dates;
    }

    protected function getDate(DateTimeInterface $startDate, DateTimeInterface $endDate, Event $event): Date
    {
        $date = new Date();

        $date->ruleTitle = $this->ruleTitle;
        $date->participants = $this->participants;
        $date->endDate = $endDate;
        $date->startDate = $startDate;

        $date->eventUuid = $event->uuid;
        $date->ruleUuid = $this->uuid;

        $date->website = $event->website;
        $date->language = $event->language;
        $date->salesStatus = $this->salesStatus ?? $event->salesStatus;
        $date->image = $this->image ?? $event->image;
        $date->genres = $event->genres;
        $date->keywords = $event->keywords;
        $date->partners = $event->partners;

        $date->eventExtra = $event->extra;
        $date->ruleExtra = $this->extra;

        $date->artist = $this->artist ?? $event->artist;
        $date->venue = $this->venue ?? $event->venue;
        $date->description = $this->description ?? $event->description;
        $date->bookingInfo = $this->bookingInfo ?? $event->bookingInfo;
        $date->title = $this->title ?? $event->title;
        $date->organizer = $this->organizer ?? $event->organizer;

        return $date;
    }

    /**
     * @throws Exception
     */
    public function getHourlyRepeats(Event $event): array
    {
        $dates = [];
        $dates[] = $this->getDate($this->startDate, $this->endDate, $event);

        $interval = new DateInterval('PT' . $this->frequencyHours . 'H');
        $repeatEndDate = clone $this->repeatEndDate;
        $repeatEndDate->add(new DateInterval('PT1S')); // Add one second, so last date is included.
        $range = new DatePeriod($this->startDate, $interval, $repeatEndDate, DatePeriod::EXCLUDE_START_DATE);

        // Diff original startDate and endDate.
        $duration = $this->startDate->diff($this->endDate, true);

        foreach($range as $startDate) {
            /**
             * @var DateTime $endDate
             */
            $endDate = clone $startDate;
            $endDate->add($duration);

            $dates[] = $this->getDate($startDate, $endDate, $event);
        }

        return $dates;
    }

    /**
     * @throws Exception
     */
    public function getDailyRepeats(Event $event): array
    {
        $dates = [];
        $dates[] = $this->getDate($this->startDate, $this->endDate, $event);

        $interval = new DateInterval('P' . $this->frequencyDays . 'D');
        $range = new DatePeriod($this->startDate, $interval, $this->getRepeatEndDate(), DatePeriod::EXCLUDE_START_DATE);

        // Diff original startDate and endDate.
        $duration = $this->startDate->diff($this->endDate, true);

        foreach($range as $startDate) {
            /**
             * @var DateTime $endDate
             */
            $endDate = clone $startDate;
            $endDate->add($duration);

            $dates[] = $this->getDate($startDate, $endDate, $event);
        }

        return $dates;
    }

    /**
     * @throws Exception
     */
    public function getWeeklyRepeats(Event $event): array
    {
        $dates = [];
        $dates[] = $this->getDate($this->startDate, $this->endDate, $event);

        $interval = new DateInterval('P' . ($this->frequencyWeeks*7) . 'D');
        $range = new DatePeriod($this->startDate, $interval, $this->getRepeatEndDate(), DatePeriod::EXCLUDE_START_DATE);

        // Diff original startDate and endDate.
        $duration = $this->startDate->diff($this->endDate, true);

        foreach($range as $start) {
            /**
             * @var DateTime $startDate
             */
            $startDate = clone $start;

            // Correct the weekday.
            $correctDay = strtolower($startDate->format('l')) === strtolower($this->frequencyWeeksOn);
            if (!$correctDay) {
                // Wrong day, set correct day.
                $intervalDayNumber = (int) $startDate->format('N');
                $addOrSubtractDays = self::getDayDifference($intervalDayNumber, $this->frequencyWeeksOn);
                if ($addOrSubtractDays > 0) {
                    // Add days.
                    $correctionInterval = new DateInterval('P' . $addOrSubtractDays . 'D');
                    $startDate->add($correctionInterval);
                } else {
                    // Remove days.
                    $correctionInterval = new DateInterval('P' . ($addOrSubtractDays*-1) . 'D');
                    $startDate->sub($correctionInterval);
                }
            }

            $endDate = clone $startDate;
            $endDate->add($duration);

            $dates[] = $this->getDate($startDate, $endDate, $event);
        }

        return $dates;
    }

    /**
     * @throws Exception
     */
    public function getMonthlyRepeats(Event $event): array
    {
        $dates = [];
        $dates[] = $this->getDate($this->startDate, $this->endDate, $event);

        $interval = new DateInterval('P' . $this->frequencyMonths . 'M');
        $range = new DatePeriod($this->startDate, $interval, $this->getRepeatEndDate(), DatePeriod::EXCLUDE_START_DATE);

        // Diff original startDate and endDate.
        $duration = $this->startDate->diff($this->endDate, true);

        foreach($range as $start) {
            /**
             * @var DateTime $startDate
             */
            $startDate = clone $start;
            $startDate->setDate((int) $startDate->format('Y'), (int) $startDate->format('n'), (int) $this->frequencyMonthsOn);

            $endDate = clone $startDate;
            $endDate->add($duration);

            $dates[] = $this->getDate($startDate, $endDate, $event);
        }

        return $dates;
    }

    /**
     * @param int    $wrongDay
     * @param string $wantedDay
     *
     * @return int
     */
    private static function getDayDifference(int $wrongDay, string $wantedDay): int
    {
        $wanted = null;
        switch ($wantedDay) {
            case 'monday':
                $wanted = 1;
                break;
            case 'tuesday':
                $wanted = 2;
                break;
            case 'wednesday':
                $wanted = 3;
                break;
            case 'thursday':
                $wanted = 4;
                break;
            case 'friday':
                $wanted = 5;
                break;
            case 'saturday':
                $wanted = 6;
                break;
            case 'sunday':
                $wanted = 7;
                break;
        }

        return $wanted - $wrongDay;
    }
}
