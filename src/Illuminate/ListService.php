<?php

namespace Dbwhddn10\FService\Illuminate;

use Dbwhddn10\FService\Service;
use Dbwhddn10\FService\Illuminate\Feature\ExpandsFeatureService;
use Dbwhddn10\FService\Illuminate\Feature\FieldsFeatureService;
use Dbwhddn10\FService\Illuminate\Feature\OrderByFeatureService;

class ListService extends Service
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
            'result' => function ($query) {

                return $query->get();
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
            OrderByFeatureService::class,
        ];
    }
}
