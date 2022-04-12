<?php
/**
 * User: luis
 * Date: 6/8/13
 * Time: 8:38 PM
 */
namespace App\Form\Constraint;

abstract class AbstractConstraint
{
    protected $errorMsg;

    public abstract function isValid($value, $lastValue = null);

    public function getErrorMsg()
    {
        return $this->errorMsg;
    }
}