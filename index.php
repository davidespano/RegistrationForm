<?php

include_once 'classes/DbManager.php';
include_once 'classes/NationList.php';

date_default_timezone_set("Europe/Rome");
// punto unico di accesso all'applicazione
AdminController::dispatch($_REQUEST);

class AdminController {

    public static function dispatch(&$request) {

        //TODO inserire un blocco per connessioni non https

        header('Content-type: text/html; charset=ISO-8859-1');

        //var_dump($request);

        if (isset($request['keyord'])) {
            $partId = $request['keyord'];
            $p = new Participant();
            $p->id = filter_var($partId, FILTER_VALIDATE_INT) ? $partId : -1;
            AdminController::loadSummary($request, $p, true);
            return;
        }




        $step = $request['step'];
        $keys = AdminController::populateKeys($request);
        if ($keys == null) {
            AdminController::write404();
            return;
        }

        switch ($step) {
            case 's1':

                $email1 = $request['email1'];
                $email2 = $request['email2'];
                $regId = str_replace("reg", "", $request['regtype']);

                if ($email1 == $email2 &&
                        filter_var($email1, FILTER_VALIDATE_EMAIL) &&
                        filter_var($regId, FILTER_VALIDATE_INT)) {
                    $keys->email = $email1;
                    $keys->regId = filter_var($regId, FILTER_VALIDATE_INT);

                    switch ($keys->regId) {
                        case -1:
                            // request for registration summary
                            $p = DbManager::instance()->getParticipantSummary($keys->email);
                            if (!isset($p)) {
                                include 'views/noRegistration.php';
                                return;
                            }
                            $keys->regId = -1;
                            $logo = $keys->logo;
                            AdminController::loadSummary($request, $p, false);
                            break;

                        default:
                            $p = DbManager::instance()->getOrCreateParticipant($keys->email, $keys->regId);
                            if ($p->state > 0) {
                                // registration completed
                                AdminController::loadSummary($request, $p, false);
                            } else {
                                // registration in progress
                                $keys->participantId = $p->id;
                                AdminController::loadPersonalStep($keys, $p);
                            }
                            break;
                    }
                } else {
                    AdminController::loadInitialStep($keys);
                }
                break;

            case 's2':
                $partId = $request['partId'];
                $p = new Participant();
                $p->id = filter_var($partId, FILTER_VALIDATE_INT) ? $partId : -1;
                $p = DbManager::instance()->getParticipantById($partId);
                if (AdminController::populateParticipant($p, $request)) {
                    DbManager::instance()->updateParticipant($p);
                    DbManager::instance()->lazyLoadParticipant($p);
                    $workshops = DbManager::instance()->getWorkshopsByConfId($p->getRegType()->conferenceId);
                    $extras = DbManager::instance()->getExtraByConfId($p->getRegType()->conferenceId);
                    AdminController::loadWorkshopExtraStep($keys, $p, $workshops, $extras);
                } else {
                    // something wrong, go to step 1
                    $keys->email = $p->email;
                    $keys->regId = $p->regtype_id;
                    $keys->participantId = $p->id;
                    AdminController::loadPersonalStep($keys, $p);
                }
                break;

            case 's3':
                $partId = $request['partId'];
                $p = new Participant();
                $p->id = filter_var($partId, FILTER_VALIDATE_INT) ? $partId : -1;
                AdminController::loadSummary($request, $p, false);
                break;


            default:
                AdminController::loadInitialStep($keys);
                break;
        }
    }

    public static function populateKeys(&$request) {
        $keys = new Keys();

        if (isset($request['conf'])) {
            $c = DbManager::instance()->getConferenceByCode($request['conf']);
            if ($c != null) {
                $keys->logo = "events/$c->code/logo.png";
                $keys->conf = $c->code;
                $keys->numeraUrl = $c->numeraurl;
                $keys->vendor = $c->vendor;
                return $keys;
            } else {
                return null;
            }
        }
    }

    public static function loadInitialStep($keys) {

        $regs = DbManager::instance()->getRegTypes($keys->conf, 1);
        include 'views/login.php';
    }

    public static function loadPersonalStep($keys, $p) {
        $logo = $keys->logo;
        $nations = NationList::getMap();
        include 'views/personal.php';
    }

    public static function loadWorkshopExtraStep($keys, $p, $workshops, $extras) {

        include 'views/workshops.php';
    }

    public static function loadSummary(&$request, $p, $numera) {
        $p = DbManager::instance()->getParticipantById($p->id);
        $workshops = DbManager::instance()->getWorkshopsByConfId($p->getRegType()->conferenceId);
        $extras = DbManager::instance()->getExtraByConfId($p->getRegType()->conferenceId);
        if ($request['step'] == 's3' && $p->state != 1) {
            // aggiungo workshop ed extra solo se sono al passo 3 e l'ordine 
            // non e' chiuso
            AdminController::addWorkshopExtra($p, $request, $extras);
        }
        DbManager::instance()->lazyLoadParticipant($p);
        $conf = DbManager::instance()->getConferenceById($p->getRegType()->conferenceId);
        $request['conf'] = $conf->code;
        $keys = AdminController::populateKeys($request);
        DbManager::instance()->lazyLoadParticipant($p);
        if ($p->state == 1) {
            $state = "ok";
        }
        if ($numera && $p->state == 0) {
            $state = "nok";
        }
        if (!$numera && $p->state == 0) {
            $state = "pay";
        }

        include 'views/summary.php';
    }

    public static function populateParticipant($p, &$request) {
        $ok = true;
        if (isset($request["prefix"])) {
            $p->prefix = $request["prefix"];
        }
        if (isset($request["name"])) {
            $p->firstname = $request["name"];
        } else {
            $ok = false;
        }
        if (isset($request["middleName"])) {
            $p->middlename = $request["middleName"];
        }
        if (isset($request["lastName"])) {
            $p->lastname = $request["lastName"];
        }
        if (isset($request["jobTitle"])) {
            $p->jobtitle = $request["jobTitle"];
        }
        if (isset($request["badgeName"])) {
            $p->badge = $request["badgeName"];
        } else {
            $ok = false;
        }
        if (isset($request["company"])) {
            $p->company = $request["company"];
        } else {
            $ok = false;
        }
        if (isset($request["contry"])) {
            $p->country = $request["contry"];
        } else {
            $ok = false;
        }
        if (isset($request["address1"])) {
            $p->addressline1 = $request["address1"];
        } else {
            $ok = false;
        }
        if (isset($request["address1"])) {
            $p->addressline2 = $request["address2"];
        }
        if (isset($request["city"])) {
            $p->city = $request["city"];
        } else {
            $ok = false;
        }
        if (isset($request["zip"])) {
            $p->zip = $request["zip"];
        } else {
            $ok = false;
        }
        if (isset($request["vat"])) {
            $p->vat = $request["vat"];
        }
        if (isset($request["invoice"])) {
            switch ($request["invoice"]) {
                case 'personal':
                    $p->invoiceType = 0;
                    break;
                case 'organization':
                    $p->invoiceType = 1;
                    break;
                default :
                    $p->invoiceType = 0;
                    break;
            }
        }
        if (isset($request["membershipName"])) {
            $p->membershipName = $request["membershipName"];
        }
        if (isset($request["membershipId"])) {
            $p->membershipId = $request["membershipId"];
        }

        if (isset($request["cf"])) {
            $p->cf = $request["cf"];
        }

        if (isset($request["idNumber"])) {
            $p->idNumber = $request["idNumber"];
        }


        if (isset($request["birthDate"])) {
            $p->birthDate = $request["birthDate"];
        }

        if (isset($request["birthPlace"])) {
            $p->birthPlace = $request["birthPlace"];
        }

        if (isset($request["diet"])) {
            foreach ($request["diet"] as $diet) {
                switch ($diet) {
                    case 'meatFree':
                        $p->meatfree = 1;
                        break;
                    case 'fishFree':
                        $p->fishfree = 1;
                        break;
                    case 'shellFishFree':
                        $p->shellfishfree = 1;
                        break;
                    case 'eggFree':
                        $p->eggfree = 1;
                        break;
                    case 'milkFree';
                        $p->milkfree = 1;
                        break;
                    case 'animalFree':
                        $p->animalfree = 1;
                        break;
                    case 'glutenFree':
                        $p->glutenfree = 1;
                        break;
                    case 'peanutFree':
                        $p->peanutfree = 1;
                        break;
                    case 'wheatFree':
                        $p->wheatfree = 1;
                        break;
                    case 'soyFree':
                        $p->soyfree = 1;
                        break;
                }
            }
        }
        if (isset($request["otherDiet"])) {
            $p->additionaldiet = $request["otherDiet"];
        }
        $p->ipaddress = $_SERVER['REMOTE_ADDR'];
        return $ok;
    }

    public static function addWorkshopExtra($p, &$request, &$extras) {
        if (isset($request["w"])) {
            DbManager::instance()->deleteWorkshops($p->id);
            foreach ($request["w"] as $w) {
                $wid = str_replace("w", "", $w);
                DbManager::instance()->insertWorkshop($p->id, $wid);
            }
        }

        DbManager::instance()->deleteExtras($p->id);
        foreach ($extras as $extra) {
            $rk = "e" . $extra->id;
            if (isset($request[$rk])) {
                $val = filter_var($request[$rk], FILTER_VALIDATE_INT) ? $request[$rk] : 0;
                if ($val > 0) {
                    DbManager::instance()->insertExtra($p->id, $extra->id, $val);
                }
            }
        }
    }

    public static function write404() {
        // impostiamo il codice della risposta http a 404 (file not found)
        header('HTTP/1.0 404 Not Found');
        echo '<h1>404 Not Found </h1>';
        echo "Sorry, the page you requested is not available :(";
        exit();
    }

}

class Keys {

    public $logo;
    public $conf;
    public $email;
    public $regId;
    public $participantId;
    public $vendor;

}

?>