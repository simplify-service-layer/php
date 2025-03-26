<?php

namespace SimplifyServiceLayer;

use SimplifyServiceLayer\Validation\Validator;

class Service extends ServiceBase
{
    public static function filterPresentRelatedRule($rule)
    {
        return preg_match('/^required/', $rule) || preg_match('/^present/', $rule) ? $rule : null;
    }

    public static function getDependencyKeysInRule($rule)
    {
        $matches = [];

        preg_match_all(static::BIND_NAME_EXP, $rule, $matches);

        return $matches[1];
    }

    public static function getValidationErrors($data, $ruleLists, $names, $messages)
    {
        foreach ($ruleLists as $k => $ruleList) {
            foreach ($ruleList as $j => $rule) {
                $ruleLists[$k][$j] = preg_replace(static::BIND_NAME_EXP, '$1', $rule);
            }
        }

        foreach ($names as $k => $name) {
            $names[$k] = preg_replace('/\[\.\.\.\]/', '', $name);
        }

        $validator = Validator::newInstance($data, $ruleLists, $names, $messages);
        $validator->passes();

        return $validator->errors()->messages();
    }

    public static function getValidationErrorTemplateMessages()
    {
        $locale = 'en';
        $messages = include __DIR__.DIRECTORY_SEPARATOR.'Validation'.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.$locale.'.php';

        return $messages;
    }

    public static function hasArrayObjectRuleInRuleList($ruleList, $key = null)
    {
        if (!empty($ruleList) && in_array('array', $ruleList)) {
            return true;
        }

        return false;
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
