<?php

namespace App\Form\Input;

class InputView {

    protected $type = 'view';
    protected $content;

    public function __construct($content)
    {
        $this->content = $content;
    }

    public function getType()
    {
        return $this->type;
    }

    public function renderView()
    {
        return $this->content;
    }
} 