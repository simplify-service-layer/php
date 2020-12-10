<?php

namespace Dbwhddn10\FService\Cloud;

use Dbwhddn10\FService\Service;
use Aws\Sdk;

class AwsSdkService extends Service
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
            'config' => function ($key, $region, $secret) {

                return [
                    'version' => 'latest',
                    'region'  => $region,
                    'credentials' => [
                        'key'    => $key,
                        'secret' => $secret,
                    ]
                ];
            },

            'key' => function () {

                throw new \Exception;
            },

            'region' => function () {

                throw new \Exception;
            },

            'result' => function ($config) {

                return new Sdk($config);
            },

            'secret' => function () {

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
        return [];
    }

    public static function getArrTraits()
    {
        return [];
    }
}
