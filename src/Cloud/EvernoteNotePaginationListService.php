<?php

namespace Dbwhddn10\FService\Cloud;

use Dbwhddn10\FService\Cloud\EvernoteNoteService;
use Dbwhddn10\FService\DB\OrderByFeatureService;
use Dbwhddn10\FService\DB\SkipFeatureService;
use Dbwhddn10\FService\Service;
use EDAM\NoteStore\NoteFilter;
use EDAM\Types\NoteSortOrder;

class EvernoteNotePaginationListService extends Service
{
    public static function getArrBindNames()
    {
        return [];
    }

    public static function getArrCallbackLists()
    {
        return [
            'filter.order' => function ($filter, $orderBy) {

                $order = explode(' ', $orderBy)[0];

                if ( $order == 'created' )
                {
                    $filter->order = NoteSortOrder::CREATED;
                }
                else if ( $order == 'title' )
                {
                    $filter->order = NoteSortOrder::TITLE;
                }
                else if ( $order == 'updated' )
                {
                    $filter->order = NoteSortOrder::UPDATED;
                }
                else if ( $order == 'relevance' )
                {
                    $filter->order = NoteSortOrder::RELEVANCE;
                }
                else
                {
                    throw new \Exception;
                }
            },

            'filter.ascending' => function ($filter, $orderBy) {

                $orderType = explode(' ', $orderBy)[1];

                if ( $orderType == 'asc' )
                {
                    $filter->ascending = true;
                }
                else if ( $orderType == 'desc' )
                {
                    $filter->ascending = false;
                }
                else
                {
                    throw new \Exception;
                }
            },

            'filter.tagGuids' => function ($filter, $tagIds) {

                $filter->tagGuids = explode(',', $tagIds);
            },

            'filter.words' => function ($filter, $words) {

                $filter->words = $words;
            },
        ];
    }

    public static function getArrLoaders()
    {
        return [
            'available_order_by' => function () {

                return ['created asc', 'created desc', 'updated asc', 'updated desc', 'title asc', 'relevance desc'];
            },

            'client' => function ($token='') {

                return [EvernoteNoteService::class, [
                    'token'
                        => $token,
                ], [
                    'token'
                        => '{{token}}',
                ]];
            },

            'filter' => function () {

                return new NoteFilter;
            },

            'order_by' => function () {

                return 'created desc';
            },

            'result' => function ($client, $filter, $skip, $limit) {

                $store = $client->getAdvancedClient()->getNoteStore();
                $spec  = new \EDAM\NoteStore\NotesMetadataResultSpec([
                    'includeTitle' => true,
                ]);
                $list  = $store->findNotesMetadata($filter, $skip, $limit, $spec);

                foreach ( $list->notes as $i => $note )
                {
                    $note->content = $store->getNoteContent($note->guid);
                }

                return $list;
            },
        ];
    }

    public static function getArrPromiseLists()
    {
        return [];
    }

    public static function getArrRuleLists()
    {
        return [
            'order_by'
                => ['string', 'in:created asc,created desc,relevance desc,title asc,updated asc,updated desc'],

            'words'
                => ['string'],

            'tag_ids'
                => ['string'],
        ];
    }

    public static function getArrTraits()
    {
        return [
            SkipFeatureService::class,
            OrderByFeatureService::class,
        ];
    }
}
