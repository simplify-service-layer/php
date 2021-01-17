<?php

namespace FnService\DB;

use FnService\Service;
use FnService\DB\LimitFeatureService;

class SkipFeatureService extends Service
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
        return [
            LimitFeatureService::class,
        ];
    }
}
