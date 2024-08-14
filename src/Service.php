<?php

namespace FunctionalCoding;

use FunctionalCoding\Validation\Validator;

class Service
{
    public const BIND_NAME_EXP = '/\{\{([a-z0-9\_\.\*]+)\}\}/';
    protected \ArrayObject $childs;
    protected \ArrayObject $data;
    protected \ArrayObject $errors;
    protected \ArrayObject $inputs;
    protected bool $isResponsed;
    protected bool $isRun;
    protected \ArrayObject $names;
    protected \ArrayObject $validations;
    private static \Closure $localeResolver;
    private static array $onBeforeRunCallbacks = [];
    private static array $onFailCallbacks = [];
    private static array $onSuccessCallbacks = [];
    private static \Closure $responseResolver;
    private static \Closure $validationErrorListResolver;

    public function __construct(array $inputs = [], array $names = [])
    {
        $this->childs = new \ArrayObject();
        $this->data = new \ArrayObject();
        $this->errors = new \ArrayObject();
        $this->inputs = new \ArrayObject($inputs);
        $this->names = new \ArrayObject($names);
        $this->validations = new \ArrayObject();
        $this->isRun = false;
        $this->isResponsed = false;

        foreach ($this->inputs as $key => $value) {
            $this->validate($key);
        }
    }

    public static function addOnBeforeRunCallback(\Closure $callback)
    {
        static::$onBeforeRunCallbacks[] = $callback;
    }

    public static function addOnFailCallback(\Closure $callback)
    {
        static::$onFailCallbacks[] = $callback;
    }

    public static function addOnSuccessCallback(\Closure $callback)
    {
        static::$onSuccessCallbacks[] = $callback;
    }

    public static function getAllBindNames()
    {
        $arr = [];

        foreach ([...static::getAllTraits(), static::class] as $class) {
            $arr = array_merge($arr, $class::getBindNames());
        }

        return new \ArrayObject($arr);
    }

    public static function getAllCallbacks()
    {
        $arr = [];

        foreach ([...static::getAllTraits(), static::class] as $class) {
            $arr = array_merge($arr, $class::getCallbacks());
        }

        return new \ArrayObject($arr);
    }

    public static function getAllLoaders()
    {
        $arr = [];

        foreach ([...static::getAllTraits(), static::class] as $class) {
            $arr = array_merge($arr, $class::getLoaders());
        }

        return new \ArrayObject($arr);
    }

    public static function getAllPromiseLists()
    {
        $arr = [];

        foreach ([...static::getAllTraits(), static::class] as $class) {
            $arr = array_merge_recursive($arr, $class::getPromiseLists());
        }

        return new \ArrayObject($arr);
    }

    public static function getAllRuleLists()
    {
        $arr = [];

        foreach ([...static::getAllTraits(), static::class] as $class) {
            foreach ($class::getRuleLists() as $key => $ruleList) {
                foreach ($ruleList as $rule) {
                    if (!array_key_exists($key, $arr)) {
                        $arr[$key] = [];
                    }
                    if (!in_array($rule, $arr[$key])) {
                        // throw new \Exception('duplicated rule exist in same key in '.static::class);
                        array_push($arr[$key], $rule);
                    }
                }
            }
        }

        return new \ArrayObject($arr);
    }

    public static function getAllTraits()
    {
        $arr = [];

        foreach (static::getTraits() as $class) {
            $arr = array_merge($arr, $class::getAllTraits()->getArrayCopy());
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

    public function getChilds()
    {
        return $this->childs;
    }

    public function getData()
    {
        $data = clone $this->data;

        $data->ksort();

        return $data;
    }

    public function getErrors()
    {
        return clone $this->errors;
    }

    public static function getLoaders()
    {
        return [];
    }

    public function getNames()
    {
        $data = clone $this->names;

        $data->ksort();

        return $data;
    }

    public static function getPromiseLists()
    {
        return [];
    }

    public function getResponseBody()
    {
        $totalErrors = $this->getTotalErrors();
        $errors = [];

        array_walk_recursive($totalErrors, function ($value) use (&$errors) {
            $errors[] = $value;
        });

        $result = $this->getData()->offsetExists('result') ? $this->getData()->offsetGet('result') : null;

        if (!empty(static::$responseResolver)) {
            return (static::$responseResolver)($result, $errors);
        }

        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        return [
            'result' => $result,
        ];
    }

    public static function getRuleLists()
    {
        return [];
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

    public static function getTraits()
    {
        return [];
    }

    public function getValidates()
    {
        $arr = $this->validations->getArrayCopy();

        ksort($arr);

        return new \ArrayObject($arr);
    }

    public static function initService($value)
    {
        isset($value[1]) ?: $value[1] = [];
        isset($value[2]) ?: $value[2] = [];

        $class = $value[0];
        $data = $value[1];
        $names = $value[2];

        foreach ($data as $key => $value) {
            if ('' === $value) {
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

    public function run()
    {
        if (!$this->isRun) {
            foreach (array_keys((array) $this->inputs()) as $key) {
                $this->validate($key);
            }

            foreach (array_keys((array) $this->getAllRuleLists()) as $key) {
                $this->validate($key);
            }

            foreach (array_keys((array) $this->getAllLoaders()) as $key) {
                $this->validate($key);
            }
            $this->isRun = true;
        }

        if (empty($this->getTotalErrors()) && !$this->getData()->offsetExists('result')) {
            throw new \Exception('result data key is not exists in '.static::class);
        }

        if (!empty($this->getTotalErrors())) {
            return $this->resolveError();
        }

        return $this->getData()->offsetGet('result');
    }

    public function runWithLifeCycleCallbacks()
    {
        foreach (static::$onBeforeRunCallbacks as $callback) {
            $callback();
        }

        $result = $this->run();
        $errors = $this->getTotalErrors();

        if (empty($errors)) {
            $this->runDeferCallbacks();
            foreach (static::$onSuccessCallbacks as $callback) {
                $callback();
            }
        } else {
            foreach (static::$onFailCallbacks as $callback) {
                $callback();
            }
        }

        foreach ($this->childs as $child) {
            $child->runDeferCallbacks();
        }

        return $result;
    }

    public static function setLocaleResolver(\Closure $resolver)
    {
        static::$localeResolver = $resolver;
    }

    public static function setResponseResolver(\Closure $resolver)
    {
        static::$responseResolver = $resolver;
    }

    public static function setValidationErrorListResolver(\Closure $resolver)
    {
        static::$validationErrorListResolver = $resolver;
    }

    protected function getAvailableData($key)
    {
        $data = $this->getData();
        $loader = $this->getAllLoaders()->offsetExists($key) ? $this->getAllLoaders()->offsetGet($key) : null;

        if ($data->offsetExists($key)) {
            return $data;
        }

        if ($this->inputs()->offsetExists($key)) {
            $value = $this->inputs()->offsetGet($key);
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

        $hasServiceInArray = false;
        if (!empty($value) && is_array($value) && array_values($value) === $value) {
            foreach ($value as $v) {
                if (static::isInitable($v)) {
                    $hasServiceInArray = true;
                }
            }
        }
        $values = $hasServiceInArray ? $value : [$value];
        $hasError = false;

        foreach ($values as $i => $v) {
            if (static::isInitable($v)) {
                isset($v[1]) ?: $v[1] = [];
                isset($v[2]) ?: $v[2] = [];

                foreach ($v[2] as $k => $name) {
                    $v[2][$k] = $this->resolveBindName($name);
                }
                $service = static::initService($v);
                $resolved = $service->run();
            } elseif ($v instanceof self) {
                $service = $v;
                $resolved = $service->run();
            } else {
                $values[$i] = $v;

                break;
            }

            $this->childs->offsetSet($hasServiceInArray ? $key.'.'.$i : $key, $service);

            if ($this->isResolveError($resolved)) {
                unset($values[$i]);
                $hasError = true;

                $this->validations->offsetSet($key, false);
            }

            $values[$i] = $resolved;
        }

        if (!$hasError) {
            $data->offsetSet($key, $hasServiceInArray ? $values : $values[0]);
        }

        return $data;
    }

    protected function getAvailableRuleList($key, $data)
    {
        $ruleLists = [
            $key => $this->getAllRuleLists()->offsetExists($key) ? $this->getAllRuleLists()->offsetGet($key) : [],
        ];
        if (!$this->getAllLoaders()->offsetExists($key) && !$this->inputs->offsetExists($key)) {
            $ruleLists[$key] = array_filter($ruleLists[$key], function ($rule) {
                return $this->isRequiredRule($rule);
            });
        }

        if (!empty($ruleLists[$key])) {
            if ($data->offsetExists($key) && $this->getAllRuleLists()->offsetExists($key.'.*')) {
                $this->names->offsetSet($key.'.*', $this->resolveBindName('{{'.$key.'.*}}'));
                $ruleLists[$key.'.*'] = $this->getAllRuleLists()->offsetGet($key.'.*');
                $keyVal = $data[$key];
                if (!is_array($keyVal) && !($keyVal instanceof \ArrayAccess) && !in_array('array', $ruleLists[$key])) {
                    throw new \Exception($key.' key must has array rule');
                }

                foreach (array_keys($this->toArray($keyVal)) as $i) {
                    $ruleLists[$key.'.'.$i] = $ruleLists[$key.'.*'];
                    $this->names->offsetSet(
                        $key.'.'.$i,
                        str_replace('*', $i, $this->names->offsetGet($key.'.*'))
                    );
                }
            }

            if ($this->names->offsetExists($key.'.*')) {
                unset($ruleLists[$key.'.*']);
                $this->names->offsetUnset($key.'.*');
            }

            $this->names->offsetSet($key, $this->resolveBindName('{{'.$key.'}}'));
        }

        foreach ($ruleLists as $k => $ruleList) {
            foreach ($ruleList as $i => $rule) {
                $bindKeys = $this->getBindKeys($rule);

                foreach ($bindKeys as $bindKey) {
                    $this->names->offsetSet($bindKey, $this->resolveBindName('{{'.$bindKey.'}}'));

                    if (!$this->validate($bindKey)) {
                        $this->validations->offsetSet($key, false);
                        unset($ruleList[$i]);

                        continue;
                    }

                    if (!$this->isRequiredRule($rule) && !$this->getData()->offsetExists($bindKey)) {
                        throw new \Exception('"'.$bindKey.'" key required rule not exists in '.static::class);
                    }
                }

                $ruleList[$i] = preg_replace(static::BIND_NAME_EXP, '$1', $rule);
            }
            $ruleLists[$k] = $ruleList;

            if (empty($ruleLists[$k])) {
                unset($ruleLists[$k]);
            }
        }

        return $ruleLists;
    }

    protected function getBindKeys(string $str)
    {
        $matches = [];

        preg_match_all(static::BIND_NAME_EXP, $str, $matches);

        return $matches[1];
    }

    protected function getClosureDependencies(\Closure $func)
    {
        if (null == $func) {
            return [];
        }

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

    protected function getLocale()
    {
        if (!empty(static::$localeResolver)) {
            return (static::$localeResolver)();
        }

        return 'en';
    }

    protected function getOrderedCallbackKeys($key)
    {
        $promiseKeys = array_filter(array_keys($this->getAllPromiseLists()->getArrayCopy()), function ($value) use ($key) {
            return preg_match('/^'.$key.'\\./', $value);
        });
        $allKeys = array_filter(array_keys($this->getAllCallbacks()->getArrayCopy()), function ($value) use ($key) {
            return preg_match('/^'.$key.'\\./', $value);
        });
        $orderedKeys = $this->getShouldOrderedCallbackKeys($promiseKeys);
        $restKeys = array_diff($allKeys, $orderedKeys);

        return array_merge($orderedKeys, $restKeys);
    }

    protected function getShouldOrderedCallbackKeys($keys)
    {
        $arr = [];
        $rtn = [];

        foreach ($keys as $key) {
            $deps = $this->getAllPromiseLists()->offsetExists($key) ? $this->getAllPromiseLists()->offsetGet($key) : [];
            $list = $this->getShouldOrderedCallbackKeys($deps);
            $list = array_merge($list, [$key]);
            $arr = array_merge($list, $arr);
        }

        foreach ($arr as $value) {
            $rtn[$value] = null;
        }

        return array_keys($rtn);
    }

    protected function getValidationErrors($locale, $data, $ruleLists, $names)
    {
        if (!empty(static::$validationErrorListResolver)) {
            return (static::$validationErrorListResolver)($locale, $data, $ruleLists, $names);
        }

        $validator = Validator::newInstance($locale, $data, $ruleLists, $names);
        $validator->passes();

        return $validator->errors()->messages();
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

    protected function resolve($func)
    {
        $resolver = \Closure::bind($func, $this);
        $depNames = $this->getClosureDependencies($func);
        $depVals = [];
        $params = (new \ReflectionFunction($resolver))->getParameters();

        foreach ($depNames as $i => $depName) {
            if ($this->data->offsetExists($depName)) {
                $depVals[] = $this->data->offsetGet($depName);
            } elseif ($params[$i]->isDefaultValueAvailable()) {
                $depVals[] = $params[$i]->getDefaultValue();
            } else {
                // must not throw exception, but only return
                return $this->resolveError();
            }
        }

        return call_user_func_array($resolver, $depVals);
    }

    protected function resolveBindName(string $name)
    {
        while ($boundKeys = $this->getBindKeys($name)) {
            $key = $boundKeys[0];
            $pattern = '/\{\{'.$key.'\}\}/';
            $bindNames = new \ArrayObject(array_merge(
                $this->getAllBindNames()->getArrayCopy(),
                $this->names->getArrayCopy(),
            ));
            $bindName = $bindNames->offsetExists($key) ? $bindNames->offsetGet($key) : null;

            if (null == $bindName) {
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

    protected function runDeferCallbacks()
    {
        $callbacks = array_filter($this->getAllCallbacks()->getArrayCopy(), function ($value) {
            return preg_match('/:defer$/', $value);
        }, ARRAY_FILTER_USE_KEY);

        foreach ($callbacks as $callback) {
            $this->resolve($callback);
        }
    }

    protected function validate($key)
    {
        if (count(explode('.', $key)) > 1) {
            $this->validate(explode('.', $key)[0]);
        }

        if ($this->validations->offsetExists($key)) {
            return $this->validations->offsetGet($key);
        }

        $promiseList = $this->getAllPromiseLists()->offsetExists($key) ? $this->getAllPromiseLists()->offsetGet($key) : [];

        foreach ($promiseList as $promise) {
            $segs = explode(':', $promise);
            $promiseKey = $segs[0];
            $isStrict = isset($segs[1]) && 'strict' == $segs[1];

            // isStrict mode is deprecated
            // if (!$this->validate($promiseKey) && $isStrict) {
            if (!$this->validate($promiseKey)) {
                $this->validations->offsetSet($key, false);

                return false;
            }
        }

        $loader = $this->getAllLoaders()->offsetExists($key) ? $this->getAllLoaders()->offsetGet($key) : null;
        $deps = $loader ? $this->getClosureDependencies($loader) : [];

        foreach ($deps as $dep) {
            if (!$this->validate($dep)) {
                $this->validations->offsetSet($key, false);
            }
        }

        if ($this->validations->offsetExists($key) && false === $this->validations->offsetGet($key)) {
            return false;
        }

        $data = $this->getAvailableData($key);
        $ruleLists = $this->getAvailableRuleList($key, $data);

        foreach ($ruleLists as $ruleKey => $ruleList) {
            $k = explode('.', $ruleKey)[0];
            $locale = $this->getLocale();
            $items = array_merge(
                $this->data->getArrayCopy(),
                $data->getArrayCopy()
            );
            if (array_key_exists($k, $items) && !empty($items[$k])) {
                $item = $this->toArray($items[$k]);
                if (is_null($item)) {
                    throw new \Exception('data {'.$k.'} key can\'t convert primative value');
                }
                $items[$k] = $item;
            }

            $errors = $this->getValidationErrors(
                $locale,
                $items,
                [$ruleKey => $ruleList],
                $this->names->getArrayCopy()
            );

            if (!empty($errors)) {
                $this->validations->offsetSet($ruleKey, false);

                foreach ($errors as $messageList) {
                    $errors = $this->errors->offsetExists($ruleKey) ? $this->errors->offsetGet($ruleKey) : [];
                    $this->errors->offsetSet($ruleKey, array_merge($errors, $messageList));
                }
                $this->validations->offsetSet($key, false);

                return false;
            }
        }

        if ($this->validations->offsetExists($key) && false === $this->validations->offsetGet($key)) {
            return false;
        }

        if ($data->offsetExists($key)) {
            $this->data->offsetSet($key, $data->offsetGet($key));
        }

        $this->validations->offsetSet($key, true);

        $orderedCallbackKeys = $this->getOrderedCallbackKeys($key);

        foreach ($orderedCallbackKeys as $callbackKey) {
            $callback = $this->getAllCallbacks()->offsetGet($callbackKey);
            $deps = $this->getClosureDependencies($callback);

            foreach ($deps as $dep) {
                if (!$this->validate($dep)) {
                    $this->validations->offsetSet($key, false);
                }
            }

            if (!preg_match('/:defer$/', $callbackKey)) {
                $this->resolve($callback);
            }
        }

        if (false === $this->validations->offsetGet($key)) {
            return false;
        }

        return true;
    }

    private function toArray($value)
    {
        if (is_object($value) && method_exists($value, 'toArray')) {
            $result = $value->toArray();
        } else {
            $result = json_decode(json_encode($value), true);
        }

        return $result;
    }
}
