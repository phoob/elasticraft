<?php

namespace dfo\elasticraft\transformers;

use craft\elements\Entry;
use dfo\elasticraft\BaseTransformer;

class EntryTransformer extends BaseTransformer {
    private $versionid = null;
    private $draftId = null;

    public function __construct($versionId = null, $draftId = null) {
        $this->versionId = $versionId;
        $this->draftId = $draftId;
    }

    public function transform(Entry $entry) {
        if ($this->draftId) {
            // Fetch by draft id
            $entry = Craft::$app->getEntryRevisions()->getDraftById($this->draftId);
            if (!$entry) { return []; }
        }

        // Build the page response
        return [
            'id' => $entry->id,
            'active' => $entry->enabled,

            'date' => $this->getDates($entry),
            'updateNote' => $entry->updateNote,

            'slug' => $entry->slug,
            'uri' => $entry->uri,
            'type' => $entry->type->handle,


            'title' => $entry->title,
            'body' => $this->getBody($entry),

            'bodyText' => $this->getBodyText($entry),
        ];
    }

    protected function getBody(Entry $entry) 
    {
        // Build the html body of the document 
        return '<p>Hello world.</p>';
    }

    protected function getBodyText(Entry $entry)
    {
        // Build raw text body of the document for search
        return 'Hello world.';
    }
}