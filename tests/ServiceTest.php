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
    public function testCallback()
    {
        $service = (new class extends Service {
            public static function getBindNames()
            {
                return [
                    'result' => 'name for result',
                ];
            }

            public static function getCallbacks()
            {
                return [
                    'result__cb1' => function ($result) {
                        $result->abcd = 'aaaa';
                    },
                ];
            }

            public static function getRuleLists()
            {
                return [
                    'result' => ['required'],
                ];
            }
        })->init(['result' => (object) ['aaaa' => 'aaaa']]);

        $service->run();

        $this->assertEquals($service->getErrors()->getArrayCopy(), []);
        $this->assertEquals($service->getData()['result']->aaaa, 'aaaa');
        $this->assertEquals($service->getData()['result']->abcd, 'aaaa');
    }

    public function testCallbackWithDependency()
    {
        $service1 = (new class extends Service {
            public static function getBindNames()
            {
                return [
                    'result' => 'name for result',
                ];
            }

            public static function getCallbacks()
            {
                return [
                    'result__cb1' => function ($result, $test1) {
                        $result->abcd = $test1;
                    },
                    'result__cb2' => function ($result, $test2) {
                        $result->bcde = $test2;
                    },
                ];
            }

            public static function getLoaders()
            {
                return [
                    'test1' => function () {
                        return 'test1 val';
                    },
                ];
            }

            public static function getRuleLists()
            {
                return [
                    'result' => ['required'],
                ];
            }
        })->init(['result' => (object) ['aaaa' => 'aaaa']]);

        $service1->run();

        $this->assertEquals($service1->getErrors()->getArrayCopy(), []);
        $this->assertEquals($service1->getData()['result']->aaaa, 'aaaa');
        $this->assertEquals($service1->getData()['result']->abcd, 'test1 val');
        $this->assertTrue($service1->getValidations()['result']);
        $this->assertTrue($service1->getValidations()['test1']);
        $this->assertTrue($service1->getValidations()['test2']);
        $this->assertFalse(
            array_key_exists('bcde', (array) $service1->getData()['result'])
        );

        $service2 = (new class extends Service {
            public static function getBindNames()
            {
                return [
                    'result' => 'name for result',
                    'test2' => 'name for test2',
                ];
            }

            public static function getCallbacks()
            {
                return [
                    'result__cb1' => function ($result, $test1) {
                        $result->abcd = $test1;
                    },
                    'result__cb2' => function ($result, $test2) {
                        $result->bcde = $test2;
                    },
                ];
            }

            public static function getLoaders()
            {
                return [
                    'test1' => function () {
                        return 'test1 val';
                    },
                ];
            }

            public static function getRuleLists()
            {
                return [
                    'result' => ['required'],
                    'test2' => ['required'],
                ];
            }
        })->init(['result' => (object) ['aaaa' => 'aaaa']]);

        $service2->run();

        $this->assertNotEquals($service2->getErrors()->getArrayCopy(), []);
        $this->assertFalse($service2->getValidations()['result']);
        $this->assertTrue($service2->getValidations()['test1']);
        $this->assertFalse($service2->getValidations()['test2']);
        $this->assertFalse(
            array_key_exists('abcd', (array) $service2->getData()['result'])
        );
        $this->assertFalse(
            array_key_exists('bcde', (array) $service2->getData()['result'])
        );
    }

    public function testLoadDataFromInput()
    {
        $service = (new class extends Service {
            public static function getBindNames()
            {
                return [
                    'result' => 'name for result',
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
        })->init(['result' => 'result value']);

        $service->run();

        $this->assertEquals($service->getErrors()->getArrayCopy(), []);
    }

    public function testLoadDataFromInputChildBatchService()
    {
        $childService = (new class extends Service {
            public static function getLoaders()
            {
                return [
                    'result' => function () {
                        return 'child result value';
                    },
                ];
            }
        })->init();

        $service = (new class extends Service {
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
        })->init([
            'result' => [
                [get_class($childService)],
                [get_class($childService)],
            ],
        ], []);

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
        $childService = (new class extends Service {
            public static function getLoaders()
            {
                return [
                    'result' => function () {
                        return 'child result value';
                    },
                ];
            }
        })->init();

        $service = (new class extends Service {
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
        })->init([
            'result' => [get_class($childService)],
        ], []);

        $service->run();
        $value = $service->getData()->offsetGet('result');

        $this->assertEquals($value, 'child result value');
        $this->assertEquals($service->getErrors()->getArrayCopy(), []);
    }

    public function testLoadDataFromLoader()
    {
        $service1 = (new class extends Service {
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
        })->init();

        $service1->run();

        $this->assertEquals($service1->getErrors()->getArrayCopy(), []);

        $service2 = (new class extends Service {
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
        })->init();

        $service2->run();

        $this->assertNotEquals($service2->getErrors()->getArrayCopy(), []);
    }

    public function testLoadDataFromLoaderWithDependency()
    {
        $service1 = (new class extends Service {
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
        })->init();

        $service1->run();

        $this->assertEquals($service1->getErrors()->getArrayCopy(), []);
        $this->assertEquals($service1->getData()['result'], 'aaaaaa value');
    }

    public function testLoadDataFromProperty()
    {
        $service1 = (new class extends Service {
            public $result = 'aaa';

            public static function getBindNames()
            {
                return [
                    'result' => 'name for result',
                ];
            }

            public static function getLoaders()
            {
                return [];
            }

            public static function getRuleLists()
            {
                return [
                    'result' => ['required', 'string'],
                ];
            }
        })->init();

        $service1->run();

        $this->assertEquals($service1->getErrors()->getArrayCopy(), []);

        $service2 = (new class extends Service {
            public $result = ['aaa', 'bbb', 'ccc'];

            public static function getBindNames()
            {
                return [
                    'result' => 'name for result',
                ];
            }

            public static function getLoaders()
            {
                return [];
            }

            public static function getRuleLists()
            {
                return [
                    'result' => ['required', 'string'],
                ];
            }
        })->init();

        $service2->run();

        $this->assertNotEquals($service2->getErrors()->getArrayCopy(), []);
    }

    public function testLoadDataFromPropertyInDependency()
    {
        $service1 = (new class extends Service {
            public $aaa = 'aaaaaa';

            public static function getBindNames()
            {
                return [
                    'result' => 'name for result',
                ];
            }

            public static function getLoaders()
            {
                return [
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
        })->init();

        $service1->run();

        $this->assertEquals($service1->getErrors()->getArrayCopy(), []);
        $this->assertEquals($service1->getData()['result'], 'aaaaaa value');
    }

    public function testLoadDataKeyInvaildBecauseOfChildrenRule()
    {
        $service = (new class extends Service {
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
        })->init();

        $service->run();

        $this->assertFalse($service->getValidations()->offsetGet('result'));
        $this->assertFalse($service->getValidations()->offsetGet('result.a'));
        $this->assertTrue($service->getValidations()->offsetGet('result.b'));
    }

    public function testLoadDataKeyInvaildBecauseOfParentRule()
    {
        $service = (new class extends Service {
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
        })->init();

        $service->run();

        $this->assertFalse($service->getValidations()->offsetGet('result'));
        $this->assertFalse($service->getValidations()->offsetGet('result.a'));
        $this->assertFalse($service->getValidations()->offsetGet('result.a.c'));
        $this->assertTrue($service->getValidations()->offsetGet('result.b'));
        $this->assertTrue($service->getValidations()->offsetGet('result.b.c'));
    }

    public function testLoadName()
    {
        $service = (new class extends Service {
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
        })->init(
            [],
            ['result' => 'result name'],
        );

        $service->run();

        $this->assertNotEquals($service->getErrors()->getArrayCopy(), []);
        $this->assertStringContainsString('result name', $service->getErrors()->getArrayCopy()['result'][0]);
    }

    public function testLoadNameBound()
    {
        $service = (new class extends Service {
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
        })->init([], []);

        $service->run();

        $this->assertNotEquals($service->getErrors()->getArrayCopy(), []);
        $this->assertStringContainsString('result name', $service->getErrors()->getArrayCopy()['result'][0]);
    }

    public function testLoadNameBoundNested()
    {
        $service = (new class extends Service {
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
        })->init(
            [],
            [
                'result' => '{{abcd}}',
                'aaa' => 'aaaa',
                'abcd' => '{{aaa}} bbb ccc ddd',
            ],
        );

        $service->run();

        $this->assertNotEquals($service->getErrors()->getArrayCopy(), []);
        $this->assertStringContainsString('aaaa bbb ccc ddd', $service->getErrors()->getArrayCopy()['result'][0]);
    }

    public function testLoadNameMultidimension()
    {
        $service = (new class extends Service {
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
        })->init(
            ['result' => ['a' => ['c' => 'ccc']]],
            ['result' => 'result[...] name'],
        );

        $service->run();

        $this->assertNotEquals($service->getErrors()->getArrayCopy(), []);
    }
}
