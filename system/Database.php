<?php


class Database
{

  private $connection;
  private static $db;

  public static function getInstance($option = null)
  {
    if (self::$db == null) {
      self::$db = new Database($option);
    }
    return self::$db;
  }

  private function __construct($option = null)
  {
    if ($option != null) {
      $host = $option['host'];
      $user = $option['user'];
      $pass = $option['pass'];
      $name = $option['name'];
    } else {
      global $config;
      $host = $config['host'];
      $user = $config['user'];
      $pass = $config['pass'];
      $name = $config['name'];
    }

    $this->connection = new mysqli($host, $user, $pass, $name);
    if ($this->connection->connect_error) {
      echo "Connection failed: " . $this->connection->connect_error;
      exit;
    }

    $this->connection->query("SET NAMES 'ut8'");
  }

  public function query($sql)
  {
    return $this->connection->query($sql);
  }

  public function insertUserData($chatId, $caller)
  {
    $this->query("INSERT INTO `_user_data` VALUES (NULL, '" . time() . "', '" . $chatId . "', '" . $caller . "', '********', 'en', '0')");
  }

  public function insertDate($dateId, $chatId, $date)
  {
    $this->query("INSERT INTO `_user_dates` VALUES (NULL, '" . $dateId . "', '" . $chatId . "', '" . $date . "', '********', '********', '0', '0')");
  }

  public function updateUserData($chatId, $field, $value)
  {
    // UPDATE `_user_data` SET `_level` = '********' WHERE `_chatId` LIKE '441660894'
    $this->query("UPDATE `_user_data` SET `" . $field . "` = '" . $value . "' WHERE `_chatId` LIKE '" . $chatId . "'");
  }

  public function insertEntrance($chatId, $time, $entrance)
  {
    $this->query("INSERT INTO `_user_entrance` VALUES (NULL, '" . $chatId . "', '" . $time . "', '" . $entrance . "')");
  }

  public function insertUserAge($chatId, $age)
  {
    $this->query("INSERT INTO `_user_age` VALUES (NULL, '" . $chatId . "', '" . $age . "')");
  }

  public function getUserData($chatId)
  {
    $result = $this->query("SELECT * FROM `_user_data` WHERE `_chatId` LIKE '" . $chatId . "'");
    return $result->fetch_array();
  }

  public function getDateData($id)
  {
    $result = $this->query("SELECT * FROM `_user_dates` WHERE `_id` LIKE '" . $id . "'");
    return $result->fetch_array();
  }

  public function getUserStatus()
  {
    $data = array();
    $result = $this->query("SELECT COUNT(*) AS _count FROM `_user_data`");
    $data['All'] = $result->fetch_array()['_count'];
    $result = $this->query("SELECT COUNT(*) AS _count FROM `_user_data` WHERE `_active` < 2 ");
    $data['Active'] = $result->fetch_array()['_count'];
    return $data;
  }

  public function getTableCount($table)
  {
    $result = $this->query("SELECT COUNT(*) AS _count FROM `" . $table . "` ");
    return $result->fetch_array()['_count'];
  }

  public function getCount($switch, $chatId = null)
  {
    $sql = null;
    switch ($switch) {
      case "active":
        $sql = "SELECT COUNT(*) AS _count FROM `_user_data` WHERE `_active` = 0";
        break;
      case "sleep":
        $sql = "SELECT COUNT(*) AS _count FROM `_user_data` WHERE `_active` = 1";
        break;
      case "dead":
        $sql = "SELECT COUNT(*) AS _count FROM `_user_data` WHERE `_active` = 2";
        break;
      case "dates":
        $sql = "SELECT COUNT(*) AS _count FROM `_user_dates` WHERE `_chatId` LIKE '" . $chatId . "'";
        break;
      case "caller":
        $sql = "SELECT COUNT(*) as _count FROM `_user_data` WHERE `_caller` LIKE '" . $chatId . "'";
        break;
    }
    $result = $this->query($sql);
    return $result->fetch_array()['_count'];
  }

  public function getReceptorData($group, $top)
  {
    $sql = null;
    switch ($group) {
      case "All":
        $sql = "SELECT `_id`, `_chatId` FROM `_user_data` WHERE `_active` < 2 AND `_id` > " . $top . " ORDER BY `_id` ASC ";
        break;
      case "En":
        $sql = "SELECT `_id`, `_chatId` FROM `_user_data` WHERE `_lan` LIKE 'en' AND `_active` < 2 AND `_id` > " . $top . " ORDER BY `_id` ASC";
        break;
      case "Fa":
        $sql = "SELECT `_id`, `_chatId` FROM `_user_data` WHERE `_lan` LIKE 'fa' AND `_active` < 2 AND `_id` > " . $top . " ORDER BY `_id` ASC";
        break;
    }

    $result = $this->query($sql);
    $data = array();
    while ($row = mysqli_fetch_array($result)) {
      $helper['id'] = $row['_id'];
      $helper['chatId'] = $row['_chatId'];
      $data[] = $helper;
    }
    return $data;
  }

  public function getRepeatDates($chatId, $date)
  {
    $result = $this->query("SELECT COUNT(CONCAT(_chatId,'|', _date)) AS `_value` FROM `_user_dates` WHERE _chatId LIKE '" . $chatId . "' AND _date LIKE '" . $date . "'");
    return $result->fetch_array()['_value'];
  }

  public function setViewedDate($dateId)
  {
    $this->query("UPDATE _user_dates SET _view = _view + 1 WHERE _id LIKE '" . $dateId . "'");
  }

  public function getDateList($chatId)
  {
    $result = $this->query("SELECT * FROM `_user_dates` WHERE `_chatId` LIKE '" . $chatId . "'");
    $data = array();
    while ($row = mysqli_fetch_array($result)) {
      $helper['id'] = $row['_id'];
      $helper['view'] = $row['_view'];
      $data[] = $helper;
    }
    return $data;
  }
}
