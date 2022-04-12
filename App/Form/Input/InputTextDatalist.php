<?php
/**
 * User: luis
 * Date: 6/8/13
 * Time: 5:30 PM
 */
namespace App\Form\Input;

class InputTextDatalist extends InputText
{
    protected $listId;
    protected $datalist = array();

    public function __construct($name,$listId)
    {
        parent::__construct($name);
        $this->listId = $listId;
    }

    public function addData($value)
    {
        $data = new Data($value);
        $this->datalist[] = $data;
        return $this;
    }

    public function renderView()
    {
        $layout = sprintf('<input type="%s" name="%s"', $this->getType(), $this->getName());
        $layout .= $this->renderAttr($this->getId(),'id="%s"');
        $layout .= $this->renderAttr($this->getRequired(),'required');
        $layout .= $this->renderAttr($this->getAutofocus(),'autofocus');
        $layout .= $this->renderAttr($this->getClass(),'class="%s"');
        $layout .= $this->renderAttr($this->getPlaceholder(),'placeholder="%s"');
        $layout .= $this->renderAttr($this->getPattern(),'pattern="%s"');
        $layout .= $this->renderAttr($this->getTitle(),'title="%s"');
        $layout .= $this->renderAttr($this->getValue(),'value="%s"');
        $layout .= $this->renderAttr($this->getMaxLength(),'maxlength="%s"');
        $layout .= $this->renderAttr($this->getUppercase(),'style="text-transform: uppercase;"');
        $layout .= $this->renderAttr($this->getReadonly(),'readonly');
        $layout .= $this->renderAttr($this->getDisabled(),'disabled');
        $layout .= $this->renderAttr($this->listId,'list="%s"');
        foreach ($this->events as $event) {
            $layout .= $event->renderView();
        }
        $layout .= ' />';
        //
        $layout .= sprintf('<datalist id="%s">',$this->listId) . PHP_EOL;
        foreach ($this->datalist as $data) {
            $layout .= $data->renderView();
        }
        $layout .= '</datalist>' . PHP_EOL;
        return $layout;
    }
}