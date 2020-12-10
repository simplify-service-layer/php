<?php

namespace Dbwhddn10\FService\Illuminate;

use Dbwhddn10\FService\Service;
use Dbwhddn10\FService\Illuminate\Pagination\CursorPaginationService;
use Dbwhddn10\FService\Illuminate\Pagination\OffsetPaginationService;
use Dbwhddn10\FService\Illuminate\Feature\ExpandsFeatureService;
use Dbwhddn10\FService\Illuminate\Feature\FieldsFeatureService;
use Dbwhddn10\FService\Illuminate\Feature\LimitFeatureService;
use Dbwhddn10\FService\Illuminate\Feature\OrderByFeatureService;

class PaginationListService extends Service
{
    public static function getArrBindNames()
    {
        return [];
    }

    public static function getArrCallbackLists()
    {
        return [];
    }

    public static function getArrLoaders()
    {
        return [
            'cursor' => function ($cursorId='', $modelClass='') {

                throw new \Exception;
            },

            'result' => function ($cursor='', $limit, $orderByArray, $page='', $query) {

                if ( $page !== '' )
                {
                    return [OffsetPaginationService::class, [
                        'limit'
                            => $limit,
                        'page'
                            => $page,
                        'query'
                            => $query,
                    ], [
                        'page'
                            => '{{page}}',
                    ]];
                }
                else
                {
                    return [CursorPaginationService::class, [
                        'cursor'
                            => $cursor,
                        'limit'
                            => $limit,
                        'order_by_array'
                            => $orderByArray,
                        'query'
                            => $query,
                    ]];
                }
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
            'cursor_id'
                => ['integer'],
        ];
    }

    public static function getArrTraits()
    {
        return [
            ExpandsFeatureService::class,
            FieldsFeatureService::class,
            LimitFeatureService::class,
            OrderByFeatureService::class,
        ];
    }
}
