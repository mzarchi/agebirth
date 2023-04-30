<?php
require_once 'system/Telegram.php';
require_once 'system/Database.php';
require_once 'system/Core.php';

const _TOKEN = "bot-token";

global $config;
$config['host'] = "localhost";
$config['user'] = "username";
$config['pass'] = "password";
$config['name'] = "database-name";
