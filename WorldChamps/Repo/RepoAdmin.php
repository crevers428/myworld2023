<?php

namespace WorldChamps\Repo;

use App\Repo\Repo;
use WorldChamps\View\Page;
use WorldChamps\View;
use WorldChamps\Email;
use WorldChamps\View\Page\AdminNonQualifiedPageView;
use WorldChamps\View\Page\AdminReimbursementsPageView;
use WorldChamps\View\Page\AdminTshirtsPageView;

class WCIF_Registration {

    public function __construct($events)
    {
        $this->eventIds = $events;
        $this->status = "accepted";
    }
}

class WCIF_Extensions {

    public function __construct($events_s,$events_n,$events_w,$days,$t_shirt_size,$score_taking,$check_in,$wca_booth)
    {
        $this->events_s = $events_s;
        $this->events_n = $events_n;
        $this->events_w = $events_w;
        $this->days = $days;
        $this->t_shirt_size = $t_shirt_size;
        $this->score_taking = $score_taking;
        $this->check_in = $check_in;
        $this->wca_booth = $wca_booth;
    }

}

class WCIF_PersonalBest {

    public function __construct($eventId,$best,$worldRanking,$continentalRanking,$nationalRanking,$type)
    {
        $this->eventId = $eventId;
        $this->best = $best;
        $this->worldRanking = $worldRanking;
        $this->continentalRanking = $continentalRanking;
        $this->nationalRanking = $nationalRanking;
        $this->type = $type;
    }
}

class WCIF_Person {

    public function __construct(
        $registrantId,$name,$wcaUserId,$wcaId,$countryIso2,$gender,$birthdate,$email,
        $roles,$events,$events_s,$events_n,$events_w,$days, $personalBests,
        $t_shirt_size,$score_taking,$check_in,$wca_booth
    )
    {
        $this->registrantId = $registrantId;
        $this->name = $name;
        $this->wcaUserId = $wcaUserId;
        $this->wcaId = $wcaId;
        $this->countryIso2 = $countryIso2;
        $this->gender = $gender;
        $this->birthdate = $birthdate;
        $this->email = $email;
        $this->roles = $roles;
        $this->registration = new WCIF_Registration($events);
        $this->extensions = new WCIF_Extensions($events_s,$events_n,$events_w,$days,
                                                $t_shirt_size,$score_taking,$check_in,$wca_booth);
        $this->personalBests = $personalBests;
    }
}

class WCIF {

    public function __construct($id,$name,$shortName)
    {
        $this->formatVersion = "1.0";
        $this->id = $id;
        $this->name = $name;
        $this->shortName = $shortName;
        $this->persons = array();
    }

    public function addPerson(
        $registrantId,$name,$wcaUserId,$wcaId,$countryIso2,$gender,$birthdate,$email,
        $roles,$events,$events_s,$events_n,$events_w,$days, $personalBests,
        $t_shirt_size,$score_taking,$check_in,$wca_booth
    )
    {
        $this->persons[] = new WCIF_Person(
            $registrantId,$name,$wcaUserId,$wcaId,$countryIso2,$gender,$birthdate,$email,
            $roles,$events,$events_s,$events_n,$events_w,$days, $personalBests,
            $t_shirt_size,$score_taking,$check_in,$wca_booth
        );
    }
}

class RepoAdmin extends Repo
{
    const _IMPORT_URL_PATH = "https://www.worldcubeassociation.org/results/misc/";
    const _IMPORT_URL = "https://www.worldcubeassociation.org/results/misc/export.html";
    const _IMPORT_DIR = '../import/';
    const _NEEDLE_A = "SQL: <a href='";
    const _NEEDLE_B = "'";
    const NO_REFUNDABLE_BASE = 70;

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

    // ------------- import - dirty part begins :P

    private function echo_time_consumed($t)
    {
        echo "<b>".sprintf("%.2f", microtime(true)-$t)." seconds</b> consumed.<p>";
    }

    private function get_page($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    private function download($url,$fname)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        $fp = fopen($fname, 'w+');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60*5);
        curl_exec ($ch);
        curl_close ($ch);
        fclose($fp);
    }

    public function importWCA()
    {
        $t0 = microtime(true);

        // STEP 1 - Guess the ZIP file to download

        $t1 = microtime(true);
        echo "Guessing the ZIP file name to download... ";
        $content = $this->get_page(RepoAdmin::_IMPORT_URL);
        if (!$content)
            die("<b>".RepoAdmin::_IMPORT_URL."</b> returned no content!");
        $a = strpos($content,RepoAdmin::_NEEDLE_A);
        $b = strpos($content,"'",$a+strlen(RepoAdmin::_NEEDLE_A));
        if (!$a || !$b)
            die("Link to ZIP file couldn't be found!");
        $a += strlen(RepoAdmin::_NEEDLE_A);
        $link = substr($content,$a,$b-$a);
        echo $link."<br>";
        $this->echo_time_consumed($t1);

        // STEP 2 - Download the file

        if (!file_exists(RepoAdmin::_IMPORT_DIR)) {
            mkdir(RepoAdmin::_IMPORT_DIR,0777,true);
        }

        $t1 = microtime(true);
        echo "Downloading <b>$link</b>... ";
        $this->download(RepoAdmin::_IMPORT_URL_PATH.$link, RepoAdmin::_IMPORT_DIR.$link);
        $this->echo_time_consumed($t1);

        // STEP 3 - Unzip file

        $t1 = microtime(true);
        echo "Unzipping SQL file from <b>$link</b>... ";
        $zip = new \ZipArchive;
        $res = $zip->open(RepoAdmin::_IMPORT_DIR.$link);
        if ($res === TRUE)
        {
            $zip->extractTo(RepoAdmin::_IMPORT_DIR);
            $zip->close();
        }
        else
            die("Couldn't unzip file!");
        $zip = null;
        $this->echo_time_consumed($t1);

        // STEP 4 - Import SQL file

        $t1 = microtime(true);
        echo "<b>Importing SQL file</b> to your database... ";
        $app = $this->app;
        include '__private__.inc'; // not include_once!
        if ($this->app->isProd()) {
            system('mysql --default-character-set=utf8 -u '.$user.' -p\''.$pass.'\' '.$DBName.' < '.RepoAdmin::_IMPORT_DIR.'WCA_export.sql');
        } else {
            system('mysql --default-character-set=utf8 -u '.$user.' -p\''.$pass.'\' '.$DBName.' < '.RepoAdmin::_IMPORT_DIR.'WCA_export.sql');
        }
        $this->echo_time_consumed($t1);

        // STEP 5 - Indexing

        $t1 = microtime(true);
        echo "<b>Indexing</b> the tables... ";
        $this->openConnection();

        $this->DB->query("DROP TABLE championships");
        $this->DB->query("DROP TABLE Competitions");
        $this->DB->query("DROP TABLE Continents");
        $this->DB->query("DROP TABLE eligible_country_iso2s_for_championship");
        $this->DB->query("DROP TABLE Events");
        $this->DB->query("DROP TABLE Formats");
        $this->DB->query("DROP TABLE Persons");
        $this->DB->query("DROP TABLE Results");
        $this->DB->query("DROP TABLE Rounds");
        $this->DB->query("DROP TABLE RoundTypes");
        $this->DB->query("DROP TABLE Scrambles");

        $this->DB->query("ALTER TABLE RanksSingle ADD INDEX (personId,eventId)");
        $this->DB->query("ALTER TABLE RanksAverage ADD INDEX (personId,eventId)");
        $this->DB->query("ALTER TABLE Countries ADD PRIMARY KEY (id), ADD INDEX(iso2)");
        // $this->DB->query('ALTER TABLE RanksSingle CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci');
        // $this->DB->query('ALTER TABLE RanksAverage CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci');

        $this->echo_time_consumed($t1);

        // STEP 6 - Clean

        $t1 = microtime(true);
        echo "<b>Deleting</b> temporary files... ";
        if (file_exists(RepoAdmin::_IMPORT_DIR.$link)) unlink(RepoAdmin::_IMPORT_DIR.$link);
        if (file_exists(RepoAdmin::_IMPORT_DIR."metadata.json")) unlink(RepoAdmin::_IMPORT_DIR."metadata.json");
        if (file_exists(RepoAdmin::_IMPORT_DIR."README.md")) unlink(RepoAdmin::_IMPORT_DIR."README.md");
        if (file_exists(RepoAdmin::_IMPORT_DIR."WCA_export.sql")) unlink(RepoAdmin::_IMPORT_DIR."WCA_export.sql");
        $this->echo_time_consumed($t1);

        // END

        echo "Overall: ";
        $this->echo_time_consumed($t0);

        Page\CompetitorsPageView::deleteCacheFile($this->app);
        Page\PsychsheetPageView::deleteCacheFile($this->app);
    }

    public function credentials()
    {
        $credentialWidth = 99;
        $credentialHeight = 63;
        $pageWidth = $credentialWidth*2;
        $pageHeight = $credentialHeight*4;
        $numOfColumns = floor($pageWidth / $credentialWidth);
        $numOfRows = floor($pageHeight / $credentialHeight);

        require_once("../TCPDF/tcpdf.php");
        // require_once("../TCPDF/include/tcpdf_fonts.php");

        // create new PDF document
        // $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf = new \TCPDF('p', 'mm', array($pageWidth,$pageHeight), true, 'UTF-8', false);
        // \TCPDF_FONTS::addTTFfont('../TCPDF/fonts/Montserrat.ttf', 'TrueTypeUnicode', '', 32);
        /*
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Nicola Asuni');
        $pdf->SetTitle('TCPDF Example 001');
        $pdf->SetSubject('TCPDF Tutorial');
        $pdf->SetKeywords('TCPDF, PDF, example, test, guide');
        */
        /*
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 001', PDF_HEADER_STRING, array(0,64,255), array(0,64,128));
        $pdf->setFooterData(array(0,64,0), array(0,64,128));
        */
        /*
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        */
        /*
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        */
        /*
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        */
        // set auto page breaks
        $pdf->SetAutoPageBreak(false);

        /*
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        */
        /*
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        */

        // --------------------------------------------------------

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setFontSubsetting(true);
        //$pdf->SetFont('arialuni', '', 12, '', true);
        $pdf->SetFont('Montserrat', '', 12, '', true);

        $this->openConnection();
        $results = $this->DB->query(
            "SELECT users.name, wca_id, Countries.name AS country, iso2, approved, orga_team " .
            "FROM users " .
            "LEFT JOIN Countries ON iso2=country_iso2 " .
            "LEFT JOIN staff ON staff.user_id=users.id " .
            "WHERE users.id IN (SELECT DISTINCT user_id FROM registrations) OR " .
            "      users.id IN (SELECT user_id FROM staff WHERE approved) " .
            "ORDER BY name"
        );
        $col = 0;
        $row = 0;
        foreach ($results as $person) {

            if (!$col && !$row) $pdf->AddPage();

            $parenthesis_a = strpos($person['name'],"(");
            if ($parenthesis_a === false) {
                $localName = $person['name'];
                $otherName = '';
            } else {
                $parenthesis_b = strpos($person['name'],")");
                $localName = substr($person['name'],$parenthesis_a+1,$parenthesis_b-$parenthesis_a-1);
                $otherName = substr($person['name'],0,$parenthesis_a-1);
            }

            $pdf->Image('../TCPDF/flags/bg_'.($person["orga_team"]?'coreteam':($person['approved']?'staff':'competitor')).'.png',
                $col*$credentialWidth+0,$row*$credentialHeight+0,$credentialWidth,$credentialHeight
            );
            $nameLen = strlen($localName);
            $pdf->SetFontSize($nameLen>40?12:($nameLen>35?14:16));
            $pdf->SetTextColor(0);
            $pdf->SetXY($col*$credentialWidth+0,$row*$credentialHeight+($nameLen>40?8.5:($nameLen>35?8:7.6)));
            $pdf->cell(82,0, $localName,0,0,'C',false,'',3);

            $pdf->Image('../TCPDF/flags/'.strtolower($person['iso2']).'.png',
                $col*$credentialWidth+82,$row*$credentialHeight+7.8,0,7.2
            );

            $pdf->SetFontSize(14);
            if ($person["approved"]) $pdf->SetTextColor(255);
            $pdf->SetXY($col*$credentialWidth+0,$row*$credentialHeight+15);
            $pdf->cell(82,0, $otherName,0,0,'C',false,'',3);

            $pdf->SetXY($col*$credentialWidth+0,$row*$credentialHeight+22);
            $pdf->cell($credentialWidth,0, $person['country'],0,0,'C',false,'',3);

            $pdf->SetFontSize(8);
            $pdf->SetXY($col*$credentialWidth+0,$row*$credentialHeight+28);
            $pdf->cell($credentialWidth,0, $person['wca_id'],0,0,'C');

            $col = ($col + 1) % $numOfColumns;
            if (!$col) $row = ($row + 1) % $numOfRows;
        }

        $pdf->Output('WCA World Championship 2019 - Credentials', 'I');
    }

    public function CheckInList()
    {
        $rowHeight = 10;
        $marginLeft = $marginTop = 10;
        $pageWidth = 210;
        $pageHeight = 297;

        require_once("../TCPDF/tcpdf.php");
        // require_once("../TCPDF/include/tcpdf_fonts.php");

        // create new PDF document
        // $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf = new \TCPDF('p', 'mm', array($pageWidth,$pageHeight), true, 'UTF-8');
        // \TCPDF_FONTS::addTTFfont('../TCPDF/fonts/Montserrat.ttf', 'TrueTypeUnicode', '', 32);
        /*
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Nicola Asuni');
        $pdf->SetTitle('TCPDF Example 001');
        $pdf->SetSubject('TCPDF Tutorial');
        $pdf->SetKeywords('TCPDF, PDF, example, test, guide');
        */
        /*
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 001', PDF_HEADER_STRING, array(0,64,255), array(0,64,128));
        $pdf->setFooterData(array(0,64,0), array(0,64,128));
        */
        /*
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        */
        /*
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        */
        /*
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        */
        // set auto page breaks
        $pdf->SetAutoPageBreak(false);

        /*
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        */
        /*
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        */

        // --------------------------------------------------------

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setFontSubsetting(true);
        $pdf->SetFont('arialuni', '', 12, '', true);
        //$pdf->SetFont('Montserrat', '', 12, '', true);

        $this->openConnection();
        $results = $this->DB->query(
            "SELECT users.name, wca_id, dob, Countries.name AS country, iso2, approved,
               IF(approved,IF(t_shirt_size = '','?',t_shirt_size),'') AS t_shirt_size,
               IF(approved,day_19+day_20+day_21+day_22,'') AS t_shirt_qty,
               SUM(d19) AS d19, SUM(d20) AS d20, SUM(d21) AS d21, SUM(d22) AS d22, SUM(all_days) AS all_days
            FROM users
            LEFT JOIN Countries ON iso2=country_iso2
            LEFT JOIN staff ON staff.user_id=users.id
            LEFT JOIN tickets ON tickets.user_id=users.id
            WHERE users.id IN (SELECT DISTINCT user_id FROM registrations) OR
                  users.id IN (SELECT user_id FROM staff WHERE approved)
            GROUP BY name, wca_id, dob, country, iso2, approved, t_shirt_size, day_19, day_20, day_21, day_22
            ORDER BY name"
        );
        $row = 0;
        foreach ($results as $person) {

            if (!$row) $pdf->AddPage();

            if (!$person["wca_id"]) {
                $pdf->Rect($marginLeft, $marginTop+$rowHeight*$row, 90, 10, 'F',null,array(255,128,128));
            }

            $pdf->SetTextColor(0);
            $pdf->Line($marginLeft,$marginTop+$rowHeight*$row,$pageWidth-$marginLeft,$marginTop+$rowHeight*$row);
            $pdf->Line($marginLeft,$marginTop+$rowHeight*$row,$marginLeft,$marginTop+$rowHeight*$row+10);
            $pdf->SetXY($marginLeft,$marginTop+$rowHeight*$row);
            $pdf->SetFontSize(12);
            $pdf->cell(90,0, $person["name"],0,0,'L',false,'',3);

            $pdf->SetXY($marginLeft,$marginTop+$rowHeight*$row+5);
            $details = $person["wca_id"];
            if ($details) $details .= " - ";
            $details .= $person["dob"] . " - " . $person["country"];
            $pdf->SetFontSize(10);
            $pdf->cell(90,0, $details,0,0,'L',false,'',3);

            $xOffset = 90;
            $pdf->SetFontSize(14);
            foreach (array("t_shirt_size","d19","d20","d21","d22","all_days") as $column) {
                $pdf->Line($marginLeft+$xOffset,$marginTop+$rowHeight*$row,$marginLeft+$xOffset,$marginTop+$rowHeight*$row+10);
                if ($person[$column]) {
                    switch ($xOffset) {
                        case 90:
                            $pdf->SetFillColor(255);
                            break;
                        case 100:
                            $pdf->SetFillColor(hexdec('4a'),hexdec('a8'),hexdec('4e'));
                            break;
                        case 110:
                            $pdf->SetFillColor(hexdec('f8'),hexdec('eb'),hexdec('40'));
                            break;
                        case 120:
                            $pdf->SetFillColor(hexdec('fe'),hexdec('71'),hexdec('1d'));
                            break;
                        case 130:
                            $pdf->SetFillColor(hexdec('0b'),hexdec('52'),hexdec('c2'));
                            break;
                        case 140:
                            $pdf->SetFillColor(hexdec('ff'),hexdec('ff'),hexdec('ff'));
                    }
                    $pdf->SetXY($marginLeft+$xOffset+0.1,$marginTop+$rowHeight*$row+0.1);
                    if ($column == "t_shirt_size") {
                        $pdf->cell(9.8,9.8, $person["t_shirt_qty"].$person[$column],0,0,'C',true,'',3);
                    } else {
                        $pdf->cell(9.8,9.8, $person[$column],0,0,'C',true,'',3);
                    }
                }
                $xOffset += 10;
            }

            $pdf->Line($marginLeft+$xOffset,$marginTop+$rowHeight*$row,$marginLeft+$xOffset,$marginTop+$rowHeight*$row+10);
            $pdf->Line($pageWidth-$marginLeft,$marginTop+$rowHeight*$row,$pageWidth-$marginLeft,$marginTop+$rowHeight*$row+10);
            $pdf->SetFontSize(6);
            $pdf->SetTextColor(200);
            $pdf->SetXY($marginLeft+150,$marginTop+$rowHeight*$row+7);
            $pdf->cell(40,3, $person["name"],0,0,'C',false,'',3);

            $row += 1;
            if ($row == 28) {
                $pdf->Line($marginLeft,$marginTop+$rowHeight*$row,$pageWidth-$marginLeft,$marginTop+$rowHeight*$row);
                $row = 0;
            }
        }
        $pdf->Line($marginLeft,$marginTop+$rowHeight*$row,$pageWidth-$marginLeft,$marginTop+$rowHeight*$row);

        $pdf->Output('WCA World Championship 2019 - Check-in List', 'I');
    }

    public function wcif()
    {
        header('Content-disposition: attachment; filename="WCA World Championship 2019 - WCIF.json"');
        header('Content-type: application/json; charset=utf-8');

        $obj = new WCIF("WC2019","WCA World Championship 2019","WCA World Championship 2019");

        $this->openConnection();
        $results = $this->DB->query(
            "SELECT id, users.name, wca_id, country_iso2, gender, dob, email, orga_team, approved, day_19, day_20, day_21, day_22, score_taking, check_in, wca_booth, t_shirt_size " .
            "FROM users " .
            "LEFT JOIN staff ON staff.user_id=users.id " .
            "WHERE users.id IN (SELECT DISTINCT user_id FROM registrations) OR " .
            "      users.id IN (SELECT user_id FROM staff WHERE approved) " .
            "ORDER BY name"
        );
        $obj->persons = array();
        foreach ($results as $person) {

            $events_and_pb = $this->DB->query(
                "SELECT event_id, best, worldRank, continentRank, countryRank FROM registrations " .
                "JOIN events ON events.id=event_id ".
                "LEFT JOIN RanksAverage ON RanksAverage.personId=? AND RanksAverage.eventId=registrations.event_id " .
                "WHERE user_id=? AND real_event " .
                "ORDER BY rank",
                array($person["wca_id"],$person["id"])
            );
            $events = $this->results_to_array($events_and_pb,"event_id");

            $personalBests = array();
            foreach ($events_and_pb as $evt) {
                if ($evt["best"]) {
                    $personalBests[] = new WCIF_PersonalBest(
                        $evt["event_id"],
                        $evt["best"],
                        $evt["worldRank"],
                        $evt["continentRank"],
                        $evt["countryRank"],
                        "average"
                    );
                }
            }

            if (!$person["orga_team"] && $person["approved"]) {

                $events_s = $this->DB->query(
                    "SELECT event_id FROM staff_events " .
                    "JOIN events ON events.id=event_id " .
                    "WHERE user_id=? AND type=? " .
                    "ORDER BY rank",
                    array($person["id"],"s")
                );
                $events_s = $this->results_to_array($events_s,"event_id");

                $events_n = $this->DB->query(
                    "SELECT event_id FROM staff_events " .
                    "JOIN events ON events.id=event_id " .
                    "WHERE user_id=? AND type=? " .
                    "ORDER BY rank",
                    array($person["id"],"n")
                );
                $events_n = $this->results_to_array($events_n,"event_id");

                $events_w = $this->DB->query(
                    "SELECT event_id FROM staff_events " .
                    "JOIN events ON events.id=event_id " .
                    "WHERE user_id=? AND type=? " .
                    "ORDER BY rank",
                    array($person["id"],"w")
                );
                $events_w = $this->results_to_array($events_w,"event_id");

                $days = array();
                if ($person["day_19"]) $days[] = "0";
                if ($person["day_20"]) $days[] = "1";
                if ($person["day_21"]) $days[] = "2";
                if ($person["day_22"]) $days[] = "3";

            } else {
                $events_s = array();
                $events_n = array();
                $events_w = array();
                $days = array();
            }

            $roles = array();
            if ($person["approved"]) $roles[] = "staff";
            if ($person["orga_team"]) $roles[] = "organization";

            $obj->addPerson(
                $person["id"],
                $person["name"],
                $person["id"],
                $person["wca_id"],
                $person["country_iso2"],
                $person["gender"],
                $person["dob"],
                $person["email"],
                $roles,
                $events,
                $events_s,
                $events_n,
                $events_w,
                $days,
                $personalBests,
                $person["t_shirt_size"],
                $person["score_taking"],
                $person["check_in"],
                $person["wca_booth"]
            );
        }

        echo json_encode($obj,JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function csv()
    {
        header('Content-disposition: attachment; filename="WCA World Championship 2019 - Registration.csv"');
        header('Content-type: text/csv; charset=utf-8');

        $captions = array("Status","Name","Country","WCA ID","Birth Date","Gender","Email");

        $this->openConnection();
        $events = $this->DB->query("SELECT id FROM events WHERE real_event ORDER BY rank");
        $events = $this->results_to_array($events,"id");
        $captions = array_merge($captions,$events);

        $eventColumns = "";
        foreach ($events as $evt) {
            $eventColumns .= ", IF((SELECT user_id FROM registrations WHERE user_id=users.id AND event_id='$evt'),1,0)";
        }

        $results = $this->DB->query(
            "SELECT 'a' AS status, users.name, Countries.id AS country, wca_id, dob, gender, email " .
            $eventColumns .
            " FROM users " .
            "JOIN Countries ON Countries.iso2=users.country_iso2 " .
            "WHERE users.id IN (SELECT DISTINCT user_id FROM registrations) " .
            "ORDER BY name"
        );
        $fh = fopen('php://output', 'w');
        fputcsv($fh,$captions);
        foreach ($results as $line) {
            fputcsv($fh,$line);
        }
        fclose($fh);
    }

    public function reimbursements()
    {
        $this->openConnection();
        $results = $this->DB->query(
            "SELECT id, name, amount_paid, amount_used, (amount_paid-amount_used) AS difference, " .
            "IF(approved,'YES','') AS staff, IF(approved,0,".RepoAdmin::NO_REFUNDABLE_BASE.") AS base " .
            "FROM users " .
            "LEFT JOIN staff ON staff.user_id=users.id " .
            "WHERE amount_paid <> amount_used ORDER BY name"
        );
        $total_paid = $total_used = $total_difference = $total_refund = 0;
        foreach ($results as &$row) {
            $row["escaped_name"] = addslashes($row["name"]);
            $total_paid += $row["amount_paid"];
            $total_used += $row["amount_used"];
            $total_difference += $row["difference"];
            $row["refund"] = $row["amount_paid"]-max($row["amount_used"],$row["base"]);
            $total_refund += $row["refund"];
        }
        $view = new AdminReimbursementsPageView($this->app);
        $view->render(array(
                "dues" => $results,
                "total_paid" => $total_paid,
                "total_used" => $total_used,
                "total_difference" => $total_difference,
                "total_refund" => $total_refund,
                "count" => count($results),
            ));
    }

    public function t_shirts()
    {
        $this->openConnection();
        $t_shirts = $this->DB->query(
            "SELECT t_shirt_size AS size, SUM(IF(day_19,1,0)+IF(day_20,1,0)+IF(day_21,1,0)+IF(day_22,1,0)) AS quantity ".
            "FROM staff ".
            "WHERE approved AND t_shirt_size <> '' ".
            "GROUP BY t_shirt_size ".
            "ORDER BY quantity DESC"
        );
        $emails_approved = $this->DB->query(
            "SELECT users.name, users.email FROM staff " .
            "JOIN users ON users.id=staff.user_id " .
            "WHERE approved AND t_shirt_size='' " .
            "ORDER BY name"
        );
        $emails_all = $this->DB->query(
            "SELECT users.name, users.email FROM staff " .
            "JOIN users ON users.id=staff.user_id " .
            "WHERE t_shirt_size=''" .
            "ORDER BY name"
        );
        $view = new AdminTshirtsPageView($this->app);
        $view->render(array(
                "t_shirts" => $t_shirts,
                "emails_approved" => $emails_approved,
                "emails_all" => $emails_all,
                "count_approved" => count($emails_approved),
                "count_all" => count($emails_all),
            ));
    }

    private function getNonQualified()
    {
        $this->openConnection();
        return $this->DB->query(
            "( SELECT registrations.id, rank, result_type, name_en AS event_name, name, best, qual_result, paid_fee " .
            "FROM registrations " .
            "JOIN users ON users.id=registrations.user_id " .
            "JOIN events ON events.id=registrations.event_id " .
            "LEFT JOIN RanksAverage AS Ranks ON Ranks.personId=users.wca_id AND Ranks.eventId=registrations.event_id " .
            "WHERE events.qual_result AND qual_format AND (best IS NULL OR best >= qual_result) ) " .
            "UNION ALL " .
            "( SELECT registrations.id, rank, result_type, name_en AS event_name, name, best, qual_result, paid_fee " .
            "FROM registrations " .
            "JOIN users ON users.id=registrations.user_id " .
            "JOIN events ON events.id=registrations.event_id " .
            "LEFT JOIN RanksSingle AS Ranks ON Ranks.personId=users.wca_id AND Ranks.eventId=registrations.event_id " .
            "WHERE events.qual_result AND qual_format = 0 AND (best IS NULL OR best >= qual_result) ) " .
            "ORDER BY rank, best, name"
        );

    }

    public function nonQualified()
    {
        $nonQualified = $this->getNonQualified();
        $refund = 0;
        foreach ($nonQualified as &$row) {
            $row["best"] = RepoUsers::wcaResultToString($row["best"],$row["result_type"]);
            $row["qual_result"] = RepoUsers::wcaResultToString($row["qual_result"],$row["result_type"]);
            $refund += $row["paid_fee"];
        }
        $view = new AdminNonQualifiedPageView($this->app);
        $view->render(array(
                "non_qualified" => $nonQualified,
                "refund" => $refund,
            ));
    }

    public function unregisterNonQualified()
    {
        $this->openConnection();
        $closure = $this->DB->query("SELECT date_time_".($this->app->isProd()?"prod":"dev")." AS deadline FROM registration_frames WHERE closure=1");
        if (count($closure) != 1 || $closure[0]["deadline"] > date(Repo::DATE_SQL_FORMAT))
            die("ERROR - Registration period is not over - impossible to unregister competitors now!");

        $nonQualified = $this->getNonQualified();
        foreach ($nonQualified as $row) {
            $this->DB->query("DELETE FROM registrations WHERE id=?",array($row["id"]));
        }

        Page\CompetitorsPageView::deleteCacheFile($this->app);
        Page\PsychsheetPageView::deleteCacheFile($this->app);

        $this->app->redirect("admin/nonqualified");
    }

    public function refund($user_id,$refund)
    {
        $this->openConnection();
        $closure = $this->DB->query("SELECT date_time_".($this->app->isProd()?"prod":"dev")." AS deadline FROM registration_frames WHERE closure=1");
        if (count($closure) != 1 || $closure[0]["deadline"] < date(Repo::DATE_SQL_FORMAT))
            die("ERROR - Registration period is not over - impossible to refund now!");

        $competitor = $this->DB->query(
            "SELECT name, email, amount_paid, amount_used, approved " .
            "FROM users " .
            "LEFT JOIN staff ON staff.user_id=users.id " .
            "WHERE id=?",
            array($user_id)
        )[0];
        $calculated_refund = $competitor["amount_paid"]-max($competitor["amount_used"],($competitor["approved"]?0:RepoAdmin::NO_REFUNDABLE_BASE));
        if ($calculated_refund != $refund) die("ERROR - Refund amount does not match!");

        $payments = $this->DB->query(
            "SELECT id, amount FROM payments WHERE user_id=? AND type=? ORDER BY created DESC",
            array($user_id,RepoPayments::PAYM)
        );
        $total_amount = 0;
        foreach ($payments as $payment) {
            $total_amount += $payment["amount"];
        }
        $refund_x100 = $refund*100;
        if ($refund_x100 > $total_amount) die("ERROR - no enough payments for such a refund");

        require_once('../stripe-php-5.7.0/init.php');
        include "__private_stripe__.inc";

        \Stripe\Stripe::setApiKey($stripe_secret_key);
        foreach ($payments as $payment) {
            $to_refund = min($refund_x100,$payment["amount"]);
            \Stripe\Refund::create([
                'charge' => $payment["id"],
                'amount' => $to_refund,
            ]);
            $this->DB->query(
                "UPDATE payments SET amount=amount-? WHERE id=?",
                array($to_refund,$payment["id"])
            );
            $refund_x100 -= $to_refund;
            if ($refund_x100 <= 0) break;
        }
        $this->DB->query(
            "UPDATE users SET amount_paid=amount_used WHERE id=?",
            array($user_id)
        );

        // email
        $sender = new Email\Email($this);
        $emailView = new View\Email\RefundEmailView($this->app);
        $sender->send(
            true, // $this->app->isProd(),
            $competitor["email"],
            $sender::wcEmail,
            $sender::wcEmail,
            null,
            "Reimbursement from WCA World Championship 2019",
            $emailView->renderView(array(
                    'name' => $competitor["name"],
                    'refund' => $refund,
                ))
        );

        die('OK');
    }
}
