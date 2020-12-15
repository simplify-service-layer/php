<?php

namespace Dbwhddn10\FService\DB\Illuminate\Feature;

use Dbwhddn10\FService\Service;
use Dbwhddn10\FService\DB\Illuminate\Feature\QueryFeatureService;

class FieldsFeatureService extends Service
{
    public static function getArrBindNames()
    {
        return [
            'available_fields'
                => 'options for {{fields}}',
        ];
    }

    public static function getArrCallbackLists()
    {
        return [
            'query.fields' => function ($availableFields, $fields='', $query) {

                $fields = $fields ? preg_split('/\s*,\s*/', $fields) : $availableFields;

                $query->select($fields);
            },
        ];
    }

    public static function getArrLoaders()
    {
        return [
            'available_fields' => function ($modelClass) {

                $model = new $modelClass;

                return array_merge($model->getFillable(), $model->getGuarded());
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
            'fields'
                => ['string', 'several_in:{{available_fields}}'],
        ];
    }

    public static function getArrTraits()
    {
        return [
            QueryFeatureService::class,
        ];
    }
}
