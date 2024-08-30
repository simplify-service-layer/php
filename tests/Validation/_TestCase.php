<?php

namespace SimplifyServiceLayer\Tests\Validation;

use PHPUnit\Framework\TestCase;
use SimplifyServiceLayer\Validation\Validator;

/**
 * @internal
 *
 * @coversNothing
 */
class _TestCase extends TestCase
{
    protected function getValidationErrorLists(array $data = [], array $ruleLists = [], array $names = [], $locale = 'en')
    {
        $validator = Validator::newInstance($locale, $data, $ruleLists, $names);
        $validator->passes();

        return $validator->errors();
    }
}
