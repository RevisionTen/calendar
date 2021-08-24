<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Entity;

use RevisionTen\CQRS\Model\Aggregate;

class Event extends Aggregate
{
    public const STATE_PRE_SALE = 'preSale';
    public const STATE_SALE = 'sale';
    public const STATE_SOLD = 'sold';
    public const STATE_POSTPONED = 'postponed';
    public const STATE_CANCELLED = 'cancelled';

    public ?int $website = null;

    public ?string $language = null;

    public bool $deleted = false;

    public ?string $title = null;

    public ?string $description = null;

    public ?string $artist = null;

    public ?string $organizer = null;

    public string $salesStatus = self::STATE_SALE;

    public ?array $image = null;

    public ?array $venue = null;

    public array $genres = [];

    public array $keywords = [];

    public array $partners = [];

    /**
     * @var Rule[]
     */
    public array $rules = [];

    public array $exclusions = [];

    public array $extra = [];
}
