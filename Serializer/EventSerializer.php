<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Serializer;

use RevisionTen\Calendar\Entity\Event;
use RevisionTen\Calendar\Entity\EventRead;
use Solarium\QueryType\Update\Query\Query;

class EventSerializer implements SolrSerializerInterface
{
    public function serialize(Query $update, Event $event, ?EventRead $eventRead = null): array
    {
        $docs = [];
        $helper = $update->getHelper();

        $id = $event->getUuid();

        if ($event->deleted) {
            // Delete page from index.
            $update->addDeleteById($id);
            return [];
        }

        $docs[$id] = $update->createDocument();
        $docs[$id]->id = $id;
        $docs[$id]->ispage_b = false;
        $docs[$id]->website_i = $event->website;
        $docs[$id]->language_s = $event->language;
        $docs[$id]->template_s = 'Event';
        $docs[$id]->created_dt = $helper->formatDate($event->created);
        $docs[$id]->modified_dt = $helper->formatDate($event->modified);
        $docs[$id]->title_t = $helper->filterControlCharacters($event->title);
        $docs[$id]->description_t = $helper->filterControlCharacters($event->description);

        return $docs;
    }
}
