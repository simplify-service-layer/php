<?php

namespace FnService;

use ArrayObject;
use Closure;

class Service {

    const BIND_NAME_EXP = '/\{\{([a-z0-9\_\.\*]+)\}\}/';

    protected static Closure $resolverForGetValidationErrors;
    protected ArrayObject $childs;
    protected ArrayObject $data;
    protected ArrayObject $errors;
    protected ArrayObject $inputs;
    protected ArrayObject $names;
    protected ArrayObject $validated;
    protected bool $processed;

    public function __construct(array $inputs = [], array $names = [])
    {
        $this->childs    = new ArrayObject;
        $this->data      = new ArrayObject;
        $this->errors    = new ArrayObject;
        $this->inputs    = new ArrayObject($inputs);
        $this->names     = new ArrayObject($names);
        $this->validated = new ArrayObject;
        $this->processed = false;

        foreach ( $this->inputs as $key => $value )
        {
            $this->validate($key);
        }
    }

    public function childs()
    {
        return $this->childs;
    }

    public function data()
    {
        $data = clone $this->data;

        $data->ksort();

        return $data;
    }

    public function errors()
    {
        return clone $this->errors;
    }

    public static function getAllBindNames()
    {
        $arr = [];

        foreach ( static::getAllTraits() as $class )
        {
            $arr = array_merge($arr, $class::getArrBindNames());
        }

        $arr = array_merge($arr, static::getArrBindNames());

        return new ArrayObject($arr);
    }

    public static function getAllCallbackLists()
    {
        $arr = [];

        foreach ( static::getAllTraits() as $class )
        {
            $arr = array_merge($arr, $class::getArrCallbackLists());
        }

        $arr = array_merge($arr, static::getArrCallbackLists());

        return new ArrayObject($arr);
    }

    public static function getAllLoaders()
    {
        $arr = [];

        foreach ( static::getAllTraits() as $class )
        {
            $arr = array_merge($arr, $class::getArrLoaders());
        }

        $arr = array_merge($arr, static::getArrLoaders());

        return new ArrayObject($arr);
    }

    public static function getAllPromiseLists()
    {
        $arr = [];

        foreach ( static::getAllTraits() as $class )
        {
            $arr = array_merge_recursive($arr, $class::getArrPromiseLists());
        }

        $arr = array_merge_recursive($arr, static::getArrPromiseLists());

        return new ArrayObject($arr);
    }

    public static function getAllRuleLists()
    {
        $arr = [];

        foreach ( static::getAllTraits() as $class )
        {
            $arr = array_merge_recursive($arr, $class::getArrRuleLists());
        }

        $arr = array_merge_recursive($arr, static::getArrRuleLists());

        return new ArrayObject($arr);
    }

    public static function getAllTraits()
    {
        $arr = [];

        foreach ( static::getArrTraits() as $class )
        {
            $arr = array_merge($arr, $class::getAllTraits()->getArrayCopy());
        }

        $arr = array_merge($arr, static::getArrTraits());
        $arr = array_unique($arr);

        return new ArrayObject($arr);
    }

    public static function getArrBindNames()
    {
        return [];
    }

    public static function getArrCallbackLists()
    {
        return [];
    }

    public static function getArrLoaders()
    {
        return [];
    }

    public static function getArrPromiseLists()
    {
        return [];
    }

    public static function getArrRuleLists()
    {
        return [];
    }

    public static function getArrTraits()
    {
        return [];
    }

    public static function initService($value)
    {
        isset($value[1])? : $value[1] = [];
        isset($value[2])? : $value[2] = [];

        $class  = $value[0];
        $data   = $value[1];
        $names  = $value[2];

        foreach ( $data as $key => $value )
        {
            if ( $value === '')
            {
                unset($data[$key]);
            }
        }

        return new $class($data, $names);
    }

    public function inputs()
    {
        return clone $this->inputs;
    }

    public static function isInitable($value)
    {
        return is_array($value) && array_key_exists(0, $value) && is_string($value[0]) && is_a($value[0], Service::class, true);
    }

    protected function isRequiredRule($rule)
    {
        return preg_match('/^required/', $rule);
    }

    protected function isResolveError($value)
    {
        $errorClass = get_class($this->resolveError());

        return is_object($value) && $value instanceof $errorClass;
    }

    protected function getAvailableDataWith($key)
    {
        $key    = explode('.', $key)[0];
        $data   = $this->data();
        $loader = $this->getAllLoaders()->offsetGet($key);

        if ( $data->offsetExists($key) )
        {
            return $data;
        }

        if ( $this->inputs()->offsetExists($key) )
        {
            $value  = $this->inputs()->offsetGet($key);
            $loader = function () use ($value) {

                return $value;
            };
        }

        if ( empty($loader) )
        {
            return $data;
        }

        $value     = $this->resolve($loader);
        $isArray   = is_array($value) && !static::isInitable($value);
        $arrValue  = $isArray ? $value : [$value];
        $isService = static::isInitable($arrValue[0]);
        $hasError  = false;

        foreach ( $arrValue as $i => $value )
        {
            if ( !$isService )
            {
                break;
            }

            isset($value[2])? : $value[2] = [];

            foreach ( $value[2] as $k => $name )
            {
                $value[2][$k] = $this->resolveBindName($name);
            }

            $service = static::initService($value);
            $value   = $service->run();

            $this->childs->offsetSet($isArray ? $key : $key.'.'.$i, $service);

            $arrValue[$i] = $value;

            if ( $this->isResolveError($value) )
            {
                unset($arrValue[$i]);
                $hasError = true;
            }
        }

        if ( !$hasError )
        {
            $data->offsetSet($key, $isArray ? $arrValue : $arrValue[0]);
        }

        return $data;
    }

    protected function getAvailableRulesWith($key)
    {
        $rules   = $this->getAllRuleLists()->offsetGet($key) ? : [];
        $mainKey = explode('.', $key)[0];

        if ( ! $this->getAllLoaders()->offsetExists($mainKey) && ! $this->inputs->offsetExists($mainKey) )
        {
            $rules = array_filter($rules, function ($rule) {
                return $this->isRequiredRule($rule);
            });
        }

        if ( empty($rules) )
        {
            return [];
        }

        $this->names->offsetSet($key, $this->resolveBindName('{{'.$key.'}}'));

        foreach ( $rules as $i => $rule )
        {
            $bindKeys = $this->getBindKeys($rule);

            foreach ( $bindKeys as $bindKey )
            {
                $this->names->offsetSet($bindKey, $this->resolveBindName('{{'.$bindKey.'}}'));

                if ( ! $this->validate($bindKey) )
                {
                    $this->validated->offsetSet($mainKey, false);

                    unset($rules[$i]);

                    continue;
                }

                if ( ! $this->isRequiredRule($rule) && ! $this->data()->offsetExists($bindKey) )
                {
                    throw new \Exception('"' . $bindKey . '" key required rule not exists');
                }
            }

            if ( array_key_exists($i, $rules) )
            {
                $rules[$i] = preg_replace(static::BIND_NAME_EXP, '$1', $rule);
            }
        }

        return array_values($rules);
    }

    protected function getBindKeys(string $str)
    {
        $matches = [];

        preg_match_all(static::BIND_NAME_EXP, $str, $matches);

        return $matches[1];
    }

    protected function getClosureDependencies(Closure $func)
    {
        if ( $func == null )
        {
            return [];
        }

        $deps   = [];
        $params = (new \ReflectionFunction($func))->getParameters();

        foreach ( $params as $i => $param )
        {
            $deps[] = strtolower(
                preg_replace(
                    [
                        '#([A-Z][a-z]*)(\d+[A-Z][a-z]*\d+)#',
                        '#([A-Z]+\d*)([A-Z])#',
                        '#([a-z]+\d*)([A-Z])#',
                        '#([^_\d])([A-Z][a-z])#'
                    ],
                    '$1_$2',
                    $param->name
                )
            );
        }

        return $deps;
    }

    protected function getPromiseOrderedDependencies($keys)
    {
        $arr  = [];
        $rtn  = [];

        foreach ( $keys as $key )
        {
            $deps = $this->getAllPromiseLists()->offsetGet($key) ? : [];
            $list = $this->getPromiseOrderedDependencies($deps);
            $list = array_merge($list, [$key]);
            $arr  = array_merge($list, $arr);
        }

        foreach ( $arr as $value )
        {
            $rtn[$value] = null;
        }

        return array_keys($rtn);
    }

    public function getValidationErrors($data, $ruleLists, $names)
    {
        return call_user_func_array(static::$resolverForGetValidationErrors, [$data, $ruleLists, $names]);
    }

    protected function resolve($func)
    {
        $resolver = Closure::bind($func, $this);
        $depNames = $this->getClosureDependencies($func);
        $depVals  = [];
        $params   = (new \ReflectionFunction($resolver))->getParameters();

        foreach ( $depNames as $i => $depName )
        {
            if ( $this->data->offsetExists($depName) )
            {
                $depVals[] = $this->data->offsetGet($depName);
            }
            else if ( $params[$i]->isDefaultValueAvailable() )
            {
                $depVals[] = $params[$i]->getDefaultValue();
            }
            else
            {
                // must not throw exception, but only return
                return $this->resolveError();
            }
        }

        return call_user_func_array($resolver, $depVals);
    }

    protected function resolveBindName(string $name)
    {
        while ( $boundKeys = $this->getBindKeys($name) )
        {
            $key      = $boundKeys[0];
            $pattern  = '/\{\{' . $key . '\}\}/';
            $bindName = (new ArrayObject(array_merge(
                $this->getAllBindNames()->getArrayCopy(),
                $this->names->getArrayCopy(),
            )))->offsetGet($key);

            if ( $bindName == null )
            {
                throw new \Exception('"' . $key . '" name not exists');
            }

            $replace = $this->resolveBindName($bindName);
            $name    = preg_replace($pattern, $replace, $name, 1);
        }

        return $name;
    }

    protected function resolveError()
    {
        return new \Error('can\'t be resolve');
    }

    public function run()
    {
        if ( ! $this->processed )
        {
            foreach ( $this->inputs()->getArrayCopy() as $key => $value )
            {
                $this->validate($key);
            }

            foreach ( $this->getAllRuleLists()->getArrayCopy() as $key => $value )
            {
                $this->validate(explode('.', $key)[0]);
            }

            foreach ( $this->getAllLoaders()->getArrayCopy() as $key => $value )
            {
                $this->validate($key);
            }

            $this->processed = true;
        }

        if ( ! empty($this->totalErrors()) )
        {
            return $this->resolveError();
        }

        if ( ! $this->data()->offsetExists('result') )
        {
            throw new \Exception('result data key is not exists in '.static::class);
        }

        return $this->data()->offsetGet('result');
    }

    public function runAfterCommitCallbacks()
    {
        foreach ( $this->childs as $child )
        {
            $child->runAfterCommitCallbacks();
        }

        $callbacks = array_filter($this->getAllCallbackLists()->getArrayCopy(), function ($value) {

            return preg_match('/:after_commit$/', $value);
        }, ARRAY_FILTER_USE_KEY);

        foreach ( $callbacks as $callback )
        {
            $this->resolve($callback);
        }
    }

    public static function setResolverForGetValidationErrors(Closure $resolver)
    {
        static::$resolverForGetValidationErrors = $resolver;
    }

    public function totalErrors()
    {
        $arr    = $this->errors()->getArrayCopy();
        $errors = [];

        array_walk_recursive($arr, function ($value) use ($errors) {
            $errors[] = $value;
        });

        foreach ( $this->childs() as $child )
        {
            $errors = array_merge($errors, $child->totalErrors());
        }

        return $errors;
    }

    protected function validate($key)
    {
        if ( count(explode('.', $key)) > 1 )
        {
            throw new \Exception('does not support validation with child key');
        }

        if ( $this->validated->offsetExists($key) )
        {
            return $this->validated->offsetGet($key);
        }

        $promiseList = $this->getAllPromiseLists()->offsetGet($key) ? : [];

        foreach ( $promiseList as $promise )
        {
            $segs       = explode(':', $promise);
            $promiseKey = $segs[0];
            $isStrict   = isset($segs[1]) && $segs[1] == 'strict';

            if ( !$this->validate($promiseKey) && $isStrict )
            {
                $this->validated->offsetSet($key, false);

                return false;
            }
        }

        $loader = $this->getAllLoaders()->offsetGet($key);
        $deps   = $this->getClosureDependencies($loader);

        foreach ( $deps as $dep )
        {
            if ( !$this->validate($dep) )
            {
                $this->validated->offsetSet($key, false);
            }
        }

        if ( $this->validated->offsetGet($key) === false )
        {
            return false;
        }

        $ruleList = [$key => $this->getAvailableRulesWith($key)];
        $data     = $this->getAvailableDataWith($key);

        if ( $this->getAllRuleLists()->offsetExists($key.'.*') )
        {
            $ruleList[$key.'.*'] = $this->getAvailableRulesWith($key.'.*');
        }

        foreach ( $ruleList as $key => $rules )
        {
            $newErrors = $this->getValidationErrors($data->getArrayCopy(), [$key => $rules], $this->names->getArrayCopy());

            if ( !empty($newErrors) )
            {
                $oldErrors = $this->errors->offsetGet($key) ? : [];
                $errors    = array_merge($oldErrors, $newErrors);

                $this->errors->offsetSet($key, $errors);
            }
        }

        if ( ! empty($this->errors->offsetGet($key)) || ($this->childs->offsetGet($key) && ! empty($this->childs->offsetGet($key)->totalErrors())) )
        {
            $this->validated->offsetSet($key, false);

            return false;
        }

        if ( $this->validated->offsetGet($key) === false )
        {
            return false;
        }

        if ( $data->offsetExists($key) )
        {
            $this->data->offsetSet($key, $data->offsetGet($key));
        }

        $this->validated->offsetSet($key, true);

        $promiseKeys  = array_filter(array_keys($this->getAllPromiseLists()->getArrayCopy()), function ($value) use ($key) {

            return preg_match('/^'.$key.'\\./', $value);
        });
        $callbackKeys = array_filter(array_keys($this->getAllCallbackLists()->getArrayCopy()), function ($value) use ($key) {

            return preg_match('/^'.$key.'\\./', $value);
        });
        $orderedKeys  = $this->getPromiseOrderedDependencies($promiseKeys);
        $restKeys     = array_diff($callbackKeys, $orderedKeys);
        $callbackKeys = array_merge($orderedKeys, $restKeys);

        foreach ( $callbackKeys as $callbackKey )
        {
            $callback = $this->getAllCallbackLists()->offsetGet($callbackKey);
            $deps     = $this->getClosureDependencies($callback);

            foreach ( $deps as $dep )
            {
                if ( !$this->validate($dep) )
                {
                    $this->validated->offsetSet($key, false);
                }
            }

            if ( !preg_match('/:after_commit$/', $callbackKey) )
            {
                $this->resolve($callback);
            }
        }

        if ( $this->validated->offsetGet($key) === false )
        {
            return false;
        }

        return true;
    }

    public function validated()
    {
        $arr = $this->validated->getArrayCopy();

        ksort($arr);

        return new ArrayObject($arr);
    }
}
