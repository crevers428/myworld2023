<?php
/**
 * User: luis
 * Date: 6/7/13
 * Time: 2:27 PM
 */
namespace App\Form\Input;

abstract class Input extends BAbstractInput
{
    protected $placeholder;
    protected $pattern;
    protected $title;
    protected $maxLength;
	protected $uppercase;
    protected $clearAfterBind;

    public function setValue($value)
    {
        if ($this->uppercase) {
            $value = strtoupper($value);
        }
        return parent::setValue($value);
    }

    /**
     * @param mixed $pattern
     */
    public function setPattern($pattern)
    {
        $this->pattern = $pattern;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * @param mixed $placeholder
     */
    public function setPlaceholder($placeholder)
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPlaceholder()
    {
        return $this->placeholder;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $maxLength
     */
    public function setMaxLength($maxLength)
    {
        $this->maxLength = $maxLength;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMaxLength()
    {
        return $this->maxLength;
    }

    public function setUppercase($b)
    {
        $this->uppercase = $b;
        return $this;
    }

    public function getUppercase()
    {
        return $this->uppercase;
    }

    public function setClearAfterBind($b)
    {
        $this->clearAfterBind = $b;
        return $this;
    }

    public function getClearAfterBind()
    {
        return $this->clearAfterBind;
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
        if (!$this->getClearAfterBind()) $layout .= $this->renderAttr($this->getInFormValue(),'value="%s"');
        $layout .= $this->renderAttr($this->getMaxLength(),'maxlength="%s"');
        $layout .= $this->renderAttr($this->getUppercase(),'style="text-transform: uppercase;"');
        $layout .= $this->renderAttr($this->getReadonly(),'readonly');
        $layout .= $this->renderAttr($this->getDisabled(),'disabled');
        /* @var InputEvent $event */
        foreach ($this->events as $event) {
            $layout .= $event->renderView();
        }
        return $layout.' />';
    }

}