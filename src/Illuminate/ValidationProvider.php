<?php

namespace FunctionalCoding\Illuminate;

use FunctionalCoding\Service;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Application;

class ValidationProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        Service::setResolverForGetValidationErrors(function ($key, $data = [], $ruleList = [], $names = []) {
            Validator::setFacadeApplication($this->app);
            $validator = Validator::make([], [], [], []);
            $validator->setData($data);
            $validator->setRules([$key => array_keys($ruleList)]);
            $validator->setCustomMessages([]);
            $validator->setAttributeNames($names);
            $locale = $this->app instanceof Application ? $this->app->getLocale() : 'en';

            foreach ($ruleList as $rule => $class) {
                $this->extend($validator, $key, $rule, $locale, $class);
            }

            $validator->passes();

            return $validator->errors()->all();
        });
    }

    protected function extend($validator, $key, $rule, $locale, $class)
    {
        $rule = \explode(':', $rule)[0];
        $servicePath = (new \ReflectionClass($class))->getFileName();
        $validationPath = \preg_replace('/\\\Service(|s)\\\.*$/', '', $servicePath).'\\Validation\\Illuminate\\';
        $validators = include $validationPath.'validators.php';
        $replacers = include $validationPath.'replacers.php';
        $implicitRules = include $validationPath.'implicitRules.php';
        $dependentRules = include $validationPath.'dependentRules.php';
        $messages = include $validationPath.'lang\\'.$locale.'.php';

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
