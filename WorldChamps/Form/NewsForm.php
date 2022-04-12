<?php

namespace WorldChamps\Form;

use App\Application;
use App\Form\Constraint;
use Bootstrap\Form\BootstrapForm;
use WorldChamps\WorldChampsApplication;
use WorldChamps\Repo\RepoUsers;

class NewsForm extends BootstrapForm
{
    const ADD = 0;
    const EDIT = 1;
    const DELETE = 2;

    public function __construct($app,$action,$news=null)
    {
        parent::__construct($app,'news_add',2);
        //
        $this->disableAutoComplete();
        //
        $this->addText('title_en')
            ->setRequired()
            ->setPlaceholder('Title...')
            ->setClassCol('col-xs-10 col-md-10')
            //
            ->getLabel()
            ->setInner('Title')
            ->setClassCol('col-xs-2');
        //
        $this->addTextArea('body_en')
            ->setRequired()
            ->setPlaceholder('Body...')
            ->setRows(10)
            ->setClassCol('col-xs-10 col-md-10')
            //
            ->getLabel()
            ->setInner('Body')
            ->setClassCol('col-xs-2');
        //
        $authorInput = $this->addSelect('authorId')
            ->setRequired()
            ->setClassCol('col-xs-6 col-sm-3 col-md-2');
            //
        $authorInput->getLabel()
            ->setInner('Author')
            ->setClassCol('col-xs-2');
        //
        $repo = new RepoUsers($this->app);
        $admins = $repo->getUsersByRole(WorldChampsApplication::ROLE_ORGANIZER);
        foreach ($admins as $admin) {
            $authorInput->addOption($admin['id'],$admin['name']);
        }
        //
        $this->addText('date')
            ->setRequired()
            ->setPlaceholder('yyyy-mm-dd hh:mm:ss')
            ->setTitle('Use format yyyy-mm-dd hh:mm:ss')
            ->setClassCol('col-xs-6 col-sm-3')
            ->addConstraint(new Constraint\Constraint(
                    '/^(19|20)\d\d\-(0[1-9]|1[012])\-(0[1-9]|[12][0-9]|3[01]) ([0-1][0-9]|2[0-3])\:[0-5][0-9]\:[0-5][0-9]$/',
                    'Use format yyyy-mm-dd hh:mm:ss'
                ))
            //
            ->getLabel()
            ->setInner('Publish on')
            ->setClassCol('col-xs-2');
        //
        $this->setAllValues($news);
        $this->addSecurity();
        //
        switch ($action) {
            case $this::ADD:
                $this->inputs[0]->setAutofocus();
                $authorInput->setValue($this->app->getAuthId());
                $this->setValue(5, strftime('%F %T'));
                $this->addSubmit('submit')
                    ->setClass('btn btn-success')
                    ->setInner('<span class="glyphicon glyphicon-plus"></span> Add');
                break;
            case $this::EDIT:
                $this->inputs[0]->setAutofocus();
                $this->addSubmit('submit')
                    ->setClass('btn btn-info')
                    ->setInner('<span class="glyphicon glyphicon-pencil"></span> Edit');
                break;
            case $this::DELETE:
                $this->disableAll();
                $this->addSubmit('submit')
                    ->setClass('btn btn-danger')
                    ->setInner('<span class="glyphicon glyphicon-remove"></span> Delete');
        }
    }

}
