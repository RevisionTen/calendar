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

    public function getRepeatEndDate(): DateTime
    {
        $repeatEndDate = $this->repeatEndDate;
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

    protected function getDate(DateTimeInterface $startDate, DateTimeInterface $endDate, Event $event): array
    {
        return [
            'ruleUuid' => $this->uuid,
            'eventUuid' => $event->uuid,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'title' => $event->title,
            'participants' => $this->participants,
        ];
    }

    /**
     * @throws Exception
     */
    public function getDailyRepeats(Event $event): array
    {
        $dates = [];
        $dates[] = $this->getDate($this->startDate, $this->endDate, $event);

        $interval = new DateInterval('P' . $this->frequencyDays . 'D');
        $range = new DatePeriod($this->startDate, $interval ,$this->getRepeatEndDate(), DatePeriod::EXCLUDE_START_DATE);

        foreach($range as $startDate) {
            /**
             * Diff original startDate and endDate and add diff to new startDate
             *
             * @var DateTime $endDate
             */
            $endDate = clone $startDate;
            $diff = $this->startDate->diff($this->endDate, true);
            $endDate->add($diff);

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
        $range = new DatePeriod($this->startDate, $interval ,$this->getRepeatEndDate(), DatePeriod::EXCLUDE_START_DATE);

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

            // Diff original startDate and endDate and add diff to new startDate
            $endDate = clone $startDate;
            $diff = $this->startDate->diff($this->endDate, true);
            $endDate->add($diff);

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
        $range = new DatePeriod($this->startDate, $interval ,$this->getRepeatEndDate(), DatePeriod::EXCLUDE_START_DATE);

        foreach($range as $start) {
            /**
             * @var DateTime $startDate
             */
            $startDate = clone $start;
            $startDate->setDate((int) $startDate->format('Y'), (int) $startDate->format('n'), (int) $this->frequencyMonthsOn);

            // Diff original startDate and endDate and add diff to new startDate
            $endDate = clone $startDate;
            $diff = $this->startDate->diff($this->endDate, true);
            $endDate->add($diff);

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
