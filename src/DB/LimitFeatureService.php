<?php

namespace Dbwhddn10\FService\DB;

use Dbwhddn10\FService\Service;

class LimitFeatureService extends Service
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
            'limit' => function () {

                return 30;
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
            'limit'
                => ['required', 'integer', 'max:120', 'min:1'],
        ];
    }

    public static function getArrTraits()
    {
        return [];
    }
}
