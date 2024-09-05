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

    abstract public static function getLocale();

    abstract public static function getValidationErrors($locale, $data, $ruleLists, $names);

    abstract public static function hasArrayObjectRuleInRuleList($ruleList);

    abstract public static function removeDependencyKeySymbolInRule($rule);

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
            $arr = array_merge($arr, $cls::getBindNames());
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
            if (!$cls instanceof self) {
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
        return is_array($value) && array_key_exists(0, $value) && is_string($value[0]) && is_a($value[0], self::class, true);
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

    protected function filterAvailableExpandedRuleLists($cls, $key, $data, $ruleLists)
    {
        foreach (array_keys($ruleLists) as $k) {
            $segs = explode('.', $k);
            for ($i = 0; $i < count($segs) - 1; ++$i) {
                $hasArrayObjectRule = false;
                $parentKey = implode('.', array_slice($segs, 0, $i + 1));
                foreach (array_keys((array) $this->getAllRuleLists()) as $cls) {
                    $parentRuleLists = $this->getAllRuleLists()[$cls];
                    $parentRuleList = array_key_exists($parentKey, $parentRuleLists) ? $parentRuleLists[$parentKey] : [];
                    if ($cls::hasArrayObjectRuleInRuleList($parentRuleList)) {
                        $hasArrayObjectRule = true;
                    }
                }

                if (!$hasArrayObjectRule) {
                    throw new \Exception($parentKey.' key must has array rule in '.static::class);
                }
            }
        }

        $i = 0;
        while (!empty($filteredRuleLists = array_filter($ruleLists, function ($k) {
            return preg_match('/\.\*$/', $k) || preg_match('/\.\*\./', $k);
        }, ARRAY_FILTER_USE_KEY))) {
            ++$i;
            foreach (array_keys($filteredRuleLists) as $rKey) {
                $matches = [];
                preg_match('/^(.+?)\.\*/', $rKey, $matches);
                $matches[1] = $matches[1].'.*';
                $allSegs = explode('.', $matches[1]);
                $segs = [];
                $rKeyVal = $data;
                $isSuccess = true;

                while ($allSegs) {
                    $seg = array_shift($allSegs);
                    $segs[] = $seg;
                    $k = implode('.', $segs);

                    if (!is_array($rKeyVal) || (!empty($allSegs) && !array_key_exists($seg, $rKeyVal))) {
                        $isSuccess = false;

                        break;
                    }

                    if (!empty($allSegs)) {
                        $rKeyVal = $rKeyVal[$seg];
                    }
                }

                if ($isSuccess) {
                    foreach ($rKeyVal as $k => $v) {
                        $rNewKey = preg_replace('/^'.$allSegs.'\.\*/', $allSegs.'.'.$k, $rKey);
                        $ruleLists[$rNewKey] = $ruleLists[$rKey];
                        $this->names->offsetSet(
                            $rNewKey,
                            preg_replace('{{'.$i.'}}', $k, $this->resolveBindName('{{'.$rKey.'}}'))
                        );
                    }
                    unset($ruleLists[$rKey]);
                    $this->names->offsetUnset($rKey);
                }
            }
        }

        foreach (array_keys($ruleLists) as $rKey) {
            $allSegs = explode('.', $rKey);
            $segs = [];
            $rKeyVal = $data;
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
                    $this->validations->offsetSet($key, false);
                    $removeRuleLists = array_filter($ruleLists, function ($v) use ($k) {
                        return preg_match('/^'.$k.'\./', $v);
                    }, ARRAY_FILTER_USE_KEY);
                    foreach (array_keys($removeRuleLists) as $v) {
                        unset($ruleLists[$v]);
                    }
                    $removeNames = array_filter((array) $this->names, function ($v) use ($k) {
                        return preg_match('/^'.$k.'\./', $v);
                    }, ARRAY_FILTER_USE_KEY);
                    foreach (array_keys($removeNames) as $v) {
                        $this->names->offsetUnset($v);
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

    protected function getBindKeysInName(string $str)
    {
        $matches = [];

        preg_match_all(static::BIND_NAME_EXP, $str, $matches);

        return $matches[1];
    }

    protected function getClosureDependencies(\Closure $func)
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

    protected function getLoadedDataWith($key)
    {
        $data = $this->getData();
        $loader = $this->getAllLoaders()->offsetExists($key) ? $this->getAllLoaders()->offsetGet($key) : null;

        if ($data->offsetExists($key)) {
            return $data;
        }

        if ($this->getInputs()->offsetExists($key)) {
            $value = $this->getInputs()->offsetGet($key);
            $loader = function () use ($value) {
                return $value;
            };
        }

        if (empty($loader)) {
            return $data;
        }

        $value = $this->resolve($loader);

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

    protected function getOrderedCallbackKeys($key)
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

    protected function getRelatedRuleLists($key, $cls)
    {
        $ruleLists = $this->getAllRuleLists()->offsetExists($cls) ? $this->getAllRuleLists()->getArrayCopy()[$cls] : [];

        return array_filter($ruleLists, function ($k) use ($key) {
            return preg_match('/^'.$key.'$/', $k) || preg_match('/^'.$key.'\./', $k);
        }, ARRAY_FILTER_USE_KEY);
    }

    protected function getShouldOrderedCallbackKeys($keys)
    {
        $arr = [];

        foreach ($keys as $key) {
            $deps = $this->getAllPromiseLists()->offsetExists($key) ? $this->getAllPromiseLists()->offsetGet($key) : [];
            $list = $this->getShouldOrderedCallbackKeys($deps);
            $arr = [...$list, $key, ...$arr];
        }

        return array_unique(array_values($arr));
    }

    protected function isResolveError($value)
    {
        $errorClass = get_class($this->resolveError());

        return is_object($value) && $value instanceof $errorClass;
    }

    protected function resolve($func)
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

    protected function resolveBindName(string $name)
    {
        while ($boundKeys = $this->getBindKeysInName($name)) {
            $key = $boundKeys[0];
            $pattern = '/\{\{'.$key.'\}\}/';
            $bindNames = new \ArrayObject(array_merge(
                $this->getAllBindNames()->getArrayCopy(),
                $this->names->getArrayCopy(),
            ));

            if ($bindNames->offsetExists($key)) {
                $bindName = $bindNames->offsetGet($key);
            } else {
                throw new \Exception('"'.$key.'" name not exists in '.static::class);
            }

            $replace = $this->resolveBindName($bindName);
            $name = preg_replace($pattern, $replace, $name, 1);
        }

        return $name;
    }

    protected function resolveError()
    {
        return new \Error('can\'t be resolve');
    }

    protected function runAllDeferCallbacks()
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

    protected function validate($key, $depth = '')
    {
        $depth = $depth ? $depth.'|'.$key : $key;
        $depths = explode('|', $depth);
        $mainKey = explode('.', $key)[0];

        if (count(array_filter($depths, function ($seg) use ($key) {
            return $seg == $key;
        })) >= 2) {
            throw new \Exception('validation dependency circular reference['.$depth.'] occurred in '.static::class);
        }

        if ($this->validations->offsetExists($key)) {
            return $this->validations->offsetGet($key);
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

    protected function validateWith($key, $items, $depth)
    {
        foreach ([...static::getAllTraits(), static::class] as $cls) {
            $ruleLists = $this->getRelatedRuleLists($key, $cls);
            $allDepKeysInRule = [];
            $notMustPresentDepKeysInRule = [];
            $mustPresentDepKeysInRule = [];

            foreach ($ruleLists as $k => $ruleList) {
                foreach ($ruleList as $i => $rule) {
                    $presentRelatedRule = $cls::filterPresentRelatedRule($rule);
                    if ($presentRelatedRule) {
                        $notMustPresentDepKeysInRule = array_merge(
                            $notMustPresentDepKeysInRule,
                            $cls::getDependencyKeysInRule($presentRelatedRule),
                        );
                    }
                    $allDepKeysInRule = array_merge(
                        $allDepKeysInRule,
                        $cls::getDependencyKeysInRule($rule),
                    );
                }
            }
            $mustPresentDepKeysInRule = array_diff($allDepKeysInRule, $notMustPresentDepKeysInRule);

            foreach (array_unique($allDepKeysInRule) as $k) {
                if (preg_match('/\.\*/', $k)) {
                    throw new \Exception('wildcard(*) key can\'t exists in rule dependency in '.static::class);
                }
                if (!$this->validate($k, $depth)) {
                    $this->validations->offsetSet($key, false);
                }
            }

            foreach (array_unique($mustPresentDepKeysInRule) as $k) {
                if (!$this->getData()->offsetExists($k)) {
                    throw new \Exception('"'.$k.'" key required rule not exists in '.static::class);
                }
            }

            foreach ($ruleLists as $k => $ruleList) {
                foreach ($ruleList as $j => $rule) {
                    $ruleLists[$k][$j] = $cls::removeDependencyKeySymbolInRule($rule);
                }
            }

            $ruleLists = $this->filterAvailableExpandedRuleLists($cls, $key, $items, $ruleLists);
            $locale = $cls::getLocale();
            $names = [];

            foreach ($this->names as $k => $v) {
                $names[$k] = $this->resolveBindName($v);
            }

            foreach ($ruleLists as $ruleKey => $ruleList) {
                $errorLists = $cls::getValidationErrors(
                    $locale,
                    $items,
                    [$ruleKey => $ruleList],
                    $names,
                );

                if (!empty($errorLists)) {
                    if (!$this->errors->offsetExists($ruleKey)) {
                        $this->errors->offsetSet($ruleKey, []);
                    }
                    $this->errors->offsetSet($ruleKey, array_merge(
                        $this->errors->offsetGet($ruleKey),
                        $errorLists[$ruleKey],
                    ));
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
