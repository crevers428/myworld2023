<?php
/**
 * User: luis
 * Date: 6/8/13
 * Time: 11:36 PM
 */
namespace App\Form\Constraint;

class ConstraintLength extends Constraint
{
    public function __construct($min,$max, $errorMsg = 'Must have more than %d and less than %d characters')
    {
        parent::__construct(sprintf('/^.{%d,%d}$/',$min,$max),$errorMsg,array($min,$max));
    }
}