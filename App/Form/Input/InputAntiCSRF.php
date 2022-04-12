<?php

namespace App\Form\Input;

use App\Application;
use App\Form\SecureForm;

class InputAntiCSRF extends InputHidden
{
    protected $token;

    /*
     * These functions are called in this order:
     * 1. setValue()
     * 2. isValid()
     * 3. getValue() - not just once
     */

    public function isValid($lastValue)
    {
        $key = $this->name . SecureForm::ANTI_CSRF_SUFFIX;
        if (array_key_exists($key,$_SESSION)) {
            $match = ($this->value == $_SESSION[$key]);
            unset($_SESSION[$key]);
            return $match;
        } else {
            Application::addPostIt(Application::POSTIT_ERROR,'La página ha detectado CSRF en el formulario y no lo procesará');
            return false;
        }
    }

    public function getValue()
    {
        if (!$this->token) {
            $this->token = sha1(microtime());
            $_SESSION[$this->name . SecureForm::ANTI_CSRF_SUFFIX] = $this->token;
        }
        return $this->token;
    }
}