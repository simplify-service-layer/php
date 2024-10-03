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
    protected function getValidationErrorLists(array $data = [], array $ruleLists = [], array $names = [])
    {
        $messages = [];
        $validator = Validator::newInstance($data, $ruleLists, $names, $messages);
        $validator->passes();

        return $validator->errors();
    }
}
