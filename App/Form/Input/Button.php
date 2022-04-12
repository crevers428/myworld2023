<?php

namespace App\Form\Input;

class Button extends AbstractInput
{
    protected $inner;
    protected $onClick;

    /**
     * @param mixed $inner
     */
    public function setInner($inner)
    {
        $this->inner = $inner;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getInner()
    {
        if (!$this->inner) {
            return 'Cancel';
        }
        return $this->inner;
    }

    public function setOnClick($onClick)
    {
        $this->onClick = $onClick;

        return $this;
    }

    public function getOnClick()
    {
        return $this->onClick;
    }

    public function renderView()
    {
        $layout = sprintf('<button type="button" name="%s"', $this->getName());
        $layout .= $this->renderAttr($this->getId(),'id="%s"');
        $layout .= $this->renderAttr($this->getClass(),'class="%s"');
        $layout .= $this->renderAttr($this->getOnClick(),'onclick="%s"');
        return $layout.sprintf('>%s</button>',$this->getInner());
    }
}