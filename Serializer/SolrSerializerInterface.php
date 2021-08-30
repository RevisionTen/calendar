<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Serializer;

use RevisionTen\Calendar\Entity\Event;
use RevisionTen\Calendar\Entity\EventRead;
use Solarium\QueryType\Update\Query\Query;

interface SolrSerializerInterface
{
    public function serialize(Query $update, Event $event, ?EventRead $eventRead = null): array;
}
