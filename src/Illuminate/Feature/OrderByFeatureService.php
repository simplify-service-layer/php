<?php

namespace Dbwhddn10\FService\Illuminate\Feature;

use Dbwhddn10\FService\Service;
use Dbwhddn10\FService\Illuminate\Feature\QueryFeatureService;

class OrderByFeatureService extends Service
{
    public static function getArrBindNames()
    {
        return [
            'available_order_by'
                => 'options for {{order_by}}',
        ];
    }

    public static function getArrCallbackLists()
    {
        return [
            'query.order_by_array' => function ($orderByArray, $query) {

                foreach ( $orderByArray as $key => $direction )
                {
                    $query->orderBy($key, $direction);
                }
            },
        ];
    }

    public static function getArrLoaders()
    {
        return [
            'available_order_by' => function ($modelClass) {

                if ( $modelClass::CREATED_AT == null )
                {
                    return [(new $modelClass)->getKeyName().' desc', (new $modelClass)->getKeyName().' asc'];
                }
                else
                {
                    return [$modelClass::CREATED_AT.' desc', $modelClass::CREATED_AT.' asc'];
                }
            },

            'order_by' => function ($modelClass) {

                if ( $modelClass::CREATED_AT == null )
                {
                    return (new $modelClass)->getKeyName().' desc';
                }
                else
                {
                    return $modelClass::CREATED_AT.' desc';
                }
            },

            'order_by_array' => function ($modelClass, $orderBy) {

                $model   = new $modelClass;
                $orderBy = preg_replace('/\s+/', ' ', $orderBy);
                $orderBy = preg_replace('/\s*,\s*/', ',', $orderBy);
                $orders  = explode(',', $orderBy);
                $array   = [];

                foreach ( $orders as $order )
                {
                    $key       = explode(' ', $order)[0];
                    $direction = str_replace($key, '', $order);

                    $array[$key] = ltrim($direction);
                }

                if ( array_keys($array)[count($array)-1] != $model->getKeyName() )
                {
                    $array[$model->getKeyName()] = end($array);
                }

                return $array;
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
            'order_by'
                => ['string', 'in_array:{{available_order_by}}.*'],
        ];
    }

    public static function getArrTraits()
    {
        return [
            QueryFeatureService::class,
        ];
    }
}
