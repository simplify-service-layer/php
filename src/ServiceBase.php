<?php

namespace SimplifyServiceLayer;

abstract class ServiceBase
{
    public const BIND_NAME_EXP = '/\{\{([a-zA-Z][\w\.\*]+)\}\}/';
    private static array $onFailCallbacks = [];
    private static array $onStartCallbacks = [];
    private static array $onSuccessCallbacks = [];
    private \ArrayObject $childs;
    private \ArrayObject $data;
    private \ArrayObject $errors;
    private \ArrayObject $inputs;
    private bool $isRun;
    private \ArrayObject $names;
    private ?self $parent;
    private \ArrayObject $validations;

    abstract public static function filterPresentRelatedRule($rule);

    abstract public static function getDependencyKeysInRule($rule);

    abstract public static function getValidationErrors($data, $ruleLists, $names, $messages);

    abstract public static function getValidationErrorTemplateMessages();

    abstract public static function hasArrayObjectRuleInRuleList($ruleList, $key = null);

    abstract protected function getResponseBody($result, $totalErrors);

    public function __construct(array $inputs = [], array $names = [], ?self $parent = null)
    {
        $this->childs = new \ArrayObject();
        $this->data = new \ArrayObject();
        $this->errors = new \ArrayObject();
        $this->inputs = new \ArrayObject($inputs);
        $this->names = new \ArrayObject($names);
        $this->validations = new \ArrayObject();
        $this->isRun = false;
        $this->parent = $parent;

        foreach (array_keys($inputs) as $inputKey) {
            if (!preg_match('/^[a-zA-Z][\w-]{0,}/', $inputKey)) {
                throw new \Exception($inputKey.' loader key is not support pattern in '.static::class);
            }
        }

        foreach (array_keys((array) $this->inputs) as $key) {
            $this->validate($key);
        }

        // defined key validation
        static::getAllCallbacks();
        static::getAllLoaders();
    }

    public static function addOnFailCallback(\Closure $callback)
    {
        static::$onFailCallbacks[] = $callback;
    }

    public static function addOnStartCallback(\Closure $callback)
    {
        static::$onStartCallbacks[] = $callback;
    }

    public static function addOnSuccessCallback(\Closure $callback)
    {
        static::$onSuccessCallbacks[] = $callback;
    }

    public static function getAllBindNames()
    {
        $arr = [];

        foreach ([...static::getAllTraits(), static::class] as $cls) {
            $bindNames = $cls::getBindNames();
            $arr = array_merge($arr, $bindNames);
            foreach ($bindNames as $k => $v) {
                if (count(explode('.', $k)) > 1) {
                    throw new \Exception('including "." nested key '.$k.' cannot be existed in '.$cls);
                }
            }
        }

        return new \ArrayObject($arr);
    }

    public static function getAllCallbacks()
    {
        $arr = [];

        foreach (array_keys(static::getCallbacks()) as $key) {
            if (!preg_match('/^[a-zA-Z][\w-]{0,}#[\w-]{1,}(|@defer)/', $key)) {
                throw new \Exception($key.' callback key is not support pattern in '.static::class);
            }
        }

        foreach (static::getTraits() as $cls) {
            foreach ($cls::getAllCallbacks() as $key => $callback) {
                if (array_key_exists($key, $arr)) {
                    throw new \Exception($key.' callback key is duplicated in traits in '.static::class);
                }
                $arr[$key] = $callback;
            }
        }

        $arr = array_merge($arr, static::getCallbacks());

        return new \ArrayObject($arr);
    }

    public static function getAllLoaders()
    {
        $arr = [];

        foreach (array_keys(static::getLoaders()) as $key) {
            if (!preg_match('/^[a-zA-Z][\w-]{0,}/', $key)) {
                throw new \Exception($key.' loader key is not support pattern in '.static::class);
            }
        }

        foreach (static::getTraits() as $cls) {
            foreach ($cls::getAllLoaders() as $key => $loader) {
                if (array_key_exists($key, $arr)) {
                    throw new \Exception($key.' loader key is duplicated in traits in '.static::class);
                }
                $arr[$key] = $loader;
            }
        }

        $arr = array_merge($arr, static::getLoaders());

        return new \ArrayObject($arr);
    }

    public static function getAllPromiseLists()
    {
        $arr = [];

        foreach ([...static::getAllTraits(), static::class] as $cls) {
            $arr = array_merge_recursive($arr, $cls::getPromiseLists());
        }

        return new \ArrayObject($arr);
    }

    public static function getAllRuleLists()
    {
        $arr = [];

        foreach ([...static::getAllTraits(), static::class] as $cls) {
            $arr[$cls] = [];
            foreach ($cls::getRuleLists() as $key => $ruleList) {
                if (!is_array($ruleList)) {
                    $ruleList = [$ruleList];
                }
                if (!array_key_exists($key, $arr[$cls])) {
                    $arr[$cls][$key] = [];
                }
                foreach ($ruleList as $rule) {
                    array_push($arr[$cls][$key], $rule);
                }
            }
        }

        return new \ArrayObject($arr);
    }

    public static function getAllTraits()
    {
        $arr = [];

        foreach (static::getTraits() as $cls) {
            if (!static::isServiceClass($cls)) {
                throw new \Exception('trait class must extends Service');
            }
            $arr = array_merge($arr, $cls::getAllTraits()->getArrayCopy());
        }

        $arr = array_merge($arr, static::getTraits());
        $arr = array_unique($arr);

        return new \ArrayObject($arr);
    }

    public static function getBindNames()
    {
        return [];
    }

    public static function getCallbacks()
    {
        return [];
    }

    public static function getLoaders()
    {
        return [];
    }

    public static function getPromiseLists()
    {
        return [];
    }

    public static function getRuleLists()
    {
        return [];
    }

    public static function getTraits()
    {
        return [];
    }

    public static function initService($value)
    {
        isset($value[1]) ?: $value[1] = [];
        isset($value[2]) ?: $value[2] = [];
        isset($value[3]) ?: $value[3] = null;

        $cls = $value[0];
        $data = $value[1];
        $names = $value[2];
        $parent = $value[3];

        foreach ($data as $key => $value) {
            if ('' === $value) {
                unset($data[$key]);
            }
        }

        return new $cls($data, $names, $parent);
    }

    public static function isInitable($value)
    {
        return is_array($value) && array_key_exists(0, $value) && is_string($value[0]) && static::isServiceClass($value[0]);
    }

    public static function isServiceClass($value)
    {
        return is_a($value, self::class, true);
    }

    public function getChilds()
    {
        return clone $this->childs;
    }

    public function getData()
    {
        return clone $this->data;
    }

    public function getErrors()
    {
        return clone $this->errors;
    }

    public function getInputs()
    {
        return clone $this->inputs;
    }

    public function getNames()
    {
        return clone $this->names;
    }

    public function getTotalErrors()
    {
        $errors = $this->getErrors()->getArrayCopy();

        foreach ($this->getChilds() as $k => $child) {
            $childErrors = $child->getTotalErrors();
            if (!empty($childErrors)) {
                $errors[$k] = $child->getTotalErrors();
            }
        }

        return $errors;
    }

    public function getValidations()
    {
        return clone $this->validations;
    }

    public function run()
    {
        $totalErrors = $this->getTotalErrors();

        if (!$this->isRun) {
            if (!$this->parent) {
                foreach (static::$onStartCallbacks as $callback) {
                    $callback();
                }
            }

            foreach (array_keys((array) $this->getInputs()) as $key) {
                $this->validate($key);
            }

            foreach (array_keys((array) $this->getAllRuleLists()) as $cls) {
                foreach (array_keys($this->getAllRuleLists()[$cls]) as $key) {
                    $this->validate($key);
                }
            }

            foreach (array_keys((array) $this->getAllLoaders()) as $key) {
                $this->validate($key);
            }

            $totalErrors = $this->getTotalErrors();

            if (!$this->parent) {
                if (empty($totalErrors)) {
                    $this->runAllDeferCallbacks();
                    foreach (static::$onSuccessCallbacks as $callback) {
                        $callback();
                    }
                } else {
                    foreach (static::$onFailCallbacks as $callback) {
                        $callback();
                    }
                }
            }

            $this->isRun = true;
        }

        if (empty($totalErrors) && !$this->getData()->offsetExists('result')) {
            throw new \Exception('result data key is not exists in '.static::class);
        }

        if ($this->parent) {
            if (!empty($totalErrors)) {
                return $this->resolveError();
            }

            return $this->getData()->offsetGet('result');
        }

        $result = $this->getData()->offsetExists('result') ? $this->getData()->offsetGet('result') : null;

        return $this->getResponseBody($result, $totalErrors);
    }

    private function filterAvailableExpandedRuleLists($cls, $key, $data, $ruleLists)
    {
        foreach (array_keys($ruleLists) as $k) {
            $keySegs = explode('.', $k);
            for ($i = 0; $i < count($keySegs) - 1; ++$i) {
                $parentKey = implode('.', array_slice($keySegs, 0, $i + 1));
                $hasArrayObjectRule = $this->hasArrayObjectRuleInRuleLists($parentKey);

                if (!$hasArrayObjectRule) {
                    throw new \Exception($parentKey.' key must has array rule in '.$cls);
                }
            }
        }

        $i = 0;
        while (true) {
            ++$i;

            $filteredRuleLists = array_filter($ruleLists, function ($k) {
                return preg_match('/\.\*$/', $k) || preg_match('/\.\*\./', $k);
            }, ARRAY_FILTER_USE_KEY);

            if (empty($filteredRuleLists)) {
                break;
            }
            foreach (array_keys($filteredRuleLists) as $rKey) {
                $matches = [];
                preg_match('/^(.+?)\.\*/', $rKey, $matches);
                $allSegs = explode('.', $matches[1].'.*');
                $segs = [];
                $rKeyVal = (array) $data;
                $isLastKeyExists = true;

                while ($allSegs) {
                    $seg = array_shift($allSegs);
                    $segs[] = $seg;
                    $k = implode('.', $segs);

                    if (!is_array($rKeyVal) || (!empty($allSegs) && !array_key_exists($seg, $rKeyVal))) {
                        $isLastKeyExists = false;

                        break;
                    }

                    if (!empty($allSegs)) {
                        $rKeyVal = $rKeyVal[$seg];
                    }
                }

                if ($isLastKeyExists) {
                    foreach ($rKeyVal as $k => $v) {
                        $rNewKey = preg_replace('/^'.$matches[1].'\.\*/', $matches[1].'.'.$k, $rKey);
                        $ruleLists[$rNewKey] = $ruleLists[$rKey];
                    }
                }
                unset($ruleLists[$rKey]);
            }
        }

        foreach (array_keys($ruleLists) as $rKey) {
            $allSegs = explode('.', $rKey);
            $segs = [];
            $rKeyVal = (array) $data;
            while ($allSegs) {
                $seg = array_shift($allSegs);
                $segs[] = $seg;
                $k = implode('.', $segs);

                if (!array_key_exists($k, $ruleLists)) {
                    break;
                }

                if (is_array($rKeyVal) && !array_key_exists($seg, $rKeyVal)) {
                    $ruleLists[$k] = array_filter($ruleLists[$k], function ($rule) use ($cls) {
                        return $cls::filterPresentRelatedRule($rule);
                    });
                }

                if (!is_array($rKeyVal) || (!empty($allSegs) && !array_key_exists($seg, $rKeyVal))) {
                    $removeRuleLists = array_filter($ruleLists, function ($v) use ($k) {
                        return preg_match('/^'.$k.'\./', $v);
                    }, ARRAY_FILTER_USE_KEY);
                    foreach (array_keys($removeRuleLists) as $v) {
                        unset($ruleLists[$v]);
                    }

                    break;
                }

                if (!empty($allSegs)) {
                    $rKeyVal = $rKeyVal[$seg];
                }
            }
        }

        return $ruleLists;
    }

    private function getBindKeysInName(string $str)
    {
        $matches = [];

        preg_match_all(static::BIND_NAME_EXP, $str, $matches);

        return $matches[1];
    }

    private function getClosureDependencies(\Closure $func)
    {
        $deps = [];
        $params = (new \ReflectionFunction($func))->getParameters();

        foreach ($params as $i => $param) {
            $dep = $param->name;

            if (!ctype_lower($dep)) {
                $dep = preg_replace('/\s+/u', '', ucwords($dep));
                $dep = preg_replace('/(.)(?=[A-Z])/u', '$1_', $dep);
                $dep = mb_strtolower($dep, 'UTF-8');
            }

            $deps[] = $dep;
        }

        return $deps;
    }

    private function getLoadedDataWith($key)
    {
        $data = $this->getData();
        $loader = $this->getAllLoaders()->offsetExists($key) ? $this->getAllLoaders()->offsetGet($key) : null;

        if ($data->offsetExists($key)) {
            return $data;
        }

        if ($this->getInputs()->offsetExists($key)) {
            $value = $this->getInputs()->offsetGet($key);
        } else {
            if (empty($loader)) {
                return $data;
            }
            $value = $this->resolve($loader);
        }

        if ($this->isResolveError($value)) {
            return $data;
        }

        $hasServicesInArray = false;
        if (!empty($value) && is_array($value) && array_values($value) === $value) {
            foreach ($value as $v) {
                if (static::isInitable($v)) {
                    $hasServicesInArray = true;
                }
            }
        }
        $values = $hasServicesInArray ? $value : [$value];
        $hasError = false;

        foreach ($values as $i => $v) {
            $service = null;
            $resolved = null;

            if (static::isInitable($v)) {
                isset($v[1]) ?: $v[1] = [];
                isset($v[2]) ?: $v[2] = [];

                foreach ($v[2] as $k => $name) {
                    $v[2][$k] = $this->resolveBindName($name);
                }
                $v[3] = $this;

                $service = static::initService($v);
                $resolved = $service->run();
            } elseif ($v instanceof self) {
                $service = $v;
                $resolved = $service->run();
            }

            if ($service) {
                $this->childs->offsetSet($hasServicesInArray ? $key.'.'.$i : $key, $service);

                if ($this->isResolveError($resolved)) {
                    unset($values[$i]);
                    $hasError = true;

                    $this->validations->offsetSet($key, false);
                }

                $values[$i] = $resolved;
            }
        }

        if (!$hasError) {
            $this->data->offsetSet($key, $hasServicesInArray ? $values : $values[0]);
        }

        return $this->data;
    }

    private function getOrderedCallbackKeys($key)
    {
        $promiseKeys = array_filter(array_keys($this->getAllPromiseLists()->getArrayCopy()), function ($value) use ($key) {
            return preg_match('/^'.$key.'\#/', $value);
        });
        $allKeys = array_filter(array_keys($this->getAllCallbacks()->getArrayCopy()), function ($value) use ($key) {
            return preg_match('/^'.$key.'\#/', $value);
        });
        $orderedKeys = $this->getShouldOrderedCallbackKeys($promiseKeys);
        $restKeys = array_diff($allKeys, $orderedKeys);

        return array_merge($orderedKeys, $restKeys);
    }

    private function getRelatedRuleLists($key, $cls)
    {
        $ruleLists = $this->getAllRuleLists()->offsetExists($cls) ? $this->getAllRuleLists()->getArrayCopy()[$cls] : [];
        $filterLists = array_filter($ruleLists, function ($k) use ($key) {
            return preg_match('/^'.$key.'$/', $k) || preg_match('/^'.$key.'\./', $k);
        }, ARRAY_FILTER_USE_KEY);
        $keySegs = explode('.', $key);

        foreach (range(0, count($keySegs) - 2) as $i) {
            $parentKey = implode('.', array_slice($keySegs, 0, $i + 1));
            if (in_array($parentKey, array_keys($ruleLists))) {
                $filterLists[$parentKey] = $ruleLists[$parentKey];
            }
        }

        return $filterLists;
    }

    private function getShouldOrderedCallbackKeys($keys)
    {
        $arr = [];

        foreach ($keys as $key) {
            $deps = $this->getAllPromiseLists()->offsetExists($key) ? $this->getAllPromiseLists()->offsetGet($key) : [];
            $orderedKeys = $this->getShouldOrderedCallbackKeys($deps);
            $arr = [...$orderedKeys, $key, ...$arr];
        }

        return array_unique(array_values($arr));
    }

    private function hasArrayObjectRuleInRuleLists($key)
    {
        $hasArrayObjectRule = false;
        foreach ($this->getAllRuleLists() as $cl => $ruleLists) {
            $ruleList = array_key_exists($key, $ruleLists) ? $ruleLists[$key] : [];
            if ($cl::hasArrayObjectRuleInRuleList($ruleList, $key)) {
                $hasArrayObjectRule = true;
            }
        }

        return $hasArrayObjectRule;
    }

    private function isResolveError($value)
    {
        $errorClass = get_class($this->resolveError());

        return is_object($value) && $value instanceof $errorClass;
    }

    private function resolve($func)
    {
        $resolver = \Closure::bind($func, $this);
        $depNames = $this->getClosureDependencies($func);
        $depVals = [];
        $params = (new \ReflectionFunction($resolver))->getParameters();

        foreach ($depNames as $i => $depName) {
            if ($this->validations->offsetGet($depName) && $this->data->offsetExists($depName)) {
                $depVals[] = $this->data->offsetGet($depName);
            } elseif ($this->validations->offsetGet($depName) && $params[$i]->isDefaultValueAvailable()) {
                $depVals[] = $params[$i]->getDefaultValue();
            } else {
                return $this->resolveError();
            }
        }

        return call_user_func_array($resolver, $depVals);
    }

    private function resolveBindName(string $name)
    {
        while ($boundKeys = $this->getBindKeysInName($name)) {
            $key = $boundKeys[0];
            $keySegs = explode('.', $key);
            $mainKey = $keySegs[0];
            $bindNames = new \ArrayObject(array_merge(
                $this->getAllBindNames()->getArrayCopy(),
                $this->names->getArrayCopy(),
            ));

            if ($bindNames->offsetExists($mainKey)) {
                $bindName = $bindNames->offsetGet($mainKey);
            } else {
                throw new \Exception('"'.$mainKey.'" name not exists in '.static::class);
            }

            $pattern = '/\{\{(\s*)'.$key.'(\s*)\}\}/';
            $replace = $this->resolveBindName($bindName);
            $name = preg_replace($pattern, $replace, $name);
            $matches = [];
            preg_match_all('/\[\.\.\.\]/', $name, $matches);
            if (count($matches) > 1) {
                throw new \Exception($name.' has multiple "[...]" string in '.static::class);
            }
            if ($this->hasArrayObjectRuleInRuleLists($mainKey) && empty($matches)) {
                throw new \Exception('"'.$mainKey.'" name is required "[...]" string in '.static::class);
            }

            if (count($keySegs) > 1) {
                $replace = '['.implode('][', array_slice($keySegs, 1)).']';
                $name = preg_replace('/\[\.\.\.\]/', $replace, $name);
            }
        }

        return $name;
    }

    private function resolveError()
    {
        return new \Error('can\'t be resolve');
    }

    private function runAllDeferCallbacks()
    {
        $callbacks = array_filter($this->getAllCallbacks()->getArrayCopy(), function ($value) {
            return preg_match('/:defer$/', $value);
        }, ARRAY_FILTER_USE_KEY);

        foreach ($callbacks as $callback) {
            $this->resolve($callback);
        }

        foreach ($this->childs as $child) {
            $child->runAllDeferCallbacks();
        }
    }

    private function validate($key, $depth = '')
    {
        $depth = $depth ? $depth.'|'.$key : $key;
        $depths = explode('|', $depth);
        $mainKey = explode('.', $key)[0];

        if ($this->validations->offsetExists($key)) {
            return $this->validations->offsetGet($key);
        }

        if (count(array_filter($depths, function ($seg) use ($key) {
            return $seg == $key;
        })) >= 2) {
            throw new \Exception('validation dependency circular reference['.$depth.'] occurred in '.static::class);
        }

        $keySegs = explode('.', $key);
        for ($i = 0; $i < count($keySegs) - 1; ++$i) {
            $parentKey = implode('.', array_slice($keySegs, 0, $i + 1));
            if ($this->validations->offsetExists($parentKey) && true === $this->validations->offsetGet($parentKey)) {
                $this->validations->offsetSet($key, true);

                return true;
            }
        }

        $promiseList = $this->getAllPromiseLists()->offsetExists($mainKey) ? $this->getAllPromiseLists()->offsetGet($mainKey) : [];

        foreach ($promiseList as $promise) {
            if (!$this->validate($promise, $depth)) {
                $this->validations->offsetSet($mainKey, false);

                return false;
            }
        }

        $loader = $this->getAllLoaders()->offsetExists($mainKey) ? $this->getAllLoaders()->offsetGet($mainKey) : null;
        $deps = $loader ? $this->getClosureDependencies($loader) : [];

        foreach ($deps as $dep) {
            if (!$this->validate($dep, $depth)) {
                $this->validations->offsetSet($mainKey, false);
            }
        }

        $data = $this->getLoadedDataWith($mainKey);
        $items = json_decode(json_encode($data), true);

        $this->validateWith($key, $items, $depth);

        // unnecessary because data is stored already.
        if ($data->offsetExists($key)) {
            $this->data->offsetSet($key, $data->offsetGet($key));
        }

        $orderedCallbackKeys = $this->getOrderedCallbackKeys($key);

        foreach ($orderedCallbackKeys as $callbackKey) {
            $callback = $this->getAllCallbacks()->offsetGet($callbackKey);
            $deps = $this->getClosureDependencies($callback);

            foreach ($deps as $dep) {
                if (!$this->validate($dep, $depth)) {
                    $this->validations->offsetSet($key, false);
                }
            }

            if (!preg_match('/@defer$/', $callbackKey)) {
                $this->resolve($callback);
            }
        }

        if (false === $this->validations->offsetGet($key)) {
            return false;
        }

        return true;
    }

    private function validateWith($key, $items, $depth)
    {
        $mainKey = explode('.', $key)[0];

        foreach ([...static::getAllTraits(), static::class] as $cls) {
            $names = [];
            $ruleLists = $this->getRelatedRuleLists($key, $cls);
            $ruleLists = $this->filterAvailableExpandedRuleLists($cls, $key, $items, $ruleLists);

            if (!empty($ruleLists)) {
                $names[$mainKey] = $this->resolveBindName('{{'.$mainKey.'}}');
            }

            foreach ($ruleLists as $k => $ruleList) {
                foreach ($ruleList as $j => $rule) {
                    $depKeysInRule = $cls::getDependencyKeysInRule($rule);
                    foreach ($depKeysInRule as $depKey) {
                        if (preg_match('/\.\*/', $depKey)) {
                            throw new \Exception('wildcard(*) key can\'t exists in rule dependency in '.$cls);
                        }

                        $depKeySegs = explode('.', $depKey);
                        $depVal = (array) $items;
                        $hasDepVal = true;
                        while (!empty($depKeySegs)) {
                            $seg = array_shift($depKeySegs);
                            if (!array_key_exists($seg, $depVal)) {
                                $hasDepVal = false;

                                break;
                            }
                            $depVal = $depVal[$seg];
                        }

                        if (!$hasDepVal) {
                            unset($ruleLists[$k][$j]);
                        }

                        if (!$this->validate($depKey, $depth)) {
                            $this->validations->offsetSet($key, false);
                            unset($ruleLists[$k][$j]);
                        }
                        $names[$depKey] = $this->resolveBindName('{{'.$depKey.'}}');
                    }
                }
            }

            foreach ($ruleLists as $k => $ruleList) {
                $names[$k] = $this->resolveBindName('{{'.$k.'}}');
            }

            $messages = $cls::getValidationErrorTemplateMessages();

            foreach ($ruleLists as $ruleKey => $ruleList) {
                $errorLists = $cls::getValidationErrors(
                    $items,
                    [$ruleKey => $ruleList],
                    $names,
                    $messages,
                );

                if (!empty($errorLists)) {
                    if (!$this->errors->offsetExists($ruleKey)) {
                        $this->errors->offsetSet($ruleKey, []);
                    }
                    foreach ($errorLists[$ruleKey] as $error) {
                        $errors = $this->errors->offsetGet($ruleKey);
                        if (!in_array($error, $errors)) {
                            array_push($errors, $error);
                            $this->errors->offsetSet($ruleKey, $errors);
                        }
                    }
                    $this->validations->offsetSet($key, false);

                    return false;
                }
            }
        }

        if ($this->validations->offsetExists($key) && false === $this->validations->offsetGet($key)) {
            return false;
        }

        $this->validations->offsetSet($key, true);

        return true;
    }
}
