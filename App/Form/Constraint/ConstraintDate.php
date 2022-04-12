<?php

namespace App\Form\Constraint;

class ConstraintDate extends Constraint
{
    public function __construct($errorMsg = 'Must be a valid date (DD-MM-YYYY)')
    {
        parent::__construct('',$errorMsg);
    }

    public function isValid($value, $lastValue = null)
    {
        if ($value==='') return true;
        if (!preg_match('/^(19|20)\d\d[\- \/\.](0[1-9]|1[012])[\- \/\.](0[1-9]|[12][0-9]|3[01])$/',$value)) {
			return FALSE;
		} else {
			return checkdate(intval(substr($value,5,2)),intval(substr($value,8,2)),intval(substr($value,0,4)));
		}
    }

}