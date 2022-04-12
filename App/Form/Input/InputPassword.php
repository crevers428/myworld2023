<?php
/**
 * User: luis
 * Date: 6/8/13
 * Time: 5:30 PM
 */
namespace App\Form\Input;

class InputPassword extends Input
{
    public function __construct($name)
    {
        parent::__construct($name);
        $this->setType("password");
    }
}