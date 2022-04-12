<?php
/**
 * User: luis
 * Date: 6/7/13
 * Time: 2:27 PM
 */
namespace App\Form\Input;

class ButtonSubmit extends AbstractInput
{
    protected $inner;

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
            return 'Submit';
        }
        return $this->inner;
    }

    public function renderView()
    {
        $layout = sprintf('<button type="submit" name="%s"', $this->getName());
        $layout .= $this->renderAttr($this->getId(),'id="%s"');
        $layout .= $this->renderAttr($this->getClass(),'class="%s"');
        return $layout.sprintf('>%s</button>',$this->getInner());
    }
}