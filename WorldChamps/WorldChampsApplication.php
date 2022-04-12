<?php

namespace WorldChamps;

use App\Application;
use Bootstrap\App\BootstrapApp;
use WorldChamps\Lib\BasicFuncs;
use WorldChamps\Lib\WcaOauth;
use WorldChamps\Repo\RepoAdmin;
use WorldChamps\Repo\RepoEvents;
use WorldChamps\Repo\RepoPayments;
use WorldChamps\Repo\RepoRegistration;
use WorldChamps\Repo\RepoStaff;
use WorldChamps\Repo\RepoTickets;
use WorldChamps\Repo\RepoUsers;
use WorldChamps\View\Page\ContactPageView;
use WorldChamps\View\Page\ExceptionDoesNotExistView;
use WorldChamps\View\Page\ExceptionError;
use WorldChamps\View\Page\MDInstructionsPageView;
use WorldChamps\View\Page\MyWorldsAccessPageView;
use WorldChamps\View\Page\NewsEditPageView;

class WorldChampsApplication extends BootstrapApp
{
    public $secret = '';
    protected $cookiePrefix = '';
    protected $WCA_OAuth_redirect = array();
    protected $WCA_OAuth_redirect_page = '';

    const REDIRECT_IDENTIFIED = "REDIRECT_IDENTIFIED";
    const IDENTIFIED = "IDENTIFIED";
    /*
    const ADD_MODE = 0;
    const EDIT_MODE = 1;
    const DELETE_MODE = 2;
    */
    const ROLE_DELEGATE = 1;
    const ROLE_ORGANIZER = 2;

    public function __construct($prod = Application::ENV_AUTO, $debug = Application::ENV_AUTO)
    {
        parent::__construct($prod,$debug);
        $this->addVersion('language',array('en'),true,'getDefaultLanguage');
        setlocale(LC_ALL,'en_AU');
	include('../App/__private_config__.php');
	$this->secret = $CONF_SECRET;
	$this->cookiePrefix = $CONF_COOKIE_PREFIX;
	$this->WCA_OAuth_redirect = array(
	    FALSE => $CONF_DEV_PROT . $CONF_DEV_HOST . '/',
	    TRUE =>  $CONF_PROD_PROT . $CONF_PROD_HOST . '/'
	);
	$this->WCA_OAuth_redirect_page = $CONF_OAUTH_REDIR_PAGE;
    }

    protected function getDefaultLanguage()
    {
	    return false;
#        if (in_array(BasicFuncs::getCountryCodeFromIp(),array('ES')))
#            return 'es';
#        else
#            return false; // false, not other value
    }

    public function renderPostIt(&$layout)
    {
        if ($return = $this->getPostIt($type,$msg)) {
            switch($type) {
                case Application::POSTIT_ERROR:
                    $class = 'danger';
                    break;
                case Application::POSTIT_SUCCESS:
                    $class = 'success';
                    break;
                default:
                    $class = 'info';
                    break;
            }
            $layout = sprintf(
                '<div class="alert alert-%s">'.
                '<button type="button" class="close" data-dismiss="alert">'.
                '×'.
                '</button>'.
                ' %s'.
                '</div>',
                $class,
                $msg
            );
        }
        return $return;
    }

    public function error($msg)
    {
        $this->cancelOriginalCall();
        $view = new ExceptionError($this);
        $view->render(array(
                'message' => $msg
            ));
    }

    protected function error404()
    {
        $this->cancelOriginalCall();
        (new ExceptionDoesNotExistView($this))->render();
    }

    public function action()
    {
        $view = $this->firewallAndCache(
            null, null,
            func_num_args(),0,0, '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            '\WorldChamps\View\Page\HomePageView'
        );
        $view->render();
    }

    public function actionEvents()
    {
        $view = $this->firewallAndCache(
            null, null,
            func_num_args(),0,0, '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            'WorldChamps\View\Page\EventsPageView'
        );
        $view->render();
    }

    public function actionSchedule()
    {
        $view = $this->firewallAndCache(
            null, null,
            func_num_args(),0,0, '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            'WorldChamps\View\Page\SchedulePageView'
        );
        $view->render();
    }

    public function actionTickets()
    {
        $view = $this->firewallAndCache(
            null, null,
            func_num_args(),0,0, '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            'WorldChamps\View\Page\TicketsPageView'
        );
        $view->render();
    }

    public function actionCollaborators()
    {
        $view = $this->firewallAndCache(
            null, null,
            func_num_args(),0,0, '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            'WorldChamps\View\Page\CollaboratorsPageView'
        );
        $view->render();
    }

    public function actionContact()
    {
        $message_sent = array(
            'en' => 'Your message has been sent!',
            'es' => '¡Tu mensaje ha sido enviado!'
        );
        $message_not_sent = array(
            'en' => 'Could not send email!',
            'es' => '¡No se ha podido enviar tu mensaje!'
        );
        $this->firewall(
            null, null,
            func_num_args(),0,0, '\WorldChamps\View\Page\ExceptionDoesNotExistView'
        );
        $language = $this->getVersionValue("language");
        $form = new Form\ContactForm($this,$language);
        if ($this->isPOST() && $form->isValid()) {
            $visitorName = $form->getValue(0);
            $visitorEmail = $form->getValue(1);
            $about = $form->getValue(2);
            $subject = $form->getRawValue(3);
            $body = $form->getValue(4);
            $teamEmail = Form\ContactForm::$departments[$about]['email'];
            $signature = Form\ContactForm::$departments[$about]['signature'][$language];
            //$language = Form\ContactForm::$departments[$about]['language'];
            $sender = new Email\Email($this);
            // copy to the team
            $emailView = new View\Email\ContactEmailView($this,$language);
            $sent = $sender->send(
                $this->isProd(),
                $teamEmail,
                $teamEmail,
                $visitorEmail,
                null,
                Form\ContactForm::$contact_word[$language].' - '.$subject,
                $emailView->renderView(array(
                        'name' => $visitorName,
                        'body' => $body
                    ))
            );
            if (!$sent) {
                $this->addPostIt(Application::POSTIT_ERROR,$message_not_sent[$language]);
            } else {
                $this->addPostIt(Application::POSTIT_SUCCESS,$message_sent[$language]);
                // notification to sender
                $emailView = new View\Email\ContactNotificationEmailView($this,$language);
                $sender->send(
                    $this->isProd(),
                    $visitorEmail,
                    null, // noreply
                    null,
                    null,
                    Form\ContactForm::$contact_word[$language].' - '.$subject,
                    $emailView->renderView(array(
                            'name' => explode(' ',$visitorName)[0],
                            'signature' => $signature,
                            'subject' => $subject,
                            'body' => $body
                        ))
                );
            }
        }
        $view = new ContactPageView($this);
        $view->render(array(
                'form' => $form->renderView()
            ));
    }

    public function actionTravel()
    {
        $view = $this->firewallAndCache(
            null, null,
            func_num_args(),0,0, '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            '\WorldChamps\View\Page\TravelPageView'
        );
        $view->render();
    }

    public function actionLodging()
    {
        $view = $this->firewallAndCache(
            null, null,
            func_num_args(),0,0, '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            '\WorldChamps\View\Page\LodgingPageView'
        );
        $view->render();
    }
    public function actionFaq()
    {
        $view = $this->firewallAndCache(
            null, null,
            func_num_args(),0,0, '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            '\WorldChamps\View\Page\FaqPageView'
        );
        $view->render();
    }
    public function actionKoalafication()
    {
        $view = $this->firewallAndCache(
            null, null,
            func_num_args(),0,0, '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            '\WorldChamps\View\Page\KoalaficationPageView'
        );
        $view->render();
    }
    public function actionNationsCup()
    {
        $view = $this->firewallAndCache(
            null, null,
            func_num_args(),0,0, '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            '\WorldChamps\View\Page\NationsCupPageView'
        );
        $view->render();
    }
    public function actionWarmup()
    {
        $view = $this->firewallAndCache(
            null, null,
            func_num_args(),0,0, '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            '\WorldChamps\View\Page\WarmupPageView'
        );
        $view->render();
    }

    /* remove to close access ---> */
    protected function identifyInWCA($redirectWhenIdentified)
    {
        if (!array_key_exists(Application::AUTH_ID,$_SESSION)) {

            $language = $this->getVersionValue("language");
            $_SESSION[$this::REDIRECT_IDENTIFIED] = $redirectWhenIdentified;

            include "Repo/__private_oauth_wca__.inc";

            $this->redirect(
                "https://www.worldcubeassociation.org/oauth/authorize?" .
                "client_id=".$wca_oauth_id."&" .
                "redirect_uri=" . $this->WCA_OAuth_redirect[$this->isProd()] . $language . $this->WCA_OAuth_redirect_page . "&" .
                "response_type=code&" .
                "scope=public+dob+email"
            );
        }
    }

    protected function validateUser($user)
    {
        if (
            !$user->me->id ||
            !$user->me->name ||
            !$user->me->gender ||
            !$user->me->country_iso2 ||
            !$user->me->dob ||
            !$user->me->email
        ) {
            $this->error(
                $this->getVersionValue("language") == 'en' ?
                "<p>Your user profile in the WCA website is incomplete. We expect your profile to have, at least:</p>" .
                "<ul><li>a full name</li><li>a gender</li><li>a country</li><li>a date of birth</li></ul>" .
                "<p>Please <a href='https://www.worldcubeassociation.org/profile/edit' target='_blank'>edit and complete your details " .
                "in the WCA website</a> and <a href='{{link}}{{version-language}}/myWorlds'>try again</a>.</p>"
                :
                "<p>Tu perfil de usuario en la WCA está incompleto. Tu perfil debe tener, al menos:</p>" .
                "<ul><li>nombre completo</li><li>sexo</li><li>nacionalidad</li><li>fecha de nacimiento</li></ul>" .
                "<p>Por favor, <a href='https://www.worldcubeassociation.org/profile/edit' target='_blank'>edita y completa tus detalles " .
                "en la web de la WCA</a> e <a href='{{link}}{{version-language}}/myWorlds'>inténtalo de nuevo</a>.</p>"
            );
        }
    }

    public function actionIdentify()
    {
        if (func_num_args()) $this->error404();

        if (array_key_exists("code",$_GET)) {

            $language = $this->getVersionValue("language");

            include "Repo/__private_oauth_wca__.inc";

            $wca = new WcaOauth(array(
                'applicationId' => $wca_oauth_id,
                'applicationSecret' => $wca_oauth_secret,
                'redirectUri' => $this->WCA_OAuth_redirect[$this->isProd()] . $language . $this->WCA_OAuth_redirect_page
            ));

            try {

                $wca->fetchAccessToken($_GET["code"]);
                $user = $wca->getUser();

                $this->validateUser($user);

                $repo = new RepoUsers($this);
                $_SESSION[Application::AUTH_ROLE] = $repo->getRole($user);
                $_SESSION[Application::AUTH_USER] = $user->me->email;
                $_SESSION[Application::AUTH_ID] = $user->me->id;

                $this->setCookie($this::IDENTIFIED,"SET");

                if (array_key_exists($this::REDIRECT_IDENTIFIED,$_SESSION)) {
                    $this->redirect($_SESSION[$this::REDIRECT_IDENTIFIED]);
                } else {
                    $this->redirect('/');
                }

            } catch (\Exception $e) {

                $this->error("WCA Oauth - " . $e->getMessage());

            }

        } else {

            $this->error("WCA OAuth - no code");

        }
    }

    public function actionLogout()
    {
        unset($_SESSION[Application::AUTH_USER]);
        unset($_SESSION[Application::AUTH_ID]);
        unset($_SESSION[Application::AUTH_ROLE]);
        $this->removeCookie($this::IDENTIFIED);
        $this->redirect('/');
    }

    public function actionMyWorldsAccess()
    {
        if (func_num_args()) $this->error404();

        $language = $this->getVersionValue("language");
        if ($this->existsCookie($this::IDENTIFIED)) $this->redirect("/$language/myworlds");

        $view = new MyWorldsAccessPageView($this);
        $view->render();
    }

    public function actionMyWorlds()
    {
        if (func_num_args()) $this->error404();

        $this->identifyInWCA("/myworlds");

        $repo = new RepoUsers($this);
        $repo->renderMyWorlds();
    }

    public function actionPayment()
    {
        if (func_num_args()) $this->error404();

        $repo_reg = new RepoRegistration($this);
        try {
            $repo_reg->checkRegistrationOpen("Paying is not possible");
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        $this->identifyInWCA("/payment");

        $post_total_registration = array_key_exists("total",$_POST) && array_key_exists("registration",$_POST);
        $repo = new RepoPayments($this);
        if (array_key_exists("stripeToken",$_POST) && $post_total_registration) {

            try {
                $repo->chargePayment();
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        } elseif ($post_total_registration) {

            try {
                $repo->renderPayment();
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        } else {
            $language = $this->getVersionValue("language");
            $this->error(
                $language == "en" ?
                "Missing parameters or session expired. Please try again."
                :
                "Faltan parámetros o la sesión expiró. Por favor, inténtalo de nuevo."
            );
        }
    }

    public function actionStaff()
    {
        $num_args = func_num_args();
        $view = $this->firewallAndCache(
            null, null,
            $num_args,0,1, '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            '\WorldChamps\View\Page\StaffPageView'
        );

        $this->identifyInWCA("/staff");

        if ($num_args) {
            $arg = func_get_arg(0);
            $options = array("byname","bycountry");
            if ($_SESSION[Application::AUTH_ROLE] == WorldChampsApplication::ROLE_ORGANIZER) {
                $options[] = "byvotes";
            }
            if (array_search($arg,$options)===false) $this->error404();
            $order = ($arg == "bycountry" ? "country" : ($arg == "byvotes" ? "votes" : "name"));
        } else {
            $order = "name";
        }
        $repo = new RepoStaff($this);
        $repo->renderStaff($view,$order);
    }

    public function actionCompetitors()
    {
        $num_args = func_num_args();
        $view = $this->firewallAndCache(
            null, null,
            $num_args,0,1, '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            '\WorldChamps\View\Page\CompetitorsPageView'
        );

        if ($num_args) {
            $arg = func_get_arg(0);
            if (array_search($arg,array("byname","bycountry"))===false) $this->error404();
            $order_code = ($num_args && $arg == "bycountry" ? RepoUsers::ORDER_COUNTRY : RepoUsers::ORDER_NAME);
        } else {
            $order_code = RepoUsers::ORDER_NAME;
        }
        $repo_usr = new RepoUsers($this);
        $repo_evt = new RepoEvents($this);
        $view->render(array(
                'events' => $repo_evt->getRealEvents(),
                'competitors' => $repo_usr->getCompetitors($order_code, $totals),
                'totals' => $totals,
            ));
    }

    public function actionPsychsheet($eventId)
    {
        $nargs = func_num_args();
        $view = $this->firewallAndCache(
            null, null,
            $nargs,1,1, '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            'WorldChamps\View\Page\PsychsheetPageView'
        );

        $repo_evt = new RepoEvents($this);
        $events = $repo_evt->getRealEvents();
        $key = array_search($eventId, array_column($events,'id'));
        if ($key === false) $this->error404();

        $repo_usr = new RepoUsers($this);
        $view->render(array(
                'eventId' => $eventId,
                'eventName' => $events[$key]['name'],
                'competitors' => $repo_usr->getPsychsheet($eventId, $name1, $name2),
                'name1' => $name1,
                'name2' => $name2,
            ));
    }

    public function actionRegistration()
    {
        $nargs = func_num_args();
        $view = $this->firewallAndCache(
            null, null,
            $nargs,0,0, '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            'WorldChamps\View\Page\RegistrationPageView'
        );

        $repo_evt = new RepoEvents($this);

        $view->render(array(
                'events' => $repo_evt->getRegistrationFees($frames,$dateOpening),
                'frames' => $frames,
                'dateOpening' => $dateOpening,
            ));
    }

    public function actionNews()
    {
        $view = $this->firewallAndCache(
            null, null,
            func_num_args(),0,0, '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            '\WorldChamps\View\Page\NewsPageView'
        );
        $repo = new Repo\RepoNews($this);
        $view->render(array(
                'news' => $repo->getNews(),
            ));
    }

    public function actionBuytickets()
    {
        if (func_num_args()) $this->error404();

        $this->identifyInWCA("/buytickets");

        $repo_tickets = new RepoTickets($this);
        $repo_tickets->checkOnSale();

        $post_total_tickets = array_key_exists("total",$_POST) && array_key_exists("tickets",$_POST);
        $repo_pay = new RepoPayments($this);
        $repo_tickets = new RepoTickets($this);
        if (array_key_exists("stripeToken",$_POST) && $post_total_tickets) {

            try {
                $repo_pay->chargeTickets();
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        } elseif ($post_total_tickets) {

            try {
                $repo_tickets->renderPayment();
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        } else {
            $language = $this->getVersionValue("language");
            $this->error(
                $language == "en" ?
                    "Missing parameters or session expired. Please try again."
                    :
                    "Faltan parámetros o la sesión expiró. Por favor, inténtalo de nuevo."
            );
        }
    }

    public function actionFunday()
    {
        $view = $this->firewallAndCache(
            null, null,
            func_num_args(),0,0, '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            'WorldChamps\View\Page\FundayPageView'
        );
        $view->render();
    }

    // ************************************ ADMIN *************************************************************

    public function actionAdmin()
    {
        $view = $this->firewallAndCache(
            WorldChampsApplication::ROLE_ORGANIZER, '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            func_num_args(),0,0, '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            '\WorldChamps\View\Page\AdminPageView'
        );
        $view->render();
    }

    public function actionAdminImport()
    {
        $this->firewall(
            WorldChampsApplication::ROLE_ORGANIZER, '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            func_num_args(),0,0, '\WorldChamps\View\Page\ExceptionDoesNotExistView'
        );
        $repo = new RepoAdmin($this);
        $repo->importWCA();
    }

    public function actionAdminPayments()
    {
        $view = $this->firewallAndCache(
            WorldChampsApplication::ROLE_ORGANIZER, '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            func_num_args(),0,0, '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            '\WorldChamps\View\Page\AdminPaymentsPageView'
        );
        $repo = new RepoPayments($this);
        $view->render(array(
            'payments' => $repo->getAdminPayments($totals),
            'totals' => $totals,
            'tickets' => $repo->getAdminTickets($tickets_totals,$turnout),
            'tickets_totals' => $tickets_totals,
            'turnout' => $turnout,
            'check_total' => $totals[1]['count']+$totals[6]['count']+$tickets_totals[0]['amount'],
        ));
    }

    public function actionAdminCredentials()
    {
        $this->firewall(
            WorldChampsApplication::ROLE_ORGANIZER, '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            func_num_args(),0,0, '\WorldChamps\View\Page\ExceptionDoesNotExistView'
        );
        $repo = new RepoAdmin($this);
        $repo->credentials();
    }

    public function actionAdminWCIF()
    {
        $this->firewall(
            WorldChampsApplication::ROLE_ORGANIZER, '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            func_num_args(),0,0, '\WorldChamps\View\Page\ExceptionDoesNotExistView'
        );
        $repo = new RepoAdmin($this);
        $repo->wcif();
    }

    public function actionAdminCSV()
    {
        $this->firewall(
            WorldChampsApplication::ROLE_ORGANIZER, '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            func_num_args(),0,0, '\WorldChamps\View\Page\ExceptionDoesNotExistView'
        );
        $repo = new RepoAdmin($this);
        $repo->csv();
    }

    public function actionAdminReimbursements()
    {
        $this->firewall(
            WorldChampsApplication::ROLE_ORGANIZER, '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            func_num_args(),0,0, '\WorldChamps\View\Page\ExceptionDoesNotExistView'
        );
        $repo = new RepoAdmin($this);
        $repo->reimbursements();
    }

    public function actionAdminTshirts()
    {
        $this->firewall(
            WorldChampsApplication::ROLE_ORGANIZER, '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            func_num_args(),0,0, '\WorldChamps\View\Page\ExceptionDoesNotExistView'
        );
        $repo = new RepoAdmin($this);
        $repo->t_shirts();
    }

    public function actionAdminNonqualified()
    {
        $this->firewall(
            WorldChampsApplication::ROLE_ORGANIZER, '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            func_num_args(),0,1, '\WorldChamps\View\Page\ExceptionDoesNotExistView'
        );
        $num_args = func_num_args();
        if ($num_args) {
            if ($num_args > 1) $this->error404();
            $arg = func_get_arg(0);
            if ($arg != "unregister") $this->error404();
        }
        $repo = new RepoAdmin($this);
        if ($num_args) {
            $repo->unregisterNonQualified();
        } else {
            $repo->nonQualified();
        }
    }

    public function actionAdminCheckinlist()
    {
        $this->firewall(
            WorldChampsApplication::ROLE_ORGANIZER, '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            func_num_args(),0,0, '\WorldChamps\View\Page\ExceptionDoesNotExistView'
        );
        $repo = new RepoAdmin($this);
        $repo->checkInList();
    }

    // ------------------------------------------- NEWS ----------------------------------------------------------

    public function actionAdminNews()
    {
        $view = $this->firewallAndCache(
            WorldChampsApplication::ROLE_ORGANIZER, '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            func_num_args(),0,0, '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            '\WorldChamps\View\Page\AdminNewsPageView'
        );
        $repo = new Repo\RepoNews($this);
        $view->render(array(
                'news' => $repo->getNews()
            ));
    }
    
    public function actionAdminNewsAdd()
    {
        $this->firewall(
            WorldChampsApplication::ROLE_ORGANIZER,
            '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            func_num_args(),0,0, '\WorldChamps\View\Page\ExceptionDoesNotExistView'
        );
        $form = new Form\NewsForm($this,Form\NewsForm::ADD);
        if ($this->isPOST() && $form->isValid()) {
            $repo = new Repo\RepoNews($this);
            $repo->addNews($form->getAllValues());
            $this->redirect('admin/news');
        }
        $mdInstructions = new MDInstructionsPageView($this);
        $view = new NewsEditPageView($this);
        $view->render(array(
                'formTitle' => 'Nueva Noticia',
                'form' => $form->renderView(),
                'md_instructions' => $mdInstructions->renderView()
            ));
    }

    public function actionAdminNewsEdit()
    {
        $this->firewall(
            WorldChampsApplication::ROLE_ORGANIZER,
            '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            func_num_args(),1,1, '\WorldChamps\View\Page\ExceptionDoesNotExistView'
        );
        $id = func_get_arg(0);
        $repo = new Repo\RepoNews($this);
        $news = $repo->getNewsById($id);
        if (!$news) $this->error('Esa noticia no existe.');
        $form = new Form\NewsForm($this,Form\NewsForm::EDIT, $news);
        if ($this->isPOST() && $form->isValid()) {
            $repo->editNews($id,$form->getAllValues());
            $this->redirect('admin/news');
        }
        $mdInstructions = new MDInstructionsPageView($this);
        $view = new NewsEditPageView($this);
        $view->render(array(
                'formTitle' => 'Editar Noticias',
                'form' => $form->renderView(),
                'md_instructions' => $mdInstructions->renderView()
            ));
    }

    public function actionAdminNewsDelete()
    {
        $this->firewall(
            WorldChampsApplication::ROLE_ORGANIZER,
            '\WorldChamps\View\Page\ExceptionDoesNotExistView',
            func_num_args(),1,1, '\WorldChamps\View\Page\ExceptionDoesNotExistView'
        );
        $id = func_get_arg(0);
        $repo = new Repo\RepoNews($this);
        $news = $repo->getNewsById($id);
        if (!$news) $this->error('Esa noticia no existe.');
        $form = new Form\NewsForm($this,Form\NewsForm::DELETE, $news);
        if ($this->isPOST() && $form->isValid()) {
            $repo->deleteNews($id);
            $this->redirect('admin/news');
        }
        $mdInstructions = new MDInstructionsPageView($this);
        $view = new NewsEditPageView($this);
        $view->render(array(
                'formTitle' => 'Eliminar Noticias',
                'form' => $form->renderView(),
                'md_instructions' => $mdInstructions->renderView()
            ));
    }

    // ************************************ AJAX **************************************************************

    public function actionAjaxStaffApply()
    {
       // die("ERROR"); // remove to allow staff application / updates
        if (
            !array_key_exists(Application::AUTH_ID,$_SESSION) ||
            func_num_args() ||
            !array_key_exists("introduction",$_POST) ||
            !array_key_exists("score_taking",$_POST) ||
            !array_key_exists("check_in",$_POST) ||
            !array_key_exists("wca_booth",$_POST) ||
            !array_key_exists("t_shirt_size",$_POST) ||
            !array_key_exists("day_18",$_POST) ||
            !array_key_exists("day_19",$_POST) ||
            !array_key_exists("day_20",$_POST) ||
            !array_key_exists("day_21",$_POST) ||
            !array_key_exists("day_22",$_POST)
        ) {
            die("ERROR");
        } else {
            $repo = new RepoStaff($this);
            $repo->apply();
            die("OK");
        }
    }

    public function actionAjaxStaffVote()
    {
        if (
            !array_key_exists(Application::AUTH_ID,$_SESSION) ||
            $_SESSION[Application::AUTH_ROLE] < WorldChampsApplication::ROLE_DELEGATE || // criterion to be eligible to vote
            func_num_args() ||
            !array_key_exists("type",$_POST) ||
            !array_key_exists("candidate_id",$_POST)
        ) {
            die("ERROR - Invalid params");
        } else {
            if ($_POST["candidate_id"] == $_SESSION[Application::AUTH_ID])
            {
                die(
                    $this->getVersionValue("language") == 'en' ?
                        "ERROR - You cannot vote for yourself"
                        :
                        "ERROR - No puedes votar por ti"
                );
            }
            $repo = new RepoStaff($this);
            die($repo->vote($_POST["type"],$_POST["candidate_id"]));
        }
    }

    public function actionAjaxStaffAccept()
    {
        if (
            !array_key_exists(Application::AUTH_ID,$_SESSION) ||
            $_SESSION[Application::AUTH_ROLE] < WorldChampsApplication::ROLE_ORGANIZER || // admin!
            func_num_args() ||
            !array_key_exists("candidate_id",$_POST)
        ) {
            die("ERROR - Invalid params");
        } else {
            $repo = new RepoStaff($this);
            die($repo->accept($_POST["candidate_id"]));
        }
    }

    public function actionAjaxStaffRemove()
    {
        if (
            !array_key_exists(Application::AUTH_ID,$_SESSION) ||
            $_SESSION[Application::AUTH_ROLE] < WorldChampsApplication::ROLE_ORGANIZER || // admin!
            func_num_args() ||
            !array_key_exists("candidate_id",$_POST)
        ) {
            die("ERROR - Invalid params");
        } else {
            $repo = new RepoStaff($this);
            die($repo->remove($_POST["candidate_id"]));
        }
    }

    public function actionAjaxStaffErase()
    {
        if (
            !array_key_exists(Application::AUTH_ID,$_SESSION) ||
            $_SESSION[Application::AUTH_ROLE] < WorldChampsApplication::ROLE_ORGANIZER || // admin!
            func_num_args() ||
            !array_key_exists("candidate_id",$_POST)
        ) {
            die("ERROR - Invalid params");
        } else {
            $repo = new RepoStaff($this);
            die($repo->erase($_POST["candidate_id"]));
        }
    }

    public function actionAjaxRefund()
    {
        if (
            !array_key_exists(Application::AUTH_ID,$_SESSION) ||
            $_SESSION[Application::AUTH_ROLE] < WorldChampsApplication::ROLE_ORGANIZER || // admin!
            func_num_args() ||
            !array_key_exists("user_id",$_POST) ||
            !array_key_exists("refund",$_POST)
        ) {
            die("ERROR - Invalid params");
        } else {
            $repo = new RepoAdmin($this);
            die($repo->refund($_POST["user_id"],$_POST["refund"]));
        }
    }
}
