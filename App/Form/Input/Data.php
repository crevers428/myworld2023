<?php
/**
 * User: luis
 * Date: 6/13/13
 * Time: 11:57 AM
 */
namespace App\Form\Input;

class Data
{
    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function renderView()
    {
        return sprintf('<option>%s</option>',$this->value);
    }

    public function getValue()
    {
        return $this->value;
    }
}