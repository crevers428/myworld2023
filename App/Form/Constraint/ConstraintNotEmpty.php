<?php

namespace App\Form\Constraint;

class ConstraintNotEmpty extends Constraint
{
    public function __construct($errorMsg = 'Can\'t be blank')
    {
        parent::__construct(null,$errorMsg);
    }

    public function isValid($value, $lastValue = null)
    {
        //return preg_match('/^(.|\n)+$/',$value) && !preg_match('/^(\s)*$/',$value);
        return trim($value);
    }
}