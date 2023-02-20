<?php

require_once("config.php");
$json = file_get_contents('php://input');
$tg = Telegram::getInstance($json);

if ($tg->isChannelPost() === false && $tg->isEditChannelPost() === false) {

    $chatId = $tg->getChatId();
    $text = $tg->getMessageText();
    /* $member = $tg->getChannelMember($chatId);
    if ($member === true) {} else {
        $message = "To work with the bot, you must subscribe to our channel and click on <b>Check membership</b>.";
        $message .= "\nChannel link: @ZarchiProjects";
        $body[0]['text'] = "Check membership";
        $body[0]['callback_data'] = $text;
        $buttons = array('body' => $body, 'bodyVertical' => 1);
        $tg->sendInlineKeyboard($chatId, $message, "text", null, $buttons);
    } */
    $cr = Core::getInstance();
    $textHelper = $text;
    if ($chatId > 0) {
        if (strpos($text, "/start") !== false) {
            $text = "Start";
        } elseif (strpos($text, "/show") !== false) {
            $text = "Show";
        } elseif (strpos($text, "decade-") !== false) {
            $text = "decade";
        } elseif (strpos($text, "year-") !== false) {
            $text = "year";
        } elseif (strpos($text, "month-") !== false) {
            $text = "month";
        } elseif (strpos($text, "day-") !== false) {
            $text = "day";
        } elseif (strpos($text, "Time-") !== false) {
            $text = "Time";
        } elseif (strpos($text, "hour-") !== false) {
            $text = "Hour";
        } elseif (strpos($text, "minute-") !== false) {
            $text = "Minute";
        } elseif (strpos($text, "second-") !== false) {
            $text = "Second";
        } elseif (strpos($text, "share-") !== false) {
            $text = "Share";
        } elseif (strpos($text, "link-") !== false) {
            $text = "Link";
        }

        switch ($text) {
            case "/start":
            case "Start":
                $cr->setStartMenu($chatId, $textHelper);
                break;
            case "/profile":
                $cr->setProfileMenu($chatId, $textHelper);
                break;
            case "/dates":
                $cr->setDateMenu($chatId, $textHelper);
                break;
            case "/statistic":
                $cr->setStatisticMenu($chatId, $textHelper);
                break;
            case "decade":
                $cr->setDecadeMenu($chatId, $textHelper);
                break;
            case "year":
                $cr->setYearMenu($chatId, $textHelper);
                break;
            case "month":
                $cr->setMonthMenu($chatId, $textHelper);
                break;
            case "day":
                $cr->setDayMenu($chatId, $textHelper);
                break;
            case "Time":
                $cr->setTimeMenu($chatId, $textHelper);
                break;
            case "Hour":
                $cr->setHourMenu($chatId, $textHelper);
                break;
            case "Minute":
                $cr->setMinuteMenu($chatId, $textHelper);
                break;
            case "Second":
                $cr->setSecondMenu($chatId, $textHelper);
                break;
            case "Share":
                $cr->setShareMenu($chatId, $textHelper);
                break;
            case "Show":
                $cr->setShowDateMenu($chatId, $textHelper);
                break;
            case "Link":
                $cr->setLinkMessage($chatId, $textHelper);
                break;
            default:
                $cr->setDefaultMessage($chatId, $textHelper);
                break;
        }
    }
}
