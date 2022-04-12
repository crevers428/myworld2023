<?php

namespace App\Form\Constraint;

class ConstraintLengthMax extends Constraint
{
    public function __construct($max, $errorMsg = 'Must have %d characters maximum')
    {
        parent::__construct(sprintf('/^.{0,%d}$/',$max),$errorMsg,array($max));
    }
}