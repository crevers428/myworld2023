<?php
/**
 * User: luis
 * Date: 6/13/13
 * Time: 1:53 PM
 */
namespace App\Form\Input;

class InputEvent
{
    protected $eventName;
    protected $script;

    public function __construct($onEvent,$script)
    {
        $this->eventName = $onEvent;
        $this->script = $script;
    }

    public function renderView()
    {
        return sprintf(' on%s="%s"',$this->eventName,$this->script);
    }
}