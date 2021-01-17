<?php

namespace FnService\DB;

use FnService\Service;

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
        return [];
    }

    public static function getArrLoaders()
    {
        return [
            'available_order_by' => function () {

                throw new \Exception;
            },

            'order_by' => function () {

                throw new \Exception;
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
        return [];
    }
}
