<?php
/**
 * User: luis
 * Date: 6/7/13
 * Time: 2:27 PM
 */
namespace App\Form\Input;

class Label
{
    protected $inner;
    protected $input;
    protected $class;
    protected $classCol;

    public function __construct(BAbstractInput $input)
    {
        $this->input = $input;
    }

    /**
     * @param mixed $class
     */
    public function setClass($class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getClass()
    {
        return trim('control-label '.$this->classCol.' '.$this->class);
    }

    /**
     * @param mixed $class
     */
    public function setClassCol($classCol)
    {
        $this->classCol = $classCol;

        return $this;
    }

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
        return $this->inner;
    }

    /**
     * @param \App\Form\Input\Input $input
     */
    public function setInput($input)
    {
        $this->input = $input;

        return $this;
    }

    /**
     * @return \App\Form\Input\Input
     */
    public function getInput()
    {
        return $this->input;
    }

    protected function renderAttr($attr,$lo)
    {
        if ($attr) {
            return ' '.sprintf($lo,$attr);
        }
    }

    public function renderView()
    {
        $layout = sprintf('<label for="%s"', $this->getInput()->getName());
        $layout .= $this->renderAttr($this->getClass(),'class="%s"');
        return $layout.sprintf('>%s</label>',$this->getInner());
    }
}