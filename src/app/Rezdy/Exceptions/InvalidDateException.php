<?php

namespace RezdyAPI\Exceptions;

use Exception;
use InvalidArgumentException;

class InvalidDateException extends InvalidArgumentException
{

    private $field;

    private $value;

    public function __construct($field, $value, $code = 0, Exception $previous = null)
    {
        $this->field = $field;
        $this->value = $value;
        parent::__construct($field.' : '.$value.' is not a valid value.', $code, $previous);
    }

    public function getField()
    {
        return $this->field;
    }

    public function getValue()
    {
        return $this->value;
    }
}