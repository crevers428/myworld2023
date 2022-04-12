<?php
/**
 * User: luis
 * Date: 6/8/13
 * Time: 5:30 PM
 */
namespace App\Form\Input;

class TextArea extends Input
{
    protected $rows;

    /**
     * @param mixed $rows
     */
    public function setRows($rows)
    {
        $this->rows = $rows;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRows()
    {
        return $this->rows;
    }

    public function renderView()
    {
        $layout = sprintf('<textarea name="%s"', $this->getName());
        $layout .= $this->renderAttr($this->getId(),'id="%s"');
        $layout .= $this->renderAttr($this->getRequired(),'required');
        $layout .= $this->renderAttr($this->getDisabled(),'disabled');
        $layout .= $this->renderAttr($this->getAutofocus(),'autofocus');
        $layout .= $this->renderAttr($this->getClass(),'class="%s"');
        $layout .= $this->renderAttr($this->getPlaceholder(),'placeholder="%s"');
        $layout .= $this->renderAttr($this->getPattern(),'pattern="%s"');
        $layout .= $this->renderAttr($this->getTitle(),'title="%s"');
        $layout .= $this->renderAttr($this->getRows(),'rows="%s"');
        $layout .= $this->renderAttr($this->getMaxLength(),'maxlength="%s"');
        /* @var InputEvent $event */
        foreach ($this->events as $event) {
            $layout .= $event->renderView();
        }
        return sprintf($layout.'>%s</textarea>',$this->getClearAfterBind()?'':$this->getValue());
    }

}