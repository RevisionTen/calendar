<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use RevisionTen\CMS\Entity\Alias;
use RevisionTen\CMS\Traits\LanguageAndWebsiteTrait;
use RevisionTen\CMS\Traits\ReadModelTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="calendar_event_read", uniqueConstraints={
 *      @ORM\UniqueConstraint(name="unique_file",
 *          columns={"uuid"})
 * })
 */
class EventRead
{
    use ReadModelTrait;
    use LanguageAndWebsiteTrait;

    /**
     * @ORM\OneToOne(targetEntity="RevisionTen\CMS\Entity\Alias", cascade={"persist", "remove"}))
     * @ORM\JoinColumn(onDelete="SET NULL", nullable=true)
     */
    private ?Alias $alias = null;

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

    public function __construct()
    {
        $this->alias = new Alias();
    }

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

    public function getAlias(): ?Alias
    {
        return $this->alias;
    }

    public function setAlias(?Alias $alias): EventRead
    {
        $this->alias = $alias;

        return $this;
    }
}
