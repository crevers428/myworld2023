<?php
/**
 * User: luis
 * Date: 6/8/13
 * Time: 11:36 PM
 */
namespace App\Form\Constraint;

class ConstraintSame extends Constraint
{
    public function __construct($errorMsg = 'Values must be identical')
    {
        parent::__construct('',$errorMsg);
    }

    public function isValid($value, $lastValue = null)
    {
        return ($value == $lastValue);
    }

}