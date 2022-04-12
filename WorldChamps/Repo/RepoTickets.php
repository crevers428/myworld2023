<?php

namespace WorldChamps\Repo;

use App\Application;
use App\Repo\Repo;
use WorldChamps\View\Page;

class RepoTickets extends Repo
{
    const MAX_COMPANION_TICKETS_PER_COMPETITOR = 2;
    const MAX_TICKETS_PER_DAY = 1000;
    const ONE_DAY_FEES = 5;
    const ALL_DAYS_FEES = 10;
    const DEADLINE = 1559483940; // mktime(23,59,0,6,2,2019) 11:59pm 2nd June 2019 AEST

    protected function openConnection()
    {
        if (!$this->opened) {
            parent::__openConnection(new WorldChampsDbConn($this->app));
        }
    }

    public function getTicketsByUser($user_id,&$has_tickets)
    {
        $this->openConnection();
        $result = $this->DB->query("SELECT SUM(d19) AS d19, SUM(d20) AS d20, SUM(d21) AS d21, SUM(d22) AS d22, SUM(all_days) AS all_days FROM tickets WHERE user_id=?",array($user_id));
        $result = $result[0];
        $return = array();
        $has_tickets = false;
        foreach ($result as $row) {
            if ($row) {
                $has_tickets = true;
            } else {
                $row = 0;
            }
            $return[] = 0+$row;
        }
        return $return;
    }

    public function checkOnSale()
    {
        // echo mktime(0,0,0,7,16,2018); die();
        if (time() >= RepoTickets::DEADLINE) {
            $language = $this->app->getVersionValue("language");
            $this->app->error(
                $language == 'en' ?
                    "We are sorry, but the tickets are not on sale at this moment. ".
                    "If you think this can be an error, <a href='{{link}}en/contact'>contact us</a>."
                    :
                    "Lo sentimos, pero en estos momentos las entradas no están a la venta. " .
                    "Si crees que esto puede ser un error, ponte en <a href='{{link}}es/contact'>contacto</a> con nosotros."
            );
        }
    }

    public function renderPayment()
    {
        $this->checkOnSale();

        $repo_usr = new RepoUsers($this->app);
        $user = $repo_usr->getUser(array($_SESSION[Application::AUTH_ID]));
        if (!$user) {
            throw new \Exception("User cannot be retrieved");
        }

        $this->openConnection();
        $registrations = $this->DB->query("SELECT id FROM registrations WHERE user_id=?",array($_SESSION[Application::AUTH_ID]));
        If (!count($registrations)) {
            throw new \Exception("You don't have registered to any event yet!");
        }

        $this->checkTotalAgainstTickets();

        include "__private_stripe__.inc";

        $total = $_POST["total"];
        $new_tickets = json_decode($_POST['tickets']);
        $view = new Page\TicketsPaymentPageView($this->app);
        $view->render(array(
                'tickets' => $_POST['tickets'],
                'd19' => $new_tickets[0],
                'd20' => $new_tickets[1],
                'd21' => $new_tickets[2],
                'd22' => $new_tickets[3],
                'alldays' => $new_tickets[4],
                'total' => $total,
                'totalx100' => round($total*100),
                'one_day_fees' => RepoTickets::ONE_DAY_FEES,
                'all_days_fees' => RepoTickets::ALL_DAYS_FEES,
                'publishable_key' => $stripe_publishable_key,
                'email' => $user["email"],
            ));
    }

    public function checkTotalAgainstTickets()
    {
        $total = $_POST['total'];
        $calculated_total = 0;
        $new_tickets = json_decode($_POST['tickets']);
        $cur_tickets = $this->getTicketsByUser($_SESSION[Application::AUTH_ID],$dummy);
        for ($i = 0; $i < 4; $i++) {
            if ($new_tickets[$i]+$cur_tickets[$i] + $new_tickets[4]+$cur_tickets[4] > RepoTickets::MAX_COMPANION_TICKETS_PER_COMPETITOR) {
                throw new \Exception("That exceeds the maximum number of companion tickets per competitor - operation aborted");
            }
            $calculated_total += $new_tickets[$i] * RepoTickets::ONE_DAY_FEES;
        }
        $calculated_total += $new_tickets[4] * RepoTickets::ALL_DAYS_FEES;
        if ($total <> $calculated_total) {
            throw new \Exception("The provided total and the calculated total don't match - operation aborted");
        }
        $this->checkTotalTickets($new_tickets);
    }

    public function checkTotalTickets($new_tickets)
    {
        $this->openConnection();
        $result = $this->DB->query("SELECT SUM(d19) AS d19, SUM(d20) AS d20, SUM(d21) AS d21, SUM(d22) AS d22, SUM(all_days) AS all_days FROM tickets");
        $result = $result[0];
        if (
            $result["d19"]+$result["all_days"]+$new_tickets[0]+$new_tickets[4] > RepoTickets::MAX_TICKETS_PER_DAY ||
            $result["d20"]+$result["all_days"]+$new_tickets[1]+$new_tickets[4] > RepoTickets::MAX_TICKETS_PER_DAY ||
            $result["d21"]+$result["all_days"]+$new_tickets[2]+$new_tickets[4] > RepoTickets::MAX_TICKETS_PER_DAY ||
            $result["d22"]+$result["all_days"]+$new_tickets[3]+$new_tickets[4] > RepoTickets::MAX_TICKETS_PER_DAY
        ) {
            throw new \Exception(
                $this->app->getVersionValue("language") == 'en' ?
                    "A permanent error prevents this operation from completion. " .
                    "Please, <a href='{{link}}en/contact'>contact us</a> to report it."
                    :
                    "Un error permanente impide que esta operación se complete. " .
                    "Por favor, ponte en <a href='{{link}}es/contact'>contacto</a> con nosotros."
            );
        }
    }
}
