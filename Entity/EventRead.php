<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use RevisionTen\CMS\Traits\LanguageAndWebsiteTrait;
use RevisionTen\CMS\Traits\ReadModelTrait;

class EventRead
{
    use ReadModelTrait;
    use LanguageAndWebsiteTrait;

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
}
