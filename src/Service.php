<?php

namespace FunctionalCoding;

use FunctionalCoding\Validation\Validator;

class Service extends ServiceBase
{
    public static function getValidationErrors($locale, $data, $ruleLists, $names)
    {
        $validator = Validator::newInstance($locale, $data, $ruleLists, $names);
        $validator->passes();

        return $validator->errors()->messages();
    }

    protected function filterPresentRelatedRuleList($ruleList)
    {
        return array_filter($ruleList, function ($rule) {
            return preg_match('/^required/', $rule);
        });
    }

    protected function getLocale()
    {
        return 'en';
    }

    protected function getMustPresentDependencyKeysInRuleLists($ruleLists)
    {
        $dependencies = [];
        foreach ($ruleLists as $k => $ruleList) {
            foreach ($ruleList as $i => $rule) {
                $bindKeys = $this->getBindKeys($rule);
                foreach ($bindKeys as $bindKey) {
                    if (!preg_match('/^required/', $rule)) {
                        $dependencies[] = $bindKey;
                    }
                }
            }
        }

        return $dependencies;
    }

    protected function getNotMustPresentDependencyKeysInRuleLists($ruleLists)
    {
        $dependencies = [];
        foreach ($ruleLists as $k => $ruleList) {
            foreach ($ruleList as $i => $rule) {
                $bindKeys = $this->getBindKeys($rule);
                foreach ($bindKeys as $bindKey) {
                    if (preg_match('/^required/', $rule)) {
                        $dependencies[] = $bindKey;
                    }
                }
            }
        }

        return $dependencies;
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

    protected function hasArrayObjectRuleInRuleList($ruleList)
    {
        if (!empty($ruleList) && in_array('array', $ruleList)) {
            return true;
        }

        return false;
    }

    protected function removeDependencySymbolInRuleLists($key, $data, $ruleLists)
    {
        foreach ($ruleLists as $k => $ruleList) {
            foreach ($ruleList as $i => $rule) {
                $ruleList[$i] = preg_replace(static::BIND_NAME_EXP, '$1', $rule);
            }
            $ruleLists[$k] = $ruleList;
        }

        return $ruleLists;
    }
}
