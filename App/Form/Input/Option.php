<?php
/**
 * User: luis
 * Date: 6/13/13
 * Time: 11:57 AM
 */
namespace App\Form\Input;

class Option
{
    protected $value;
    protected $inner;

    public function __construct($value,$inner)
    {
        $this->value = $value;
        $this->inner = $inner;
    }

    public function renderView($selected = null)
    {
        $layout = sprintf('<option value="%s"',$this->value);
        if ($selected) {
            $layout .= ' selected';
        }
        $layout .= sprintf('>%s</option>',$this->inner) . PHP_EOL;
        return $layout;
    }

    public function getValue()
    {
        return $this->value;
    }
}