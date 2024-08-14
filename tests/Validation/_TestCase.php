<?php

namespace FunctionalCoding\Tests\Validation;

use FunctionalCoding\Validation\Validator;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class _TestCase extends TestCase
{
    protected function getValidatorErrors(array $data = [], array $ruleLists = [], array $names = [], $locale = 'en')
    {
        $validator = Validator::newInstance($locale, $data, $ruleLists, $names);
        $validator->passes();

        return $validator->errors();
    }
}
