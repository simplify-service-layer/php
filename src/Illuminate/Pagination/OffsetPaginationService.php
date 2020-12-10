<?php

namespace Dbwhddn10\FService\Illuminate\Pagination;

use Dbwhddn10\FService\Service;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Dbwhddn10\FService\Illuminate\SelectQueryService;

class OffsetPaginationService extends Service {

    public static function getArrBindNames()
    {
        return [];
    }

    public static function getArrCallbackLists()
    {
        return [
            'query.skip' => function ($query, $skip) {

                $query->skip($skip);
            },
        ];
    }

    public static function getArrLoaders()
    {
        return [
            'result' => function ($limit, $page, $query, $selectQuery) {

                $query = (clone $query)->toBase();
                $query->limit = null;
                $query->offset = null;

                return app()->makeWith(LengthAwarePaginator::class, [
                    'items' => $selectQuery->get(),
                    'total' => $query->count(),
                    'perPage' => $limit,
                    'currentPage' => $page,
                    'options' => [
                        'path' => Paginator::resolveCurrentPath(),
                        'pageName' => 'page',
                    ]
                ]);
            },

            'select_query' => function ($query) {

                return [SelectQueryService::class, [
                    'query'
                        => $query,
                ]];
            },

            'skip' => function ($limit, $page) {

                return ( $page - 1 ) * $limit;
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
            'page'
                => ['required', 'integer', 'min:1'],
        ];
    }

    public static function getArrTraits()
    {
        return [];
    }

}
