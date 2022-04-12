<?php
/**
 * User: luis
 * Date: 6/8/13
 * Time: 5:56 PM
 */
namespace App\Form;

use App\Exception\Exception;
use App\Form\Input;
use App\Form\Constraint;

class Form
{
    const GET = 'GET';
    const POST = 'POST';

    protected $app;

    protected $name;
    protected $action;
    protected $method = Form::POST;
    protected $inputs = array();
    protected $autoComplete = true;
    protected $buttons = array();
    protected $onSubmit;

    public function __construct($app, $name)
    {
        $this->app = $app;
        $this->name = sprintf('%s_form',$name);
    }

	public function inPOST()
	{
		return array_key_exists($this->name,$_POST);
	}

    protected function rename($name)
    {
        return $this->name.'['.$name.']';
    }

    public function addText($name)
    {
        $input = new Input\InputText($this->rename($name));
        $this->inputs[] = $input;
        return $input;
    }

    public function addTextDatalist($name)
    {
        $input = new Input\InputTextDatalist($this->rename($name),'default_subjects');
        $this->inputs[] = $input;
        return $input;
    }

    public function addHidden($name)
    {
        $input = new Input\InputHidden($this->rename($name));
        $this->inputs[] = $input;
        return $input;
    }

    public function addEmail($name)
    {
        $input = new Input\InputEmail($this->rename($name));
        $this->inputs[] = $input;
        return $input;
    }

    public function addDate($name)
    {
        $input = new Input\InputDate($this->rename($name));
		$input->setPattern('(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d');
		$input->addConstraint(new Constraint\ConstraintDate());
        $this->inputs[] = $input;
        return $input;
    }

    public function addPassword($name)
    {
        $input = new Input\InputPassword($this->rename($name));
        $this->inputs[] = $input;
        return $input;
    }

    public function addSelect($name)
    {
        $input = new Input\Select($this->rename($name));
        $this->inputs[] = $input;
        return $input;
    }

    public function addTextArea($name)
    {
        $input = new Input\TextArea($this->rename($name));
        $this->inputs[] = $input;
        return $input;
    }

    public function addAntiRobots($name)
    {
        $input = new Input\InputAntiRobots($this->rename($name));
        $this->inputs[] = $input;
        return $input;
    }

    public function addAntiCSRF($name)
    {
        $input = new Input\InputAntiCSRF($this->rename($name));
        $this->inputs[] = $input;
        return $input;
    }

    public function addSubmit($name)
    {
        $button = new Input\ButtonSubmit($this->rename($name));
        $this->buttons[] = $button;
        return $button;
    }

    public function addButton($name)
    {
        $button = new Input\Button($this->rename($name));
        $this->buttons[] = $button;
        return $button;
    }

    public function addCheckbox($name)
    {
        $input = new Input\InputCheckbox($this->rename($name));
        $this->inputs[] = $input;
        return $input;
    }

    public function addView($content)
    {
        $input = new Input\InputView($content);
        $this->inputs[] = $input;
        return $input;
    }

    public function disableAutoComplete()
    {
        $this->autoComplete = false;
        return $this;
    }

    public function renderView()
    {
        /*
         * Only BootstrapForm is maintained
         */

        /*
        $layout = '<form';
        $layout .= Input\AbstractInput::renderAttr($this->action,'action="%s"');
        $layout .= Input\AbstractInput::renderAttr($this->method,'method="%s"');
        if (!$this->autoComplete) {
            $layout .= ' autocomplete="off"';
        }
        $layout .= '>' . PHP_EOL;
        */
        /* @var Input\Input $input */
        /*
        foreach ($this->inputs as $input) {
            $layout .= $input->renderLabelView() . '<br />' . PHP_EOL;
            $layout .= $input->renderView() . '<br />' . PHP_EOL;
            if ($input->errorMessages) {
                $layout .= '<span style="color:red;font-size:8pt;">' . PHP_EOL;
                foreach ($input->errorMessages as $msg) {
                    $layout .= $msg.'<br />';
                }
                $layout .= '</span>' . PHP_EOL;
            }
        }
        */
        /*
        if ($this->buttonSubmit) {
            $layout .= $this->buttonSubmit->renderView() . '<br />' . PHP_EOL;
        }
        $layout .= '</form>' . PHP_EOL;
        return $layout;
        */
    }

    public function isValid()
    {
        if (!array_key_exists($this->name,$_POST)) {
            die(sprintf('Cannot bind form %s', $this->name));
        }
        $post_form = $_POST[$this->name];
        //
        $errors = false;
        $lastValue = '';
        //$counter = 0;
        /* @var Input\Input $input */
        foreach($this->inputs as $input) {
            $type = $input->getType();
            if ($type=='view') continue;
            if (!$input->getDisabled()) {
                $rawName = $input->getRawName();
                if (!array_key_exists($rawName,$post_form)) {
                    if ($type=='checkbox') {
                        $post_form[$rawName] = null;
                    } else {
                        if (!$this->app->isProd()) var_dump($_POST);
                            die(sprintf('Cannot bind field %s', $rawName));
                    }
                }
                $input->setValue($post_form[$rawName]);
                //echo ("<p>".$input->getRawName().' = '.$input->getRawValue()."</p><p>LASTVALUE=$lastValue</p>");
                //if (++$counter > 10) die();
                if (!$input->isValid($lastValue)) {
                    $errors = true;
                    //echo $input->getName().' = '.$input->getValue().'<br />';
                }
                $lastValue = $input->getValue();
            }
        }
        return !$errors;
    }

    public function getValue($index)
    {
        return $this->inputs[$index]->getValue();
    }

    public function getRawValue($index)
    {
        if (method_exists($this->inputs[$index],'getRawValue')) {
            return $this->inputs[$index]->getRawValue();
        } else {
            return $this->inputs[$index]->getValue();
        }
    }

    public function setValue($index,$value)
    {
        return $this->inputs[$index]->setValue($value);
    }

    /**
     * @param mixed $onSubmit
     */
    public function setOnSubmit($onSubmit)
    {
        $this->onSubmit = $onSubmit;
    }

    public function getAllValues()
    {
        $values = array();
        foreach ($this->inputs as $input) {
            $type = $input->getType();
            if ($type != 'view') {
                $values[$input->getRawName()] = $input->getRawValue();
            }
        }
        return $values;
    }

    public function getValueByName($name)
    {
        $index = 0;
        while($index < count($this->inputs)) {
            if ($this->inputs[$index]->getType() != 'view' && $this->inputs[$index]->getRawName() == $name) {
                return $this->inputs[$index]->getRawValue();
            }
            $index++;
        }
        return null;
    }

    public function disableAll()
    {
        foreach ($this->inputs as $input) {
            if ($input->getType() != 'view') {
                $input->setDisabled(true);
            }
        }
    }

    public function setAllValues($values)
    {
        if (is_array($values)) {
            foreach ($this->inputs as $input) {
                if ($input->getType() != 'view') {
                    $inputName = $input->getRawName();
                    if (array_key_exists($inputName,$values)) {
                        $input->setValue($values[$inputName]);
                    };
                }
            }
        }
    }

}