<?php

namespace FunctionalCoding\Validation;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;

class Validator extends \Illuminate\Validation\Validator
{
    public function __construct($translator, array $data, array $rules, array $messages = [], array $customAttributes = [])
    {
        parent::__construct(
            $translator,
            $data,
            $rules,
            $messages,
            $customAttributes
        );

        $this->implicitRules = array_merge($this->implicitRules, [
            // ... add custom rules
        ]);
        $this->dependentRules = array_merge($this->dependentRules, [
            // ... add custom rules
        ]);
    }

    public static function newInstance($locale, $data, $ruleLists, $names)
    {
        $messages = include __DIR__.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.$locale.'.php';

        $validator = new static(
            new Translator(new ArrayLoader(), 'en'),
            $data,
            $ruleLists,
            $messages,
            $names,
        );

        return $validator;
    }

    public function validateBase64($attribute, $value, $parameters, $validator)
    {
        if (base64_encode(base64_decode($value, true)) === $value) {
            return true;
        }

        return false;
    }

    public function validateBase64Image($attribute, $value, $parameters, $validator)
    {
        $matches = [];
        if (!preg_match('/^data\:(.*)\;base64\,(.*)$/', $value, $matches)) {
            return false;
        }

        if (!in_array($matches[1], ['image/png', 'image/jpeg', 'image/gif', 'image/webp', 'image/svg+xml'])) {
            return false;
        }

        $image = base64_decode($matches[2]);
        $f = finfo_open();
        $mime = finfo_buffer($f, $image, FILEINFO_MIME_TYPE);
        finfo_close($f);

        if ($mime != $matches[1]) {
            return false;
        }

        return true;
    }

    public function validateIntegers($attribute, $value)
    {
        if (!$this->validateString(null, $value) && !$this->validateInteger(null, $value)) {
            return false;
        }

        if (empty($value)) {
            return false;
        }

        $integers = preg_split('/\s*,\s*/', $value);

        foreach ($integers as $integer) {
            if (!$this->validateInteger(null, $integer)) {
                return false;
            }
        }

        return true;
    }

    public function validateNotNull($attribute, $value)
    {
        return !$this->validateNull($attribute, $value);
    }

    public function validateNull($attribute, $value)
    {
        if (is_null($value)) {
            return true;
        }

        return false;
    }

    public function validateSomeOfArray($attribute, $value, $parameters, $validator)
    {
        $this->requireParameterCount(1, $parameters, 'some_of_array');

        if (!is_array($value)) {
            $value = preg_split('/\s*,\s*/', $value);
        }
        $options = $this->getValue($parameters[0]);

        return count($value) == count(array_intersect($value, $options));
    }

    protected function replaceSomeOfArray($message, $attribute, $rule, $parameters)
    {
        return str_replace(':list', $this->getDisplayableAttribute($parameters[0]), $message);
    }
}
