<?php

namespace App\Form\Input;

use App\Application;

class InputAntiRobots extends InputHidden
{
    public function isValid($lastValue)
    {
        if ($this->value != '') {
            Application::addPostIt(Application::POSTIT_ERROR,'La página ha detectado que el formulario ha sido enviado por un robot y no lo procesará');
            return false;
        }
        return true;
    }
}