<?php

namespace App\Form\Input;

class InputHidden extends Input
{
    public function __construct($name)
    {
        parent::__construct($name);
        $this->setType("hidden");
    }
}