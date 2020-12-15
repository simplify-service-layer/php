<?php

namespace Dbwhddn10\FService\DB\Illuminate;

use Dbwhddn10\FService\Service;
use Dbwhddn10\FService\DB\Illuminate\Feature\ExpandsFeatureService;
use Dbwhddn10\FService\DB\Illuminate\Feature\FieldsFeatureService;
use Dbwhddn10\FService\DB\Illuminate\Feature\LimitFeatureService;

class RandomListService extends Service
{
    public static function getArrBindNames()
    {
        return [];
    }

    public static function getArrCallbackLists()
    {
        return [
            'query.order_by' => function ($query) {

                $query->orderByRaw('RAND()');
            },
        ];
    }

    public static function getArrLoaders()
    {
        return [
            'result' => function ($selectQuery) {

                return $selectQuery->get();
            },

            'select_query' => function ($query) {

                return [SelectQueryService::class, [
                    'query'
                        => $query
                ]];
            },
        ];
    }

    public static function getArrPromiseLists()
    {
        return [];
    }

    public static function getArrRuleLists()
    {
        return [];
    }

    public static function getArrTraits()
    {
        return [
            ExpandsFeatureService::class,
            FieldsFeatureService::class,
            LimitFeatureService::class,
        ];
    }
}
