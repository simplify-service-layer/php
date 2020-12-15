<?php

namespace Dbwhddn10\FService\DB\Illuminate\Feature;

use Dbwhddn10\FService\Service;
use Dbwhddn10\FService\DB\Illuminate\Feature\QueryFeatureService;
use Dbwhddn10\FService\DB\LimitFeatureService as BaseService;

class LimitFeatureService extends Service
{
    public static function getArrBindNames()
    {
        return [];
    }

    public static function getArrCallbackLists()
    {
        return [
            'query.limit' => function ($limit, $query) {

                $query->take($limit);
            },
        ];
    }

    public static function getArrLoaders()
    {
        return [];
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
            BaseService::class,
            QueryFeatureService::class,
        ];
    }
}
