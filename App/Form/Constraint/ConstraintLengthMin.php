<?php
/**
 * User: luis
 * Date: 6/8/13
 * Time: 11:36 PM
 */
namespace App\Form\Constraint;

class ConstraintLengthMin extends Constraint
{
    public function __construct($min, $errorMsg = 'Must have %d characters minimum')
    {
        parent::__construct(sprintf('/^.{%d,}$/',$min),$errorMsg,array($min));
    }
}