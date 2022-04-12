<?php

namespace App\Form;

class SecureForm extends Form {

    const ROBOTS_TRAP = 'coup_on';
    const ANTI_CSRF = 'anti_csrf_token';
    const ANTI_CSRF_SUFFIX = '_csrf';

    var $securized;

    // this function MUST be called at the end of every descendant's constructor
    public function addSecurity()
    {
        $this->addAntiRobots(SecureForm::ROBOTS_TRAP);
        $this->addAntiCSRF(SecureForm::ANTI_CSRF);
        $this->securized = true;
    }

    public function isValid()
    {
        if (!$this->securized) die ('Unsecured form - calling addSecurity() is mandatory');
        return parent::isValid();
    }

} 