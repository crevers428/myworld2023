<?php
/**
 * User: luis
 * Date: 6/8/13
 * Time: 8:38 PM
 */
namespace App\Form\Constraint;

class Constraint extends AbstractConstraint
{
    protected $pattern;

    public function __construct($pattern,$errorMsg,$args = null)
    {
        $this->pattern = $pattern;
        if ($args) {
            $arguments[] = $errorMsg;
            $arguments = array_merge($arguments,$args);
            $this->errorMsg = call_user_func_array('sprintf',$arguments);
        } else {
            $this->errorMsg = $errorMsg;
        }
    }

    public function isValid($value, $lastValue = null)
    {
        return preg_match($this->pattern,$value);
    }

}