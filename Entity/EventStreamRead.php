<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use RevisionTen\CMS\Traits\LanguageAndWebsiteTrait;
use RevisionTen\CMS\Traits\ReadModelTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="calendar_event_stream_read", uniqueConstraints={
 *      @ORM\UniqueConstraint(name="unique_file",
 *          columns={"uuid"})
 * })
 */
class EventStreamRead
{
    use ReadModelTrait;
    use LanguageAndWebsiteTrait;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    public ?int $publishedVersion = null;

    /**
     * @ORM\Column(type="boolean", options={"default": 0})
     */
    public bool $deleted = false;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    public ?string $title = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    public ?string $salesStatus = null;

    /**
     * @ORM\Column(type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     */
    public ?DateTimeInterface $created = null;

    /**
     * @ORM\Column(type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     */
    public ?DateTimeInterface $modified = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $dates = null;

    /**
     * @return Date[]
     */
    public function getDates(): array
    {
        return $this->dates && is_string($this->dates) ? unserialize($this->dates, ['allowed_classes' => true]) : [];
    }

    public function setDates(Event $event): self
    {
        $datesWithDeviations = [];
        $dates = $event->getDates();
        foreach ($dates as $date) {
            $datesWithDeviations[] = $date->getDateWithDeviation();
        }
        $this->dates = serialize($datesWithDeviations);

        return $this;
    }
}
