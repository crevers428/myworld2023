<?php

namespace WorldChamps\Repo;

use App\Application;
use App\Repo\Repo;
use WorldChamps\WorldChampsApplication;
use WorldChamps\View\Page;
use WorldChamps\View;
use WorldChamps\Email;

class CompensationCharge
{
    public $id;
    public $amount;
    public $created;

    static function getToken($salt)
    {
        return 'wec_' . hash("md5", uniqid() . $salt);
    }

    public function __construct($id,$total)
    {
        $this->id = $id;
        $this->amount = round($total*100);
        $this->created = time();
    }
}

class RepoPayments extends Repo
{
    const commission = 1;
    const PAYM = "paym";
    const COMP = "comp";

    public function __construct($app)
    {
        parent::__construct($app);
        /*
        $this->addPermissions(Application::ROLE_USER, 'username','dni','fnac','direcc','local','prov','tlf');
        $this->addPermissions(Application::ROLE_ADMIN, 'nombre','caducidad','email','wca');
        */
    }

    protected function openConnection()
    {
        if (!$this->opened) {
            parent::__openConnection(new WorldChampsDbConn($this->app));
        }
    }

    protected function getEventArray($event)
    {
        return array(
            "id" => $event->id,
            "name" => $event->name,
            "paid_fee" => $event->signed_up ? $event->paid_fee : $event->price_now
        );
    }

    protected function checkTotalAgainstRegistration($withBase, &$extra_paid, &$extra_used)
    {
        $total = $_POST["total"];
        $new_registration = json_decode($_POST["registration"]);
        if (!$new_registration) {
            throw new \Exception("Invalid JSON");
        }

        $repo_reg = new RepoRegistration($this->app);
        $old_registration = $repo_reg->getRegistrationFromUser($_SESSION[Application::AUTH_ID],$dummy);

        $len = count($old_registration->events);
        $compatible_regs = (
            $old_registration->pricingLetter == $new_registration->pricingLetter &&
            $len == count($new_registration->events)
        );
        if ($compatible_regs) {
            $i = 0;
            while ($compatible_regs && $i < $len) {
                $compatible_regs = (
                    $old_registration->events[$i]->id == $new_registration->events[$i]->id &&
                    $old_registration->events[$i]->price_now == $new_registration->events[$i]->price_now &&
                    $old_registration->events[$i]->paid_fee == $new_registration->events[$i]->paid_fee &&
                    $old_registration->events[$i]->signed_up == $new_registration->events[$i]->signed_up
                );
                $i++;
            }
        }
        if (!$compatible_regs) {
            throw new \Exception("The payment parameters are incoherent with the current fees. " .
                "This is surely a temporary problem. Please try again.");
        }

        $calculated_total = 0;
        $myEvents = array();
        $i = 0;
        while ($i < $len) {
            $myEvt = null;
            if (property_exists($new_registration->events[$i],"checked") && $new_registration->events[$i]->checked) {
                if ($new_registration->events[$i]->signed_up) {
                    $calculated_total -= $old_registration->events[$i]->paid_fee;
                } else {
                    $calculated_total += $old_registration->events[$i]->price_now;
                    $myEvt = $this->getEventArray($new_registration->events[$i]);
                }
            } elseif ($new_registration->events[$i]->signed_up) {
                $myEvt = $this->getEventArray($new_registration->events[$i]);
            }
            if ($myEvt && ($i || $withBase)) {
                $myEvents[] = $myEvt;
            }
            $i++;
        }

        $repo_usr = new RepoUsers($this->app);
        $user = $repo_usr->getUser(array($_SESSION[Application::AUTH_ID]));
        if (!$user) {
            throw new \Exception("User cannot be retrieved");
        }
        $amount_credit = $user["amount_paid"]-$user["amount_used"];
        if ($calculated_total > 0) {
            $credit = min($amount_credit,$calculated_total);
        } else {
            $credit = 0;
        }

        // calculate differences in paid and used
        if ($calculated_total < 0) {
            $extra_paid = 0;
        } else {
            $extra_paid = $calculated_total-$credit;
        }
        $extra_used = $calculated_total;

        $calculated_total -= $credit;
        if ($calculated_total > 0 && $user["amount_paid"]) { // not first timer
            $calculated_total += RepoPayments::commission;
        }

        if ($calculated_total != $total) {
            throw new \Exception("The payment parameters are incoherent");
        }
 
        return $myEvents;
    }

    public function renderPayment()
    {
        $repo_reg = new RepoRegistration($this->app);
        $repo_reg->checkRegistrationOpen("Render payment is not possible");

        $repo_usr = new RepoUsers($this->app);
        $user = $repo_usr->getUser(array($_SESSION[Application::AUTH_ID]));
        if (!$user) {
            throw new \Exception("User cannot be retrieved");
        }

        $myEvents = $this->checkTotalAgainstRegistration(false,$dummy1,$dummy2);

        include "__private_stripe__.inc";

        $total = $_POST["total"];
        $view = new Page\PaymentPageView($this->app);
        $view->render(array(
            'events' => $myEvents,
            'total' => $total,
            'registration' => $_POST["registration"],
            'totalx100' => round($total*100),
            'publishable_key' => $stripe_publishable_key,
            'email' => $user["email"],
            'token' => CompensationCharge::getToken($this->app->secret),
        ));
    }

    protected function error($message)
    {
        $view = new Page\StripeError($this->app);
        $view->render(array(
                "message" => $message
            ));
    }

    public function chargePayment()
    {
        $repo_reg = new RepoRegistration($this->app);
        $repo_reg->checkRegistrationOpen("Charging is not possible");

        $language = $this->app->getVersionValue("language");
        $myEvents = $this->checkTotalAgainstRegistration(true,$extra_paid,$extra_used);

        $total = $_POST["total"];
        if ($total > 0) {

            require_once('../stripe-php-5.7.0/init.php');
            include "__private_stripe__.inc";

            \Stripe\Stripe::setApiKey($stripe_secret_key);

            $payment_type = RepoPayments::PAYM;
            $total_x_100 = round($total*100);
            try {
                $charge = \Stripe\Charge::create(array(
                        "amount" => $total_x_100,
                        "currency" => "aud",
                        "description" => "Registration payment for WCA World Championship 2019",
                        "source" => $_POST['stripeToken'],
                    ));
                /*
                } catch(\Stripe\Error\Card $e) {
                    $body = $e->getJsonBody();
                    $err  = $body['error'];
                    throw new \Exception(
                        sprintf(
                            "type=%s code=%s param=%s message=%s",
                            $err['type'],
                            $err['code'],
                            $err['param'],
                            $err['message']
                        )
                    );
                /*
                } catch (\Stripe\Error\RateLimit $e) {
                    // Too many requests made to the API too quickly
                } catch (\Stripe\Error\InvalidRequest $e) {
                    // Invalid parameters were supplied to Stripe's API
                } catch (\Stripe\Error\Authentication $e) {
                    // Authentication with Stripe's API failed
                    // (maybe you changed API keys recently)
                } catch (\Stripe\Error\ApiConnection $e) {
                    // Network communication with Stripe failed
                */
            } catch (\Stripe\Error\Base $e) {
                $body = $e->getJsonBody();
                $err  = $body['error'];
                $this->error($err["message"]);
                /*
                } catch (\Exception $e) {
                    // Something else happened, completely unrelated to Stripe
                */
            }

            if (
                // todo - livemode could be added when production is tested
                $charge->amount != $total_x_100 ||
                !$charge->paid ||
                $charge->status != "succeeded"
            ) {
                $this->error(
                    $language == 'en' ?
                        "The payment seemed to be done, but we don't agree that it was valid. " .
                        "<u>Do not try again</u> and " .
                        "<a href='{{link}}{{version-language}}/contact'>contact us</a> immediately!"
                        :
                        "Parece que el pago se realizó, pero no estamos de acuerdo en que sea válido. " .
                        "<u>No lo intentes de nuevo</u> y " .
                        "<a href='{{link}}{{version-language}}/contact'>ponte en contacto con nosotros</a> inmediatamente!"
                );
            }
        } else {
            $charge = new CompensationCharge($_POST['stripeToken'],$extra_used);
            $payment_type = RepoPayments::COMP;
        }

        $created = date(Repo::DATE_SQL_FORMAT,$charge->created);
        $this->addPayment(
            $_SESSION[Application::AUTH_ID],
            $charge->id,
            $payment_type,
            $charge->amount,
            $created
        );
		
		$reg = $_POST['registration'];
		$res = substr($reg, strpos($reg, "^^") + 2);
		$res_final = substr($res, 0, strpos($res, "\"}"));
		
		$this->openConnection();
		
		$this->DB->query(
			"INSERT INTO residencies (user_id, residency) VALUES (?, ?)",
			array($_SESSION[Application::AUTH_ID],$res_final)
		);

        $this->updateUser($extra_paid,$extra_used);

        $this->addRegistrations($myEvents);

        $this->app->addPostIt(Application::POSTIT_SUCCESS,
            $language == 'en' ?
                "Your payment was successfully done!"
                :
                "¡Tu pago se ha completado correctamente!"
        );
        $this->app->redirect("/$language/myworlds");
    }

    public function chargeTickets()
    {
        $repo_tickets = new RepoTickets($this->app);
        $repo_tickets->checkOnSale();

        $language = $this->app->getVersionValue("language");

        $this->openConnection();
        $registrations = $this->DB->query("SELECT id FROM registrations WHERE user_id=?",array($_SESSION[Application::AUTH_ID]));
        If (!count($registrations)) {
            throw new \Exception("You don't have registered to any event yet!");
        }

        $repo_tickets->checkTotalAgainstTickets();

        require_once('../stripe-php-5.7.0/init.php');
        include "__private_stripe__.inc";

        \Stripe\Stripe::setApiKey($stripe_secret_key);

        $total = $_POST["total"];
        $total_x_100 = round($total*100);
        $tickets = json_decode($_POST["tickets"]);
        try {
            $charge = \Stripe\Charge::create(array(
                    "amount" => $total_x_100,
                    "currency" => "aud",
                    "description" => "Entry payment for WCA World Championship 2019",
                    "source" => $_POST['stripeToken'],
                ));
            /*
            } catch(\Stripe\Error\Card $e) {
                $body = $e->getJsonBody();
                $err  = $body['error'];
                throw new \Exception(
                    sprintf(
                        "type=%s code=%s param=%s message=%s",
                        $err['type'],
                        $err['code'],
                        $err['param'],
                        $err['message']
                    )
                );
            /*
            } catch (\Stripe\Error\RateLimit $e) {
                // Too many requests made to the API too quickly
            } catch (\Stripe\Error\InvalidRequest $e) {
                // Invalid parameters were supplied to Stripe's API
            } catch (\Stripe\Error\Authentication $e) {
                // Authentication with Stripe's API failed
                // (maybe you changed API keys recently)
            } catch (\Stripe\Error\ApiConnection $e) {
                // Network communication with Stripe failed
            */
        } catch (\Stripe\Error\Base $e) {
            $body = $e->getJsonBody();
            $err  = $body['error'];
            $this->error($err["message"]);
            /*
            } catch (\Exception $e) {
                // Something else happened, completely unrelated to Stripe
            */
        }

        if (
            // todo - livemode could be added when production is tested
            $charge->amount != $total_x_100 ||
            !$charge->paid ||
            $charge->status != "succeeded"
        ) {
            $this->error(
                $language == 'en' ?
                    "The payment seemed to be done, but we don't agree that it was valid. " .
                    "<u>Do not try again</u> and " .
                    "<a href='{{link}}{{version-language}}/contact'>contact us</a> immediately!"
                    :
                    "Parece que el pago se realizó, pero no estamos de acuerdo en que sea válido. " .
                    "<u>No lo intentes de nuevo</u> y " .
                    "<a href='{{link}}{{version-language}}/contact'>ponte en contacto con nosotros</a> inmediatamente!"
            );
        }

        $created = date(Repo::DATE_SQL_FORMAT,$charge->created);
        $this->DB->query(
            "INSERT INTO tickets SET id=?, d19=?, d20=?, d21=?, d22=?, all_days=?, amount=?, created=?, user_id=?",
            array($charge->id,$tickets[0],$tickets[1],$tickets[2],$tickets[3],$tickets[4],$charge->amount,$created,$_SESSION[Application::AUTH_ID])
        );

        $this->app->addPostIt(Application::POSTIT_SUCCESS,
            $language == 'en' ?
                "Your payment was successfully done!"
                :
                "¡Tu pago se ha completado correctamente!"
        );
        $this->app->redirect("/$language/myworlds");
    }

    public function addPayment($user_id,$id,$type,$amount,$created)
    {
        $repo_reg = new RepoRegistration($this->app);
        if ($type == RepoPayments::PAYM) $repo_reg->checkRegistrationOpen("Adding a payment is not possible");

        if (!$amount) return;

        $this->openConnection();
        if ($type == RepoPayments::PAYM) {
            $result = $this->DB->query("SELECT id FROM payments WHERE user_id=?",array($user_id));
            if (count($result)) { // not first timer
                $commission_x_100 = round(RepoPayments::commission * 100);
                $amount -= $commission_x_100;
            } else {
                $commission_x_100 = 0;
            }
        } else {
            $commission_x_100 = 0;
        }
        $this->DB->query(
            "INSERT INTO payments (id, type, amount, our_commission, created, user_id) VALUES (?, ?, ?, ?, ?, ?)",
            array($id,$type,$amount,$commission_x_100,$created,$user_id)
        );
    }

    protected function updateUser($extra_paid,$extra_used)
    {
        $this->openConnection();
        $this->DB->query(
            "UPDATE users SET amount_paid=amount_paid+?, amount_used=amount_used+? WHERE id=?",
            array($extra_paid,$extra_used,$_SESSION[Application::AUTH_ID])
        );
    }

    protected function addRegistrations($myEvents)
    {
        $this->openConnection();
        $this->DB->query("DELETE FROM registrations WHERE user_id=?",array($_SESSION[Application::AUTH_ID]));

        $i = 0;
        $len = count($myEvents);
        while ($i < $len) {
            $this->DB->query(
                "INSERT INTO registrations (user_id, event_id, paid_fee) VALUES (?, ?, ?)",
                array(
                    $_SESSION[Application::AUTH_ID],
                    $myEvents[$i]["id"],
                    $myEvents[$i]["paid_fee"]
                )
            );
            $i++;
        }

        $this->DB->query(
            "DELETE FROM staff_events WHERE user_id=? AND type='w' AND event_id NOT IN " .
            "(SELECT event_id FROM registrations WHERE user_id=?)",
            array(
                $_SESSION[Application::AUTH_ID],
                $_SESSION[Application::AUTH_ID],
            )
        );

	// email to confirm registration (new competitors)
	if ($len > 0) {

            $repo_usr = new RepoUsers($this->app);
            $user = $repo_usr->getUser(array($_SESSION[Application::AUTH_ID]));

            $sender = new Email\Email($this);
            $emailView = new View\Email\RepoPaymentEmailView($this->app);
            $sender->send(
                true, // $this->app->isProd(),
                $user["email"],
                $sender::wcEmail,
                $sender::wcEmail,
                null,
                "Registration",
                $emailView->renderView(array(
                        'name' => $user["name"],
                    ))
            );
	}

        Page\CompetitorsPageView::deleteCacheFile($this->app);
        Page\PsychsheetPageView::deleteCacheFile($this->app);
    }

    public function getPayments(&$paid_totals,&$comm_totals)
    {
        $this->openConnection();
        $results = $this->DB->query(
            "SELECT ROUND(amount/100,2) AS amount, ROUND(our_commission/100,2) AS our_commission, created, id, type FROM payments WHERE user_id=? ORDER BY created DESC",
            array($_SESSION[Application::AUTH_ID])
        );
        if (!count($results)) return null;
        $paid_totals = 0;
        $comm_totals = 0;
        foreach ($results as $row) {
            if ($row["type"] == RepoPayments::PAYM) {
                $paid_totals += $row["amount"];
                $comm_totals += $row["our_commission"];
            }
        }
        $paid_totals = sprintf("%.2f",$paid_totals);
        $comm_totals = sprintf("%.2f",$comm_totals);
        /*
        $results[] = array(
            "amount" => "<strong>".sprintf("%.2f",$paid_totals)."</strong>",
            "our_commission" => sprintf("%.2f",$comm_totals),
            "created" => "PAID TOTALS",
            "type" => null,
            "id" => null
        );
        */
        return $results;
    }

    public function getAdminPayments(&$totals)
    {
        $this->openConnection();
        $results = $this->DB->query(
            "SELECT id, name, email, amount_paid, amount_used, amount_paid-amount_used AS credit " .
            "FROM users " .
            "WHERE amount_paid ORDER BY name"
        );
        $total_used = 0;
        $total_paid = 0;
        $total_credit = 0;
        $total_h_paid = 0;
        $total_h_credit = 0;
        $total_h_commission = 0;
        foreach($results as &$payment) {
            // history of transactions
            $history = $this->DB->query(
                "SELECT type, amount, our_commission FROM payments WHERE user_id=? ORDER BY created",
                array($payment["id"])
            );
            $paid = 0;
            $credit = 0;
            $commission = 0;
            foreach ($history as $transaction) {
                if ($transaction["type"] == RepoPayments::PAYM) {
                    $credit = 0;
                    $paid += $transaction["amount"];
                } else {
                    $credit -= $transaction["amount"];
                }
                $commission += $transaction["our_commission"];
            }
            $payment["history_paid"] = $paid / 100;
            $payment["history_credit"] = $credit / 100;
            $payment["history_commission"] = $commission / 100;
            $payment["error"] = ($payment["history_paid"] <> $payment["amount_paid"] || $payment["history_credit"] <> $payment["credit"]);
            // acc totals
            $total_paid += $payment["amount_paid"];
            $total_used += $payment["amount_used"];
            $total_credit += $payment["credit"];
            $total_h_paid += $payment["history_paid"];
            $total_h_credit += $payment["history_credit"];
            $total_h_commission += $payment["history_commission"];
        }
        $totals = array();
        $totals[] = array("count" => count($results)." payers");
        $totals[] = array("count" => $total_paid);
        $totals[] = array("count" => $total_used);
        $totals[] = array("count" => $total_credit);
        $totals[] = array("count" => $total_h_paid);
        $totals[] = array("count" => $total_h_credit);
        $totals[] = array("count" => $total_h_commission);
        return $results;
    }

    public function getAdminTickets(&$totals,&$turnout)
    {
        $this->openConnection();
        $tickets = $this->DB->query(
            "SELECT SUM(d19) AS d19, SUM(d20) AS d20, SUM(d21) AS d21, SUM(d22) AS d22, SUM(all_days) AS all_days, SUM(amount DIV 100) AS amount, user_id, users.name, users.email FROM tickets " .
            "JOIN users ON users.id=user_id " .
            "GROUP BY user_id " .
            "ORDER BY name"
        );
        foreach($tickets as &$row) {
            $row["error"] =
                (($row["d19"]+$row["d20"]+$row["d21"]+$row["d22"]) * RepoTickets::ONE_DAY_FEES) +
                ($row["all_days"] * RepoTickets::ALL_DAYS_FEES) != $row["amount"];
        }

        $totals = $this->DB->query(
            "SELECT 0 AS counter, SUM(d19) AS d19, SUM(d20) AS d20, SUM(d21) AS d21, SUM(d22) AS d22, SUM(all_days) AS all_days, SUM(amount DIV 100) AS amount FROM tickets"
        );
        $totals[0]['counter'] = count($tickets).' payers';

        $turnout = array(
            array(
                'd19' => $totals[0]['d19']+$totals[0]['all_days'],
                'd20' => $totals[0]['d20']+$totals[0]['all_days'],
                'd21' => $totals[0]['d21']+$totals[0]['all_days'],
                'd22' => $totals[0]['d22']+$totals[0]['all_days'],
            )
        );

        return $tickets;
    }
}
