<?php

namespace FunctionalCoding\Illuminate;

use ArrayAccess;
use FunctionalCoding\Service;
use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class ValidationProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        Service::setValidationErrorListResolver(function ($key, $data = [], $ruleList = [], $names = []) {
            Validator::setFacadeApplication($this->app);
            $validator = Validator::make([], [], [], []);
            $data = (new Collection($data))->toArray();

            if (preg_match('/\.\*$/', $key)) {
                $arrayKey = preg_replace('/\.\*$/', '', $key);
                $array = Arr::get($data, $arrayKey);
                $rules = [];
                if (!is_array($array) && !($array instanceof ArrayAccess)) {
                    throw new \Exception($arrayKey.' key must has array rule');
                }
                foreach ($array as $i => $v) {
                    $rules[$arrayKey.'.'.$i] = array_keys($ruleList);
                    $names[$arrayKey.'.'.$i] = str_replace('*', $i, $names[$key]);
                }
                unset($rules[$key], $names[$key]);
            } else {
                $rules = [$key => array_keys($ruleList)];
            }

            $validator->setData($data);
            $validator->setRules($rules);
            $validator->setCustomMessages([]);
            $validator->setAttributeNames($names);
            $locale = $this->app instanceof Application ? $this->app->getLocale() : 'en';

            foreach ($ruleList as $rule => $class) {
                if (!preg_match('/^App/', $class)) {
                    $this->extend($validator, $rule, $locale, $class);
                }
            }

            $validator->passes();

            return $validator->errors();
        });
    }

    protected function extend($validator, $rule, $locale, $class)
    {
        $rule = \explode(':', $rule)[0];
        $servicePath = (new \ReflectionClass($class))->getFileName();
        $matches = [];
        \preg_match('/[\/\\\]Service[s]{0,1}[\/\\\]/', $servicePath, $matches);
        $dirSeperator = $matches[0][-1];
        $validationPath = \preg_replace('/[\/\\\]Service[s]{0,1}[\/\\\].*$/', '', $servicePath).$dirSeperator.'Validation'.$dirSeperator.'Illuminate'.$dirSeperator;
        $validators = include $validationPath.'validators.php';
        $replacers = include $validationPath.'replacers.php';
        $implicitRules = include $validationPath.'implicitRules.php';
        $dependentRules = include $validationPath.'dependentRules.php';
        $messages = include $validationPath.'lang'.$dirSeperator.$locale.'.php';

        foreach ($validators as $i => $v) {
            $validators[$i] = \Closure::bind($v, new \ArrayObject($validators));
        }

        foreach ($replacers as $i => $v) {
            $replacers[$i] = \Closure::bind($v, $validator);
        }

        if (in_array($rule, $implicitRules) && isset($validators[$rule])) {
            $validator->addImplicitExtension($rule, $validators[$rule]);
        } elseif (in_array($rule, $dependentRules) && isset($validators[$rule])) {
            $validator->addDependentExtension($rule, $validators[$rule]);
        } elseif (isset($validators[$rule])) {
            $validator->addExtension($rule, $validators[$rule]);
        }

        if ($replacers) {
            $validator->addReplacers($replacers);
        }

        if (isset($messages[$rule])) {
            $validator->setCustomMessages([$rule => $messages[$rule]]);
        }
    }
}
