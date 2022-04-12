<?php
/**
 * User: luis
 * Date: 6/7/13
 * Time: 2:27 PM
 */
namespace App\Form\Input;

use App\Form\Constraint;

abstract class  BAbstractInput extends AbstractInput
{
    protected $required;
    protected $value;
    protected $autofocus;
	protected $readonly;
    protected $disabled;

    protected $label;
    public $errorMessages;

    public function __construct($name)
    {
        parent::__construct($name);
        $this->label = new Label($this);
    }

    public function getClass()
    {
        return trim('form-control '.$this->class);
    }

    public function setRequired()
    {
        $this->required = true;
		$this->addConstraint(new Constraint\ConstraintNotEmpty());
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAutofocus()
    {
        return $this->autofocus;
    }

    public function setAutofocus()
    {
        $this->autofocus = true;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRequired()
    {
        return $this->required;
    }

    /**
     * @param \App\Form\Input\Label $label
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return \App\Form\Input\Label
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return htmlspecialchars($this->value);
    }

    public function getInFormValue()
    {
        return $this->getValue();
    }

    public function getRawValue()
    {
        return $this->value;
    }

    public function renderLabelView()
    {
        return $this->getLabel()->renderView();
    }

    public function isValid($lastValue)
    {
        //echo ("<p>LASTVALUE dentro =$lastValue</p>");
        $valid = true;
        /* @var Constraint $constraint */
        foreach ($this->constraints as $constraint) {
            /*if ($this->getRawName()=='direccion') {
                print_r($this->constraints);
                echo "<p>".$this->getRawValue()."</p>";
                echo "<p>".$lastValue."</p>";
                die('llego --- FIN');
            }*/
            if (!$constraint->isValid($this->getRawValue(), $lastValue)) {
                $this->errorMessages[] = $constraint->getErrorMsg();
                $valid = false;
            }
        }
        return $valid;
    }

    public function setReadonly($b)
    {
        $this->readonly = $b;
        return $this;
    }

    public function getReadonly()
    {
        return $this->readonly;
    }

    public function setDisabled($b)
    {
        $this->disabled = $b;
        return $this;
    }

    public function getDisabled()
    {
        return $this->disabled;
    }

    public abstract function renderView();
}