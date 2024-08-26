<?php

namespace FunctionalCoding\Tests;

use FunctionalCoding\Service;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
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

        $this->assertEquals($service->getErrors()->getArrayCopy(), []);

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

        $this->assertNotEquals($service->getErrors()->getArrayCopy(), []);
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

        $this->assertEquals($service->getErrors()->getArrayCopy(), []);
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

        $service->run();
        $value = $service->getData()->offsetGet('result');

        $this->assertEquals($value, [
            'child result value',
            'child result value',
        ]);
        $this->assertEquals($service->getErrors()->getArrayCopy(), []);
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

        $this->assertEquals($service->getErrors()->getArrayCopy(), []);
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

        $service->run();
        $value = $service->getData()->offsetGet('result');

        $this->assertEquals($value, 'child result value');
        $this->assertEquals($service->getErrors()->getArrayCopy(), []);
    }

    public function testWhenParentRuleIsInvaildAndChildRuleIsValid()
    {
        $service = new class() extends Service {
            public static function getBindNames()
            {
                return [
                    'result' => 'result name',
                ];
            }

            public static function getLoaders()
            {
                return [
                    'result' => function () {
                        return [
                            'a' => [],
                            'b' => [
                                'c' => 'ccc',
                            ],
                        ];
                    },
                ];
            }

            public static function getRuleLists()
            {
                return [
                    'result' => ['array'],
                    'result.a' => ['string'],
                    'result.b' => ['array'],
                ];
            }
        };

        $service->run();

        $this->assertFalse($service->getValidations()->offsetGet('result'));
        $this->assertFalse($service->getValidations()->offsetGet('result.a'));
        $this->assertTrue($service->getValidations()->offsetExists('result.b'));
        $this->assertTrue($service->getValidations()->offsetGet('result.b'));
    }
}
