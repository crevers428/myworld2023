<?php
/**
 * User: luis
 * Date: 6/8/13
 * Time: 5:30 PM
 */
namespace App\Form\Input;

class InputCheckbox extends Input
{
    protected $checked;

    public function __construct($name)
    {
        parent::__construct($name);
        $this->setType("checkbox");
    }

    public function setChecked($checked)
    {
        $this->checked = $checked;
        return $this;
    }

    public function getChecked()
    {
        return $this->checked;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function renderView()
    {
        $layout = sprintf('<input type="checkbox" name="%s"', $this->getName());
        $layout .= $this->renderAttr($this->getId(),'id="%s"');
        $layout .= $this->renderAttr($this->getAutofocus(),'autofocus');
        $layout .= $this->renderAttr($this->getClass(),'class="%s"');
        $layout .= $this->renderAttr($this->getTitle(),'title="%s"');
        if (!$this->getClearAfterBind()) $layout .= $this->renderAttr($this->getValue(),'value="%s"');
        $layout .= $this->renderAttr($this->getReadonly(),'readonly');
        $layout .= $this->renderAttr($this->getDisabled(),'disabled');
        $layout .= $this->renderAttr($this->getChecked(),'checked');
        foreach ($this->events as $event) {
            $layout .= $event->renderView();
        }
        return $layout.'>';
    }
}