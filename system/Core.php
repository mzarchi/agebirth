<?php


class Core
{

    private static $cr;
    private static $db;
    private static $tg;

    public static function getInstance()
    {
        if (self::$cr == null) {
            self::$cr = new Core();
        }
        return self::$cr;
    }

    public function __construct()
    {
        self::$db = Database::getInstance();
        self::$tg = Telegram::getInstance();
    }

    public function sendUserEntrance($chatId, $entrance, $function)
    {
        if (strlen($chatId) > 2) {
            $message = "User: <code>" . $chatId . "</code>\n";
            $message .= "Profile: <a href='tg://user?id=" . $chatId . "'>Click To Go</a>" . "\n";
            $message .= "Entrance: <code>" . $entrance . "</code>" . "\n";
            $message .= "Function: <code>" . $function . "</code>" . "\n";
            self::$tg->sendMessage(_REPORT_CHANNEL, $message);
            //self::$db->insertEntrance($chatId, time(), $entrance);
        }
    }

    public function setStartMenu($chatId, $text)
    {
        self::$tg->setChatAction($chatId);
        $this->sendUserEntrance($chatId, $text, __FUNCTION__);
        $len = strlen($text);
        if ($len <= 7) {
            // user /start
            self::$db->insertUserData($chatId, "********");
            $decade = array(
                "1830", "1840", "1850", "1860",
                "1870", "1880", "1890", "1900",
                "1910", "1920", "1930", "1940",
                "1950", "1960", "1970", "1980",
                "1990", "2000", "2010", "2020"
            );
            $message = "Please select decade ..";
            $body = array();
            for ($i = 0; $i < sizeof($decade); $i++) {
                $body[$i]['text'] = $decade[$i] . " ↑";
                $body[$i]['callback_data'] = "decade-" . $decade[$i];
            }

            $buttons = array('body' => $body, 'bodyVertical' => 4);
            self::$tg->sendInlineKeyboard($chatId, $message, "text", null, $buttons);
        } else {
            // user /start 9876543210
            $id = str_replace("/start ", "", $text);
            if (strpos($id, 'get') !== false) {
                $ts = str_replace("get", "", $id);
                $msg = "Date: " . date('Y-m-d', $ts) . "\n";
                $msg .= "Time: " . date('H:i:s', $ts) . " UTC\n";
                $msg .= "Update Code: <code>" . $ts . "</code>";
                self::$tg->sendMessage($chatId, $msg);
            } else {
                self::$db->setViewedDate($id);
                $dateData = self::$db->getDateData($id);
                $dateChatId = $dateData['_chatId'];
                $dateChar = $dateData['_date'];

                self::$db->insertUserData($chatId, $dateChatId);
                if (strlen($dateChar) > 10) {
                    // second-1994|09|25|03|15|20
                    $this->setSecondMenu($chatId, "second-" . $dateChar, $dateData);
                } else {
                    // NoTime-1994|09|25
                    $this->setTimeMenu($chatId, "NoTime-" . $dateChar, $dateData);
                }
            }
        }
    }

    public function setDecadeMenu($chatId, $text)
    {
        // decade-1990
        self::$tg->setChatAction($chatId);
        //$this->sendUserEntrance($chatId, $text, __FUNCTION__);
        $decade = str_replace("decade-", "", $text);
        $message = "Please select year .." . "\n";
        $message .= "(<i>Choose the year you were born</i>)";
        $body = array();
        for ($i = $decade, $j = 0; $i <= $decade + 9; $i++, $j++) {
            $body[$j]['text'] = $i . "";
            $body[$j]['callback_data'] = "year-" . $i;
        }
        $footer[0]['text'] = "Back to decades";
        $footer[0]['callback_data'] = "Start";
        $buttons = array(
            'body' => $body, 'bodyVertical' => 4,
            'footer' => $footer, 'footerVertical' => 1
        );
        self::$tg->sendInlineKeyboard($chatId, $message, "text", null, $buttons);
    }

    public function setYearMenu($chatId, $text)
    {
        // year-1994
        self::$tg->setChatAction($chatId);
        //$this->sendUserEntrance($chatId, $text, __FUNCTION__);
        $year = str_replace("year-", "", $text);
        $month = array("1-Jan", "2-Feb", "3-Mar", "4-Apr", "5-May", "6-Jun", "7-Jul", "8-Aug", "9-Sep", "10-Oct", "11-Nov", "12-Dec");
        $message = "Please select month .." . "\n";
        $message .= "(<i>Choose the month you were born</i>)";
        $body = array();
        for ($i = 0; $i < sizeof($month); $i++) {
            $body[$i]['text'] = $month[$i] . "";
            $body[$i]['callback_data'] = "month-" . $year . "|" . $this->setTen($i + 1);
        }
        $decade = floor($year / 10) * 10;
        $footer[0]['text'] = "Back to years";
        $footer[0]['callback_data'] = "decade-" . $decade;
        $buttons = array(
            'body' => $body, 'bodyVertical' => 3,
            'footer' => $footer, 'footerVertical' => 1
        );
        self::$tg->sendInlineKeyboard($chatId, $message, "text", null, $buttons);
    }

    public function setMonthMenu($chatId, $text)
    {
        // month-1994|09
        self::$tg->setChatAction($chatId);
        //$this->sendUserEntrance($chatId, $text, __FUNCTION__);
        $data = str_replace("month-", "", $text);
        $date = explode("|", $data);
        $year = $date[0];
        $month = $date[1];
        $day = array("01" => 31, "02" => 29, "03" => 31, "04" => 30, "05" => 31, "06" => 30, "07" => 31, "08" => 31, "09" => 30, "10" => 31, "11" => 30, "12" => 31);
        $message = "Please select day .." . "\n";
        $message .= "(<i>Choose the day you were born</i>)";
        $body = array();
        for ($i = 0; $i < $day[$month]; $i++) {
            $body[$i]['text'] = $this->setTen($i + 1) . " ";
            $body[$i]['callback_data'] = "day-" . $year . "|" . $month . "|" . $this->setTen($i + 1);
        }
        $footer[0]['text'] = "Back to months";
        $footer[0]['callback_data'] = "year-" . $year;
        $buttons = array(
            'body' => $body, 'bodyVertical' => 7,
            'footer' => $footer, 'footerVertical' => 1
        );
        self::$tg->sendInlineKeyboard($chatId, $message, "text", null, $buttons);
    }

    public function setDayMenu($chatId, $text)
    {
        // day-1994|09|25
        self::$tg->setChatAction($chatId);
        //$this->sendUserEntrance($chatId, $text, __FUNCTION__);
        $btn = str_replace("day", "", $text);
        $message = "Do you want to enter the time?" . "\n";
        $message .= "(<i>like hour, minute and second</i>)";
        $body[0]['text'] = "No";
        $body[0]['callback_data'] = "NoTime" . $btn;
        $body[1]['text'] = "Yes";
        $body[1]['callback_data'] = "YesTime" . $btn;
        $buttons = array('body' => $body, 'bodyVertical' => 2);
        self::$tg->sendInlineKeyboard($chatId, $message, "text", null, $buttons);
    }

    public function setTimeMenu($chatId, $text, $dateData = null)
    {
        // NoTime-1994|09|25
        // YesTime-1994|09|25
        self::$tg->setChatAction($chatId);
        // $this->sendUserEntrance($chatId, $text, __FUNCTION__);
        $data = explode("-", $text);
        $date = $data[1];
        if ($data[0] == "NoTime") {
            $date = str_replace("|", "-", $date);
            $start = strtotime($date);
            if ($start < 0) {
                $second = ($start * -1) + time();
            } else {
                $second = time() - $start;
            }

            $year = number_format(floor($second / 31536000));
            self::$db->insertUserAge($chatId, $second);
            $message = "Date: <code>" . $date . "</code>\n";
            if ($dateData != null) {
                if ($dateData['_name'] != "********") {
                    $message .= "- Name: <b>" . base64_decode($dateData['_name']) . "</b>\n";
                    $message .= "- Desc: <i>" . base64_decode($dateData['_desc']) . "</i>\n";
                }
                $message .= "- Seen by " . number_format($dateData['_view']) . " person\n";
            }
            $message .= "Total: " . "\n";
            $message .= "- Seconds: " . number_format($second) . "\n";
            $message .= "- Minutes: " . number_format(floor($second / 60)) . "\n";
            $message .= "- Hours: " . number_format(floor($second / 3600)) . "\n";
            $message .= "- days: " . number_format(floor($second / 86400)) . "\n";
            $message .= "- weeks: " . number_format(floor($second / 604800)) . "\n";
            $message .= "- Months(30d): " . number_format(floor($second / 2592000)) . "\n";
            $message .= "- Years(365d): " . $year . "\n";
            $message .= "Age Calculator: @AgeBirthBot" . "\n";
            $body[0]['text'] = "Restart";
            $body[0]['callback_data'] = "Start";
            $body[1]['text'] = "Check Again";
            $body[1]['callback_data'] = "" . $text;
            $body[2]['text'] = "Share this date";
            $body[2]['callback_data'] = "share-" . str_replace("-", "|", $date);
            $buttons = array('body' => $body, 'bodyVertical' => 2);
            self::$tg->sendInlineKeyboard($chatId, $message, "text", null, $buttons);
        } else {
            $hour = array("00", "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23");
            $message = "Please select hour .." . "\n";
            $message .= "(<i>Choose the hour you were born</i>)";
            $body = array();
            for ($i = 0; $i < sizeof($hour); $i++) {
                $body[$i]['text'] = $hour[$i] . "";
                $body[$i]['callback_data'] = "hour-" . $date . "|" . $this->setTen($i);
            }
            $footer[0]['text'] = "Finish and see age details";
            $footer[0]['callback_data'] = "NoTime-" . $date;
            $buttons = array(
                'body' => $body, 'bodyVertical' => 6,
                'footer' => $footer, 'footerVertical' => 1
            );
            self::$tg->sendInlineKeyboard($chatId, $message, "text", null, $buttons);
        }
    }

    public function setHourMenu($chatId, $text)
    {
        self::$tg->setChatAction($chatId);
        //$this->sendUserEntrance($chatId, $text, __FUNCTION__);
        $time = explode("-", $text)[1];
        $minute = array(
            "00", "01", "02", "03", "04", "05",
            "06", "07", "08", "09", "10", "11",
            "12", "13", "14", "15", "16", "17",
            "18", "19", "20", "21", "22", "23",
            "24", "25", "26", "27", "28", "29",
            "30", "31", "32", "33", "34", "35",
            "36", "37", "38", "39", "40", "41",
            "42", "43", "44", "45", "46", "47",
            "48", "49", "50", "51", "52", "53",
            "54", "55", "56", "57", "58", "59",
        );
        $message = "Please select minute .." . "\n";
        $message .= "(<i>Choose the minute you were born</i>)";
        $body = array();
        for ($i = 0; $i < sizeof($minute); $i++) {
            $body[$i]['text'] = $minute[$i] . "";
            $body[$i]['callback_data'] = "minute-" . $time . "|" . $this->setTen($i);
        }
        $footer[0]['text'] = "Finish and see age details";
        $footer[0]['callback_data'] = "second-" . $time . "|00|00";
        $buttons = array(
            'body' => $body, 'bodyVertical' => 6,
            'footer' => $footer, 'footerVertical' => 1
        );
        self::$tg->sendInlineKeyboard($chatId, $message, "text", null, $buttons);
    }

    public function setMinuteMenu($chatId, $text)
    {
        self::$tg->setChatAction($chatId);
        //$this->sendUserEntrance($chatId, $text, __FUNCTION__);
        $time = explode("-", $text)[1];
        $second = array(
            "00", "01", "02", "03", "04", "05",
            "06", "07", "08", "09", "10", "11",
            "12", "13", "14", "15", "16", "17",
            "18", "19", "20", "21", "22", "23",
            "24", "25", "26", "27", "28", "29",
            "30", "31", "32", "33", "34", "35",
            "36", "37", "38", "39", "40", "41",
            "42", "43", "44", "45", "46", "47",
            "48", "49", "50", "51", "52", "53",
            "54", "55", "56", "57", "58", "59",
        );
        $message = "Please select second .." . "\n";
        $message .= "(<i>Choose the second you were born</i>)";
        $body = array();
        for ($i = 0; $i < sizeof($second); $i++) {
            $body[$i]['text'] = $second[$i] . "";
            $body[$i]['callback_data'] = "second-" . $time . "|" . $this->setTen($i);
        }
        $footer[0]['text'] = "Finish and see age details";
        $footer[0]['callback_data'] = "second-" . $time . "|00";
        $buttons = array(
            'body' => $body, 'bodyVertical' => 6,
            'footer' => $footer, 'footerVertical' => 1
        );
        self::$tg->sendInlineKeyboard($chatId, $message, "text", null, $buttons);
    }

    public function setSecondMenu($chatId, $text, $dateData = null)
    {
        // second-1994|09|25|03|15|20
        self::$tg->setChatAction($chatId);
        //$this->sendUserEntrance($chatId, $text, __FUNCTION__);
        $timeDetails = explode("-", $text)[1];
        $data = explode("|", $timeDetails);
        $date = $data[0] . "-" . $data[1] . "-" . $data[2];
        $time = $data[3] . ":" . $data[4] . ":" . $data[5];
        $start = strtotime($date . " " . $time);
        if ($start < 0) {
            $second = ($start * -1) + time();
        } else {
            $second = time() - $start;
        }

        $year = number_format(floor($second / 31536000));
        self::$db->insertUserAge($chatId, $second);
        $message = "Details: " . "\n";
        $message .= "- Date: <code>" . $date . "</code>\n";
        $message .= "- Time: " . $time . "\n";
        if ($dateData != null) {
            if ($dateData['_name'] != "********") {
                $message .= "- Name: <b>" . base64_decode($dateData['_name']) . "</b>\n";
                $message .= "- Desc: <i>" . base64_decode($dateData['_desc']) . "</i>\n";
            }
            $message .= "- Seen by " . number_format($dateData['_view']) . " person\n";
        }
        $message .= "Total: " . "\n";
        $message .= "- Seconds: " . number_format($second) . "\n";
        $message .= "- Minutes: " . number_format(floor($second / 60)) . "\n";
        $message .= "- Hours: " . number_format(floor($second / 3600)) . "\n";
        $message .= "- days: " . number_format(floor($second / 86400)) . "\n";
        $message .= "- weeks: " . number_format(floor($second / 604800)) . "\n";
        $message .= "- Months(30d): " . number_format(floor($second / 2592000)) . "\n";
        $message .= "- Years(365d): " . $year . "\n";
        $message .= "Age Calculator: @AgeBirthBot" . "\n";
        $body[0]['text'] = "Restart";
        $body[0]['callback_data'] = "Start";
        $body[1]['text'] = "Check Again";
        $body[1]['callback_data'] = "" . $text;
        $body[2]['text'] = "Share this date";
        $body[2]['callback_data'] = "share-" . $timeDetails;
        $buttons = array('body' => $body, 'bodyVertical' => 2);
        self::$tg->sendInlineKeyboard($chatId, $message, "text", null, $buttons);
    }

    public function setProfileMenu($chatId, $text)
    {
        self::$tg->setChatAction($chatId);
        //$this->sendUserEntrance($chatId, $text, __FUNCTION__);
        $userData = self::$db->getUserData($chatId);
        $age = time() - $userData['_sign_date'];
        $days = floor($age / 86400);
        $hours = floor(($age % 86400) / 3600);
        $Core = "SignUp: " . number_format($days) . " day(s), " . $hours . " hour(s) ago.\n";
        $Core .= "- Time: " . date("H:i:s", _TimeStamp) . " UTC\n";
        $Core .= "- Date: <code>" . date("Y-m-d", _TimeStamp) . "</code>\n";
        $Core .= "Your date(s): " . number_format(self::$db->getCount("dates", $chatId)) . "\n";
        $Core .= "Invited: " . number_format(self::$db->getCount("caller", $chatId)) . "\n";
        if ($chatId == _MOHAMMAD) {
            $message = "You are Capitan (🎖) of Bot" . "\n";
        } else {
            $message = "You entered after " . number_format(($userData['_id'] - 1)) . " people." . "\n";
        }
        $message .= $Core;
        $body[0]['text'] = "Your Dates";
        $body[0]['callback_data'] = "/dates";
        $buttons = array('body' => $body, 'bodyVertical' => 2);
        self::$tg->sendInlineKeyboard($chatId, $message, "text", null, $buttons);
    }

    public function setSaveMenu($chatId, $text)
    {
        self::$tg->setChatAction($chatId);
        //$this->sendUserEntrance($chatId, $text, __FUNCTION__);
        $level = str_replace("save-", "SaveDate-", $text);
        self::$db->updateUserData($chatId, '_level', $level);
        $message = "Insert a name for this date:";
        $body[0]['text'] = "Cancel";
        $body[0]['callback_data'] = "CancelFunction";
        $buttons = array('body' => $body, 'bodyVertical' => 1);
        self::$tg->sendInlineKeyboard($chatId, $message, "text", null, $buttons);
    }

    public function setShareMenu($chatId, $text)
    {
        // share-1994|09|25|03|15|20
        // share-1994|09|25|
        self::$tg->setChatAction($chatId);
        //$this->sendUserEntrance($chatId, $text, __FUNCTION__);
        $dateId = $this->getMicro();
        $dateChar = str_replace("share-", "", $text);
        $repeat = self::$db->getRepeatDates($chatId, $dateChar);
        if ($repeat == 0) {
            self::$db->insertDate($dateId, $chatId, $dateChar);
            self::$tg->sendMessage($chatId, "Share this 👇🏽 link to your friends!");
            self::$tg->sendMessage($chatId, "https://t.me/AgeBirthBot?start=" . $dateId);
            //self::$tg->sendMessage($chatId, "https://t.me/TestZarchiBot?start=" . $dateId);
        } else {
            self::$tg->sendMessage($chatId, "You have already received the date link!");
        }
    }

    public function setStatisticMenu($chatId, $text)
    {
        self::$tg->setChatAction($chatId);
        //$this->sendUserEntrance($chatId, $text, __FUNCTION__);
        $age = time() - _TimeStamp;
        $days = floor($age / 86400);
        $hours = floor(($age % 86400) / 3600);
        $userCount = self::$db->getTableCount("_user_data");
        $message = "Bot Name: <b>Age Birth</b>" . "\n";
        $message .= "Username: @AgeBirthBot" . "\n";
        $message .= "Member(s): " . number_format($userCount) . "\n";
        if ($chatId == _MOHAMMAD) {
            $activeUser = self::$db->getCount("active");
            $sleepUser = self::$db->getCount("sleep");
            $deadUser = self::$db->getCount("dead");
            $message .= "- Active: " . number_format($activeUser) . " (" . floor(($activeUser * 100) / $userCount) . "%)\n";
            $message .= "- Sleep: " . number_format($sleepUser) . " (" . floor(($sleepUser * 100) / $userCount) . "%)\n";
            $message .= "- Dead: " . number_format($deadUser) . " (" . floor(($deadUser * 100) / $userCount) . "%)\n";
        }
        $message .= "Created: " . number_format($days) . " day(s), " . $hours . " hour(s) ago.\n";
        $message .= "- Time: " . date("H:i:s", _TimeStamp) . " UTC\n";
        $message .= "- Date: <code>" . date("Y-m-d", _TimeStamp) . "</code>\n";
        $message .= "Channel: @ZarchiProjects";
        $body[0]['text'] = "Check Again";
        $body[0]['callback_data'] = "/statistic";
        $buttons = array('body' => $body, 'bodyVertical' => 1);
        self::$tg->sendInlineKeyboard($chatId, $message, "text", null, $buttons);
    }

    public function setDateMenu($chatId, $text)
    {
        self::$tg->setChatAction($chatId);
        //$this->sendUserEntrance($chatId, $text, __FUNCTION__);
        if (self::$db->getCount("dates", $chatId) > 0) {
            $dateList = self::$db->getDateList($chatId);
            $message = "Your date(s): \n";
            $message .= "(<i>Click on cmd for get more details about date</i>) \n \n";
            $counter = 1;
            foreach ($dateList as $date) {
                $message .= $counter . ". cmd: /show" . $date['id'] . "\n";
                $message .= "- Seen by " . number_format($date['view']) . " person.\n \n";
                $counter++;
            }
            self::$tg->sendMessage($chatId, $message);
        } else {
            self::$tg->sendMessage($chatId, "You don't have any date!");
        }
    }

    public function setShowDateMenu($chatId, $text)
    {
        self::$tg->setChatAction($chatId);
        //$this->sendUserEntrance($chatId, $text, __FUNCTION__);
        $id = str_replace("/show", "", $text);
        $data = self::$db->getDateData($id);
        $time = floor($id / 1000000);
        $age = time() - $time;
        $days = floor($age / 86400);
        $hours = floor(($age % 86400) / 3600);
        $callback = (strlen($data['_date']) > 12 ? "second-" . $data['_date'] : "NoTime-" . $data['_date']);

        $message = "Id: <code>" . $id . "</code>\n";
        $message .= "Seen: " . number_format($data['_view']) . "\n";
        $message .= "Created: " . number_format($days) . " day(s), " . $hours . " hour(s) ago.\n";
        $message .= "- Time: " . date("H:i:s", $time) . " UTC\n";
        $message .= "- Date: <code>" . date("Y-m-d", $time) . "</code> \n";
        $body[0]['text'] = "Show";
        $body[0]['callback_data'] = $callback;
        $body[1]['text'] = "Share";
        $body[1]['callback_data'] = "link-" . $id;
        $buttons = array('body' => $body, 'bodyVertical' => 2);
        self::$tg->sendInlineKeyboard($chatId, $message, "text", null, $buttons);
    }

    public function setLinkMessage($chatId, $text)
    {
        self::$tg->setChatAction($chatId);
        //$this->sendUserEntrance($chatId, $text, __FUNCTION__);
        $id = str_replace("link-", "", $text);
        self::$tg->sendMessage($chatId, "Share this 👇🏽 link to your friends!");
        self::$tg->sendMessage($chatId, "https://t.me/AgeBirthBot?start=" . $id);
    }

    public function setDefaultMessage($chatId, $text)
    {
        $this->sendUserEntrance($chatId, $text, __FUNCTION__);
        self::$tg->sendMessage($chatId, "Please use bot keyboard!");
    }

    /*     * * * * * * * * * * * * * * * * * * * * * *
     * ╔═╗╔═╗  ╔╗╔╗             ╔╗╔╗      ╔╗      *
     * ║║╚╝║║ ╔╝╚╣║            ╔╝╚╣║      ║║      *
     * ║╔╗╔╗╠═╩╗╔╣╚═╦══╦═╗ ╔╗╔╦═╩╗╔╣╚═╦══╦═╝╠══╗  *
     * ║║║║║║╔╗║║║╔╗║║═╣╔╝ ║╚╝║║═╣║║╔╗║╔╗║╔╗║══╣  *
     * ║║║║║║╚╝║╚╣║║║║═╣║  ║║║║║═╣╚╣║║║╚╝║╚╝╠══║  *
     * ╚╝╚╝╚╩══╩═╩╝╚╩══╩╝  ╚╩╩╩══╩═╩╝╚╩══╩══╩══╝  *
     * * * * * * * * * * * * * * * * * * * * * * */

    public function getMicro()
    {
        $data = explode(" ", microtime());
        return time() . str_replace("0.", "", number_format($data[0], 6));
    }

    public function setTen($x)
    {
        return ($x < 10 ? "0" . $x : $x);
    }
}
