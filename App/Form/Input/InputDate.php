<?php

namespace App\Form\Input;

use App\Application;

class InputDate extends Input
{
    public function __construct($name)
    {
        parent::__construct($name);
        $this->setType("date");
    }

    static public function dateStrIn($dateStr)
    {
        if (!Application::agentIsChrome() && preg_match('/^[0-9]{4,4}-[0-9]{2,2}-[0-9]{2,2}$/',$dateStr)) {
            $dateStr = substr($dateStr,8,2) . '-' . substr($dateStr,5,2) . '-' . substr($dateStr,0,4);
        }
        return $dateStr;
    }

    static public function dateStrOut($dateStr)
    {
        if (!Application::agentIsChrome() && preg_match('/^[0-9]{2,2}-[0-9]{2,2}-[0-9]{4,4}$/',$dateStr)) {
            $dateStr = substr($dateStr,6,4) . '-' . substr($dateStr,3,2) . '-' . substr($dateStr,0,2);
        }
        return $dateStr;
    }

    public function setValue($value)
    {
        return parent::setValue(InputDate::dateStrIn($value));
    }

    public function getValue()
    {
        return InputDate::dateStrOut(parent::getValue());
    }

    public function getInFormValue()
    {
        return $this->value;
    }

    public function getRawValue()
    {
        return InputDate::dateStrOut(parent::getRawValue());
    }

}