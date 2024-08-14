<?php

namespace FunctionalCoding\Tests\Validation;

/**
 * @internal
 *
 * @coversNothing
 */
final class ValidatorTest extends _TestCase
{
    public function testArray(): void
    {
        $errorKeys1 = $this->getValidatorErrors(
            ['key1' => []],
            ['key1' => ['array']],
        )->keys();
        $errorKeys2 = $this->getValidatorErrors(
            ['key1' => 'aaa'],
            ['key1' => ['array']],
        )->keys();

        $this->assertEquals($errorKeys1, []);
        $this->assertEquals($errorKeys2, ['key1']);
    }

    public function testSomeOfArray(): void
    {
        $errorKeys1 = $this->getValidatorErrors(
            ['key1' => 'aaa,ccc', 'opts' => ['aaa', 'bbb', 'ccc']],
            ['key1' => ['some_of_array:opts']],
        )->keys();

        $errorKeys2 = $this->getValidatorErrors(
            ['key1' => 'aaa,ddd', 'opts' => ['aaa', 'bbb', 'ccc']],
            ['key1' => ['some_of_array:opts']],
        )->keys();

        $this->assertEquals($errorKeys1, []);
        $this->assertEquals($errorKeys2, ['key1']);
    }
}
