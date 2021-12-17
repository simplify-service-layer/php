<?php

namespace FunctionalCoding\Tests;

use FunctionalCoding\Service;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class ServiceTest extends TestCase
{
    public function testWhenBasicCase()
    {
        $service = new class() extends Service {
            public static function getBindNames()
            {
                return [
                    'result' => 'name for result',
                ];
            }

            public static function getLoaders()
            {
                return [
                    'result' => function () {
                        return 'result value';
                    },
                ];
            }

            public static function getRuleLists()
            {
                return [
                    'result' => ['required', 'string'],
                ];
            }
        };

        $service->run();

        $this->assertEquals($service->getErrors()->all(), []);

        $service = new class() extends Service {
            public static function getBindNames()
            {
                return [
                    'result' => 'name for result',
                ];
            }

            public static function getLoaders()
            {
                return [
                    'result' => function () {
                        return ['aaa', 'bbb', 'ccc'];
                    },
                ];
            }

            public static function getRuleLists()
            {
                return [
                    'result' => ['required', 'string'],
                ];
            }
        };

        $service->run();

        $this->assertNotEquals($service->getErrors()->all(), []);
    }

    public function testWhenInputValueIsNotEmpty()
    {
        $service = new class(['result' => 'result value']) extends Service {
            public static function getBindNames()
            {
                return [
                    'result' => 'name for key1',
                ];
            }

            public static function getLoaders()
            {
                return [];
            }

            public static function getRuleLists()
            {
                return [
                    'result' => ['required'],
                ];
            }
        };

        $service->run();

        $this->assertEquals($service->getErrors()->all(), []);
    }

    public function testWhenBindNameValueIsNotEmpty()
    {
        $service = new class(['result' => 'result value'], ['result' => 'result name']) extends Service {
            public static function getBindNames()
            {
                return [];
            }

            public static function getLoaders()
            {
                return [];
            }

            public static function getRuleLists()
            {
                return [
                    'result' => ['required'],
                ];
            }
        };

        $service->run();

        $this->assertEquals($service->getErrors()->all(), []);
    }

    public function testWhenInputValueIsServiceInitable()
    {
        $service = new class() extends Service {
            public static function getLoaders()
            {
                return [
                    'result' => function () {
                        return 'child result value';
                    },
                ];
            }
        };

        $service = new class(['result' => [get_class($service)]], []) extends Service {
            public static function getBindNames()
            {
                return [
                    'result' => 'parent result name',
                ];
            }

            public static function getLoaders()
            {
                return [];
            }

            public static function getRuleLists()
            {
                return [
                    'result' => ['required'],
                ];
            }
        };

        $value = $service->run();

        $this->assertEquals($value, 'child result value');
        $this->assertEquals($service->getErrors()->all(), []);
    }

    public function testWhenInputValueIsBatchService()
    {
        $service = new class() extends Service {
            public static function getLoaders()
            {
                return [
                    'result' => function () {
                        return 'child result value';
                    },
                ];
            }
        };

        $service = new class(['result' => [[get_class($service)], [get_class($service)]]], []) extends Service {
            public static function getBindNames()
            {
                return [
                    'result' => 'parent result name',
                ];
            }

            public static function getLoaders()
            {
                return [];
            }

            public static function getRuleLists()
            {
                return [
                    'result' => ['required'],
                ];
            }
        };

        $value = $service->run();

        $this->assertEquals($value, [
            'child result value',
            'child result value',
        ]);
        $this->assertEquals($service->getErrors()->all(), []);
    }
}
