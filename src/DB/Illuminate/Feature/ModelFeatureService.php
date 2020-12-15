<?php

namespace Dbwhddn10\FService\DB\Illuminate\Feature;

use Dbwhddn10\FService\Service;
use Dbwhddn10\FService\DB\Illuminate\Feature\QueryFeatureService;

class ModelFeatureService extends Service
{
    public static function getArrBindNames()
    {
        return [
            'model'
                => 'model for {{id}}',
        ];
    }

    public static function getArrCallbackLists()
    {
        return [
            'query.id' => function ($id, $query) {

                $query->where($query->getModel()->getKeyName(), $id);
            },
        ];
    }

    public static function getArrLoaders()
    {
        return [
            'model' => function ($query) {

                return $query->first();
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
            'id'
                => ['required', 'integer'],

            'model'
                => ['not_null']
        ];
    }

    public static function getArrTraits()
    {
        return [
            QueryFeatureService::class,
        ];
    }
}
