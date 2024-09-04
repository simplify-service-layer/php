<?php

namespace SimplifyServiceLayer;

use SimplifyServiceLayer\Validation\Validator;

class Service extends ServiceBase
{
    public static function filterPresentRelatedRule($rule)
    {
        return preg_match('/^required/', $rule) ? $rule : null;
    }

    public static function getDependencyKeysInRule($rule)
    {
        $matches = [];

        preg_match_all(static::BIND_NAME_EXP, $rule, $matches);

        return $matches[1];
    }

    public static function getLocale()
    {
        return 'en';
    }

    public static function getValidationErrors($locale, $data, $ruleLists, $names)
    {
        $validator = Validator::newInstance($locale, $data, $ruleLists, $names);
        $validator->passes();

        return $validator->errors()->messages();
    }

    public static function hasArrayObjectRuleInRuleList($ruleList)
    {
        if (!empty($ruleList) && in_array('array', $ruleList)) {
            return true;
        }

        return false;
    }

    public static function removeDependencyKeySymbolInRule($rule)
    {
        return preg_replace(static::BIND_NAME_EXP, '$1', $rule);
    }

    protected function getResponseBody($result, $totalErrors)
    {
        $errors = [];

        array_walk_recursive($totalErrors, function ($value) use (&$errors) {
            $errors[] = $value;
        });

        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        return [
            'result' => $result,
        ];
    }
}
