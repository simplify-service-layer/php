<?php

namespace Dbwhddn10\FService\DB\Illuminate;

use Dbwhddn10\FService\Service;
use Dbwhddn10\FService\DB\Illuminate\Feature\ModelFeatureService;

class UpdatingService extends Service
{
    public static function getArrBindNames()
    {
        return [];
    }

    public static function getArrCallbackLists()
    {
        return [
            'result.model' => function ($model) {

                $model->save();
            },
        ];
    }

    public static function getArrLoaders()
    {
        return [
            'result' => function ($model) {

                return $model;
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
            ModelFeatureService::class,
        ];
    }
}
