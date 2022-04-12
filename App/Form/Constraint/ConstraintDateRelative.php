<?php

namespace App\Form\Constraint;

use DateTime;

class ConstraintDateRelative extends Constraint
{
    protected $min;
    protected $max;

    public function __construct($min,$max, $errorMsg = 'Must be between %d and %d days relative to the above date')
    {
        parent::__construct('',$errorMsg,array($min,$max));
        $this->min = $min;
        $this->max = $max;
    }

    public function isValid($value, $lastValue = null)
    {
        $datetime1 = new DateTime($lastValue);
        $datetime2 = new DateTime($value);
        $diff = $datetime1->diff($datetime2);
        //echo "$lastValue, $value, {$diff->days}";
        $days = $diff->days;
        if ($diff->invert) $days = -$days;
        return ($days >= $this->min && $days <= $this->max);
    }
}