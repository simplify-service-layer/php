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
        })->setWith(['result' => (object) ['aaaa' => 'aaaa']]);

        $service->run();

        $this->assertEquals($service->getErrors(), []);
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
        })->setWith(['result' => (object) ['aaaa' => 'aaaa']]);

        $service1->run();

        $this->assertEquals($service1->getErrors(), []);
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
        })->setWith(['result' => (object) ['aaaa' => 'aaaa']]);

        $service2->run();

        $this->assertNotEquals($service2->getErrors(), []);
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
        })->setWith(['result' => 'result value']);

        $service->run();

        $this->assertEquals($service->getErrors(), []);
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
        })->setWith();

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
        })->setWith([
            'result' => [
                [get_class($childService)],
                [get_class($childService)],
            ],
        ], []);

        $service->run();
        $value = $service->getData()['result'];

        $this->assertEquals($value, [
            'child result value',
            'child result value',
        ]);
        $this->assertEquals($service->getErrors(), []);
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
        })->setWith();

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
        })->setWith([
            'result' => [get_class($childService)],
        ], []);

        $service->run();
        $value = $service->getData()['result'];

        $this->assertEquals($value, 'child result value');
        $this->assertEquals($service->getErrors(), []);
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
        })->setWith();

        $service1->run();

        $this->assertEquals($service1->getErrors(), []);

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
        })->setWith();

        $service2->run();

        $this->assertNotEquals($service2->getErrors(), []);
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
        })->setWith();

        $service1->run();

        $this->assertEquals($service1->getErrors(), []);
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
        })->setWith();

        $service1->run();

        $this->assertEquals($service1->getErrors(), []);

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
        })->setWith();

        $service2->run();

        $this->assertNotEquals($service2->getErrors(), []);
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
        })->setWith();

        $service1->run();

        $this->assertEquals($service1->getErrors(), []);
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
        })->setWith();

        $service->run();

        $this->assertFalse($service->getValidations()['result']);
        $this->assertFalse($service->getValidations()['result.a']);
        $this->assertTrue($service->getValidations()['result.b']);
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
        })->setWith();

        $service->run();

        $this->assertFalse($service->getValidations()['result']);
        $this->assertFalse($service->getValidations()['result.a']);
        $this->assertFalse($service->getValidations()['result.a.c']);
        $this->assertTrue($service->getValidations()['result.b']);
        $this->assertTrue($service->getValidations()['result.b.c']);
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
        })->setWith(
            [],
            ['result' => 'result name'],
        );

        $service->run();

        $this->assertNotEquals($service->getErrors(), []);
        $this->assertStringContainsString('result name', $service->getErrors()['result'][0]);
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
        })->setWith([], []);

        $service->run();

        $this->assertNotEquals($service->getErrors(), []);
        $this->assertStringContainsString('result name', $service->getErrors()['result'][0]);
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
        })->setWith(
            [],
            [
                'result' => '{{abcd}}',
                'aaa' => 'aaaa',
                'abcd' => '{{aaa}} bbb ccc ddd',
            ],
        );

        $service->run();

        $this->assertNotEquals($service->getErrors(), []);
        $this->assertStringContainsString('aaaa bbb ccc ddd', $service->getErrors()['result'][0]);
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
        })->setWith(
            ['result' => ['a' => ['c' => 'ccc']]],
            ['result' => 'result[...] name'],
        );

        $service->run();

        $this->assertNotEquals($service->getErrors(), []);
    }
}
