<?php

namespace Bootstrap\Form;

use App\Form\Input;
use App\Form\Input\BAbstractInput;
use App\Form\SecureForm;

class BootstrapForm extends SecureForm
{
    protected $buttonsOffset;

    public function __construct($app,$name,$buttonsOffset)
    {
        parent::__construct($app,$name);
        $this->buttonsOffset = $buttonsOffset;
    }

    public function renderView()
    {
        /* @var BAbstractInput $input */
        $noLabels = true;
        foreach ($this->inputs as $input) {
            if ($input->getLabel()->getInner()) {
                $noLabels = false;
                break;
            }
        }
        $layout = '<form '.($noLabels ? '' : 'class="form-horizontal"');
        $layout .= ' id="'.$this->name.'"';
        $layout .= Input\AbstractInput::renderAttr($this->action,'action="%s"');
        $layout .= Input\AbstractInput::renderAttr($this->method,'method="%s"');
        $layout .= Input\AbstractInput::renderAttr($this->onSubmit,'onsubmit="return %s;"');
        $layout .= '>' . PHP_EOL;
        foreach ($this->inputs as $input) {
            $type = $input->getType();
            if ($type=='hidden' || $type=='view') {
                $layout .= $input->renderView() . PHP_EOL;
            } else {
                $withErrors = $input->errorMessages;
                $isCheckbox = ($type=='checkbox');
                $layout .= $isCheckbox?'<div class="checkbox':'<div class="form-group';
                $layout .= ($withErrors?' error':'').'" id="'.$input->getNameInForm('div').'"';
                            $style = '';
                            if (!$input->isVisible()) {
                                $style .= 'display:none;';
                            }
                            if ($noLabels) {
                                $style .= 'margin-bottom:0;';
                            }
                            if ($style) {
                                $layout .= sprintf(' style="%s"',$style);
                            }
                            $layout .= '>' . PHP_EOL;
                    if (!$noLabels) {
                        if ($isCheckbox) {
                            $layout .= '<label>' . PHP_EOL;
                        } else {
                            $layout .= $input->renderLabelView() . PHP_EOL;
                        }
                    }
                    if ($input->getClassCol()) {
                        $layout .= '<div class="'.$input->getClassCol().'">' . PHP_EOL;
                    } else {
                        $layout .= '<div>' . PHP_EOL;
                    }
                        $layout .= $input->renderView() . PHP_EOL;
                        if (!$noLabels && $isCheckbox) {
                            $layout .= ' '.$input->getLabel()->getInner() . PHP_EOL;
                        }
                        if ($withErrors) {
                            $layout .= '<span class="help-block" style="font-size:8pt;line-height:100%;margin-top:4pt;">' . PHP_EOL;
                            foreach ($input->errorMessages as $msg) {
                                $layout .= $msg.'<br />';
                            }
                            $layout .= '</span>' . PHP_EOL;
                        }
                    $layout .= '</div>' . PHP_EOL;
                if (!$noLabels && $isCheckbox) {
                    $layout .= '</label>' . PHP_EOL;
                }
                $layout .= '</div>' . PHP_EOL;
            }
        }
        /*
        if ($this->buttonSubmit) {
            $layout .= '<div class="control-group">' . PHP_EOL;
                $layout .= '<div>' . PHP_EOL;
                    $layout .= $this->buttonSubmit->renderView() . PHP_EOL;
                $layout .= '</div>' . PHP_EOL;
            $layout .= '</div>' . PHP_EOL;
        }
        */
        if (count($this->buttons)) {
            $layout .= '<div class="form-group">' . PHP_EOL;
                if ($this->buttonsOffset) {
                    $layout .= '<div class="col-xs-offset-'.$this->buttonsOffset.' ' .
                        'col-xs-'.(12-$this->buttonsOffset).'">' . PHP_EOL;
                } else {
                    $layout .= '<div>' . PHP_EOL;
                }
                foreach ($this->buttons as $btn) {
                    $layout .= $btn->renderView() . PHP_EOL;
                }
                $layout .= '</div>' . PHP_EOL;
            $layout .= '</div>' . PHP_EOL;
        }
        $layout .= '</form>' . PHP_EOL;
        return $layout;
    }
}