<?php
namespace WorldChamps\Form;

use Bootstrap\Form\BootstrapForm;
/*
use App\Form\Constraint;
use EC\Repo;
*/

class ContactForm extends BootstrapForm
{
    static public $contact_word = array(
        'en' => 'Contact',
        'es' => 'Contacto'
    );
    static public $departments = array(
        'competition' => array(
            'title' => array(
                'en' => 'Competition in general',
                'es' => 'Competición en general'
            ),
            'email' => 'wc2019@speedcubing.org.au',
            'signature' => array(
                'en' => 'WC2019 Organisation Team',
                'es' => 'Equipo organizador del WCA World Championship 2019'
            )
        ),
        'registration' => array(
            'title' => array(
                'en' => 'Registration',
                'es' => 'Registro'
            ),
            'email' => 'wc2019@speedcubing.org.au',
            'signature' => array(
                'en' => 'WC2019 Organisation Team',
                'es' => 'Equipo organizador del WCA World Championship 2019'
            )
        ),
        'local' => array(
            'title' => array(
                'en' => 'About Melbourne',
                'es' => 'Sobre Melbourne'
            ),
            'email' => 'wc2019@speedcubing.org.au',
            'signature' => array(
                'en' => 'WC2019 Organisation Team',
                'es' => 'Equipo organizador del WCA World Championship 2019'
            )
        ),
        'website' => array(
            'title' => array(
                'en' => 'About the website',
                'es' => 'Sobre la web'
            ),
            'email' => 'wc2019@speedcubing.org.au',
            'signature' => array(
                'en' => 'WC2019 Organisation Team',
                'es' => 'Equipo organizador del WCA World Championship 2019'
            )
        ),
        'staff' => array(
            'title' => array(
                'en' => 'Staff Application',
                'es' => 'Sobre la web'
            ),
            'email' => 'wc2019@speedcubing.org.au',
            'signature' => array(
                'en' => 'WC2019 Organisation Team',
                'es' => 'Equipo organizador del WCA World Championship 2019'
            )
         ),
        'sponsorship' => array(
            'title' => array(
                'en' => 'Sponsorship',
                'es' => 'Sobre la web'
            ),
            'email' => 'wc2019@speedcubing.org.au',
            'signature' => array(
                'en' => 'WC2019 Organisation Team',
                'es' => 'Equipo organizador del WCA World Championship 2019'
            )
        ),
        'nationscup' => array(
            'title' => array(
                'en' => 'Rubik\'s Nations Cup',
                'es' => 'Sobre la web'
            ),
            'email' => 'wc2019@speedcubing.org.au',
            'signature' => array(
                'en' => 'WC2019 Organisation Team',
                'es' => 'Equipo organizador del WCA World Championship 2019'
            )
        ),
    );

    public function __construct($app,$language)
    {
        $enter_your_name_words = array(
            'en' => 'Enter your name...',
            'es' => 'Introduce tu nombre...'
        );
        $name_word = array(
            'en' => 'Name',
            'es' => 'Nombre'
        );
        $enter_your_email_words = array(
            'en' => 'Enter your email...',
            'es' => 'Introduce tu email...'
        );
        $select_topic_words = array(
            'en' => '(Select topic...)',
            'es' => '(Selecciona el tema...)'
        );
        $subject_words = array(
            'en' => 'Subject (dropdown to see default options)...',
            'es' => 'Asunto (cursor abajo para sugerencias)'
        );
        $registration_word = array(
            'en' => 'Registration',
            'es' => 'Registro'
        );
        $events_words = array(
            'en' => 'Changing, removing or adding events',
            'es' => 'Poner, quitar o cambiar pruebas'
        );
        $accommodation_word = array(
            'en' => 'Accommodation',
            'es' => 'Hoteles y estancia'
        );
        $travel_word = array(
            'en' => 'Travel',
            'es' => 'Viaje'
        );
        $website_words = array(
            'en' => 'Problems with the website',
            'es' => 'Problemas con la web'
        );
        $subject_word = array(
            'en' => 'Subject',
            'es' => 'Asunto'
        );
        $message_words = array(
            'en' => 'Write your message here...',
            'es' => 'Escribe tu mensaje aquí...'
        );
        $message_word = array(
            'en' => 'Message',
            'es' => 'Mensaje'
        );
        $send_word = array(
            'en' => 'send',
            'es' => 'enviar'
        );

        parent::__construct($app,'contact',3);
        //
        $this->disableAutoComplete();
        //
        $this->addText('name')
            ->setAutofocus()
            ->setPlaceholder($enter_your_name_words[$language])
            ->setRequired()
            ->setClassCol('col-xs-8 col-md-4')
            //
            ->getLabel()
            ->setInner($name_word[$language])
            ->setClassCol('col-xs-3');
        //
        $this->addEmail('email')
            ->setPlaceholder($enter_your_email_words[$language])
            ->setRequired()
            ->setClassCol('col-xs-8 col-md-4')
            //
            ->getLabel()
            ->setInner('Email')
            ->setClassCol('col-xs-3');
        //
        $about = $this->addSelect('about');
        $about->addOption('',$select_topic_words[$language]);
        foreach ($this::$departments as $name => $department) {
            $about->addOption($name,$department['title'][$language]);
        }
        $about->getLabel()
            ->setClassCol('col-xs-3');
        $about->setRequired()
            ->setClassCol('col-xs-8 col-md-4');
        //
        /*
        $contact = $this->addSelect('contact');
        foreach ($this::$departments as $name => $department) {
            $contact->addOption($name,$department['signature']);
        }
        $contact->getLabel()
            ->setInner('Contact')
            ->setClassCol('col-xs-3');
        $contact->setDisabled(true)
            ->hide()
            ->setClassCol('col-xs-6 col-md-2');
        */
        //
        $this->addTextDatalist('subject')
            ->setPlaceholder($subject_words[$language])
			->setRequired()
			->setClassCol('col-xs-8 col-md-6')
            //
            ->addData($registration_word[$language])
            ->addData($events_words[$language])
            ->addData($accommodation_word[$language])
            ->addData($travel_word[$language])
            ->addData($website_words[$language])
            //
            ->getLabel()
            ->setInner($subject_word[$language])
            ->setClassCol('col-xs-3');
        //
        $this->addTextArea('body')
            ->setPlaceholder($message_words[$language])
            ->setRequired()
            ->setClearAfterBind(true)
            ->setClassCol('col-xs-9')
            //
            ->getLabel()
            ->setInner($message_word[$language])
            ->setClassCol('col-xs-3');
        //
        $this->addSubmit('submit')
            ->setClass('btn btn-primary')
            ->setInner($send_word[$language]);

        $this->addSecurity();
    }
}
