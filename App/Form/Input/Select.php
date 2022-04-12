<?php
/**
 * User: luis
 * Date: 6/7/13
 * Time: 2:27 PM
 */
namespace App\Form\Input;

class Select extends BAbstractInput
{
    protected $options = array();

    public function addOption($value,$inner)
    {
        $option = new Option($value,$inner);
        $this->options[] = $option;
    }

    public function renderView()
    {
        $layout = sprintf('<select name="%s"', $this->getName());
        $layout .= $this->renderAttr($this->getId(),'id="%s"');
        $layout .= $this->renderAttr($this->getRequired(),'required');
        $layout .= $this->renderAttr($this->getClass(),'class="%s"');
        $layout .= $this->renderAttr($this->getReadonly(),'readonly');
        $layout .= $this->renderAttr($this->getDisabled(),'disabled');
        /* @var InputEvent $event */
        foreach ($this->events as $event) {
            $layout .= $event->renderView();
        }
        $layout .= '>' . PHP_EOL;
        /* @var Option $option */
        foreach ($this->options as $option) {
            $layout .= '    ' . $option->renderView($this->value == $option->getValue());
        }
        $layout .= '</select>' . PHP_EOL;
        return $layout;
    }

}