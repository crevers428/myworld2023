<?php
/**
 * User: luis
 * Date: 6/8/13
 * Time: 11:36 PM
 */
namespace App\Form\Constraint;

class ConstraintAlphanumericUTF8 extends Constraint
{
    public function __construct($errorMsg = 'Use only letters and numbers')
    {
        parent::__construct('/^[\pL0-9]*$/u',$errorMsg);
    }
}