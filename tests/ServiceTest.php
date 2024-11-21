<?php

namespace SimplifyServiceLayer\Tests;

use PHPUnit\Framework\TestCase;
use SimplifyServiceLayer\Service;

/**
 * @internal
 *
 * @coversNothing
 */
class ServiceTest extends TestCase
{
    public function testLoadDataFromInput()
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

    public function testLoadDataFromInputChildBatchService()
    {
        $childService = new class extends Service {
            public static function getLoaders()
            {
                return [
                    'result' => function () {
                        return 'child result value';
                    },
                ];
            }
        };

        $service = new class(['result' => [[get_class($childService)], [get_class($childService)]]], []) extends Service {
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

    public function testLoadDataFromInputService()
    {
        $childService = new class extends Service {
            public static function getLoaders()
            {
                return [
                    'result' => function () {
                        return 'child result value';
                    },
                ];
            }
        };

        $service = new class(['result' => [get_class($childService)]], []) extends Service {
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

    public function testLoadDataFromLoader()
    {
        $service1 = new class extends Service {
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
        $service1->run();

        $this->assertEquals($service1->getErrors()->getArrayCopy(), []);

        $service2 = new class extends Service {
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

        $service2->run();

        $this->assertNotEquals($service2->getErrors()->getArrayCopy(), []);
    }

    public function testLoadDataFromLoaderWithDependency()
    {
        $service1 = new class extends Service {
            public static function getBindNames()
            {
                return [
                    'result' => 'name for result',
                ];
            }

            public static function getLoaders()
            {
                return [
                    'aaa' => function () {
                        return 'aaaaaa';
                    },
                    'result' => function ($aaa) {
                        return $aaa.' value';
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
        $service1->run();

        $this->assertEquals($service1->getErrors()->getArrayCopy(), []);
        $this->assertEquals($service1->getData()['result'], 'aaaaaa value');
    }

    public function testLoadDataKeyInvaildBecauseOfChildrenRule()
    {
        $service = new class extends Service {
            public static function getBindNames()
            {
                return [
                    'result' => 'result[...] name',
                ];
            }

            public static function getLoaders()
            {
                return [
                    'result' => function () {
                        return [
                            'a' => [
                                'c' => 'ccc',
                            ],
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
        $this->assertTrue($service->getValidations()->offsetGet('result.b'));
    }

    public function testLoadDataKeyInvaildBecauseOfParentRule()
    {
        $service = new class extends Service {
            public static function getBindNames()
            {
                return [
                    'result' => 'result[...] name',
                ];
            }

            public static function getLoaders()
            {
                return [
                    'result' => function () {
                        return [
                            'a' => [
                                'c' => 'ccc',
                            ],
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
                    'result.a' => ['array', 'string'],
                    'result.a.c' => ['string'],
                    'result.b' => ['array'],
                    'result.b.c' => ['string'],
                ];
            }
        };

        $service->run();

        $this->assertFalse($service->getValidations()->offsetGet('result'));
        $this->assertFalse($service->getValidations()->offsetGet('result.a'));
        $this->assertFalse($service->getValidations()->offsetGet('result.a.c'));
        $this->assertTrue($service->getValidations()->offsetGet('result.b'));
        $this->assertTrue($service->getValidations()->offsetGet('result.b.c'));
    }

    public function testLoadName()
    {
        $service = new class([], ['result' => 'result name']) extends Service {
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

        $this->assertNotEquals($service->getErrors()->getArrayCopy(), []);
        $this->assertStringContainsString('result name', $service->getErrors()->getArrayCopy()['result'][0]);
    }

    public function testLoadNameBound()
    {
        $service = new class([], []) extends Service {
            public static function getBindNames()
            {
                return ['result' => 'result name'];
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

        $this->assertNotEquals($service->getErrors()->getArrayCopy(), []);
        $this->assertStringContainsString('result name', $service->getErrors()->getArrayCopy()['result'][0]);
    }

    public function testLoadNameBoundNested()
    {
        $service = new class([], ['result' => '{{abcd}}', 'aaa' => 'aaaa', 'abcd' => '{{aaa}} bbb ccc ddd']) extends Service {
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

        $this->assertNotEquals($service->getErrors()->getArrayCopy(), []);
        $this->assertStringContainsString('aaaa bbb ccc ddd', $service->getErrors()->getArrayCopy()['result'][0]);
    }

    public function testLoadNameMultidimension()
    {
        $service = new class(['result' => ['a' => ['c' => 'ccc']]], ['result' => 'result[...] name']) extends Service {
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
                    'result' => ['array', 'required'],
                    'result.a' => ['array', 'required'],
                    'result.a.b' => ['required'],
                ];
            }
        };

        $service->run();

        $this->assertNotEquals($service->getErrors()->getArrayCopy(), []);
    }
}
