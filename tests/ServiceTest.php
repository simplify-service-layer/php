<?php

namespace FnService\Tests;

use PHPUnit\Framework\TestCase;
use FnService\Service;

class ServiceTest extends TestCase {

    public function testWhenBasicCase()
    {
        $service = new class extends Service
        {
            public static function getArrBindNames()
            {
                return [
                    'result'
                    => 'name for result',
                ];
            }

            public static function getArrLoaders()
            {
                return [
                    'result' => function () {

                        return 'result value';
                    },
                ];
            }

            public static function getArrRuleLists()
            {
                return [
                    'result'
                    => ['required', 'string'],
                ];
            }
        };

        $service->run();

        $this->assertEquals($service->errors()->all(), []);

        $service = new class extends Service
        {
            public static function getArrBindNames()
            {
                return [
                    'result'
                    => 'name for result',
                ];
            }

            public static function getArrLoaders()
            {
                return [
                    'result' => function () {

                        return ['aaa', 'bbb', 'ccc'];
                    },
                ];
            }

            public static function getArrRuleLists()
            {
                return [
                    'result'
                    => ['required', 'string'],
                ];
            }
        };

        $service->run();

        $this->assertNotEquals($service->errors()->all(), []);
    }

    public function testWhenInputValueIsNotEmpty()
    {
        $service = new class([
            'result'
            => 'result value',
        ]) extends Service
        {
            public static function getArrBindNames()
            {
                return [
                    'result'
                    => 'name for key1',
                ];
            }

            public static function getArrLoaders()
            {
                return [];
            }

            public static function getArrRuleLists()
            {
                return [
                    'result'
                    => ['required'],
                ];
            }
        };

        $service->run();

        $this->assertEquals($service->errors()->all(), []);
    }

    public function testWhenBindNameValueIsNotEmpty()
    {
        $service = new class([
            'result'
            => 'result value',
        ], [
            'result'
            => 'result name',
        ]) extends Service
        {
            public static function getArrBindNames()
            {
                return [];
            }

            public static function getArrLoaders()
            {
                return [];
            }

            public static function getArrRuleLists()
            {
                return [
                    'result'
                    => ['required'],
                ];
            }
        };

        $service->run();

        $this->assertEquals($service->errors()->all(), []);
    }

    public function testWhenInputValueIsServiceInitable()
    {
        $service = new class extends Service
        {
            public static function getArrLoaders()
            {
                return [
                    'result' => function () {

                        return 'child result value';
                    },
                ];
            }
        };

        $service = new class([
            'result'
            => [get_class($service)],
        ], [
        ]) extends Service
        {
            public static function getArrBindNames()
            {
                return [
                    'result'
                    => 'parent result name',
                ];
            }

            public static function getArrLoaders()
            {
                return [];
            }

            public static function getArrRuleLists()
            {
                return [
                    'result'
                    => ['required'],
                ];
            }
        };

        $value = $service->run();

        $this->assertEquals($value, 'child result value');
        $this->assertEquals($service->errors()->all(), []);
    }

    public function testWhenInputValueIsBatchService()
    {
        $service = new class extends Service
        {
            public static function getArrLoaders()
            {
                return [
                    'result' => function () {

                        return 'child result value';
                    },
                ];
            }
        };

        $service = new class([
            'result'
            => [
                [get_class($service)],
                [get_class($service)],
            ],
        ], [
        ]) extends Service
        {
            public static function getArrBindNames()
            {
                return [
                    'result'
                    => 'parent result name',
                ];
            }

            public static function getArrLoaders()
            {
                return [];
            }

            public static function getArrRuleLists()
            {
                return [
                    'result'
                    => ['required'],
                ];
            }
        };

        $value = $service->run();

        $this->assertEquals($value, [
            'child result value',
            'child result value',
        ]);
        $this->assertEquals($service->errors()->all(), []);
    }
}