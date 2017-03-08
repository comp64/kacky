<?php
namespace Comp\Kacky;

/* MySQL singleton object and supplementary class mysqli_result_lite */

// class that provides a similar interface to the mysqli_stmt::get_result function
//  in case it is not available
class mysqli_result_lite {
  /**
   * @var \mysqli_stmt
   */
  private $stmt;

  public $num_rows;

  /**
   * mysqli_result_lite constructor.
   * @param \mysqli_stmt $statement
   */
  function __construct($statement) {
    $this->stmt = $statement;
    $this->stmt->store_result();
    $this->num_rows = $this->stmt->num_rows;
  }

  public function fetch_assoc() {
    $metadata = $this->stmt->result_metadata();
    $data = array();
    $params = array();
    while ($field = $metadata->fetch_field()) {
      $params[] = &$data[$field->name];
    }
    call_user_func_array(array($this->stmt, 'bind_result'), $params);
    $rv = $this->stmt->fetch();
    if ($rv===true) return $data;
    else return $rv;
  }

  public function fetch_row() {
    $metadata = $this->stmt->result_metadata();
    $data = array();
    $params = array();
    $i=0;
    while ($field = $metadata->fetch_field()) {
      $params[] = &$data[$i];
      $i++;
    }
    call_user_func_array(array($this->stmt, 'bind_result'), $params);
    $rv = $this->stmt->fetch();
    if ($rv===true) return $data;
    else return $rv;
  }

  public function free() {
    $this->stmt->free_result();
    $this->stmt->close();
    $this->stmt = null;
  }

  public function close() {
    $this->free();
  }
}

class MySQL extends \mysqli {

  private $transaction;

  function __construct($h, $u, $p, $d) {
    $this->transaction=false;
    parent::__construct($h, $u, $p, $d);
    $this->query("SET NAMES 'utf8'");
  }

  // convert the array to references
  // dirty hack needed by mysqli_stmt::bind_param
  private function mkRefs($data) {
    $refs = array();
    foreach ($data as $k=>$v) $refs[$k] = &$data[$k];
    return $refs;
  }

  public function mq_remove($str) {
    if (get_magic_quotes_gpc()) return stripslashes($str);
    else return $str;
  }

  public function q($q, $bind_vars=null) {
    if ($bind_vars !== null) { // prepared statement
      $stmt = $this->stmt_init();
      if (!$stmt->prepare($q)) {
        throw new \Exception('SQL prepare error: '.$stmt->error.' ['.$q.']');
      }

      // $stmt->bind_param called in a way to convert array into parameters
      // moreover the stupid bind_param requires references
      if (count($bind_vars)) {
        if (!call_user_func_array(array($stmt, 'bind_param'), $this->mkRefs($bind_vars))) {
          $stmt->close();
          throw new \Exception('SQL bind_param error: '.$stmt->error.' ['.$q.'] ['.print_r($bind_vars, true).']');
        }
      }

      if (!$stmt->execute()) {
        throw new \Exception('SQL execute error: '.$stmt->error.' ['.$q.'] ['.print_r($bind_vars, true).']');
      }

      if (method_exists($stmt, 'get_result')) {
        $res = $stmt->get_result();
        $stmt->close();
      } else { // emulate mysqli_result with lite version
        $res = new mysqli_result_lite($stmt);
      }
      // get_result gives false for non-SELECT queries
      // but at this time we know the query was successful, so just pretend that result is true
      if ($res === false) $res = true;
    } else {
      $res = $this->query($q);
    }

    if ($res === false) {
      throw new \Exception('SQL query error: '.$this->error.' ['.$q.']');
    } else
      return $res;
  }

  public function getval($q, $bind_vars=null) {
    $res=$this->q($q, $bind_vars);
    $row=$res->fetch_row();
    $res->close();
    if (is_null($row)) return null;
    else return $row[0];
  }

  public function getrow($q, $bind_vars=null) {
    $res=$this->q($q, $bind_vars);
    if ($res->num_rows > 0)
      $row=$res->fetch_assoc();
    else $row=null;
    $res->close();
    return $row;
  }

  public function getrow2($q, $bind_vars=null) {
    $res=$this->q($q, $bind_vars);
    if ($res->num_rows > 0)
      $row=$res->fetch_row();
    else $row=null;
    $res->close();
    return $row;
  }

  public function getarray($q, $bind_vars=null) {
    $res=$this->q($q, $bind_vars);
    $arr=array();
    while($row=$res->fetch_assoc()) {
      $arr[]=$row;
    }
    $res->close();
    return $arr;
  }

  public function getarray2($q, $bind_vars=null) {
    $res=$this->q($q, $bind_vars);
    $arr=array();
    while($row=$res->fetch_row()) {
      $arr[]=$row;
    }
    $res->close();
    return $arr;
  }

  public function escape($q, $quote_remove=false) {
    if ($quote_remove)
      return $this->real_escape_string(self::mq_remove($q));
    else
      return $this->real_escape_string($q);
  }

  public function escape_array($keys_s, $keys_i, $data, $quote_remove=false, $with_html=false) {
    $esc=array();
    foreach ($keys_s as $k) {
      if (isset($data[$k])) {
        $esc[$k]=$this->escape($data[$k], $quote_remove);
        if ($with_html) $esc[$k.'_html']=htmlentities($data[$k], ENT_QUOTES, 'UTF-8');
      } else {
        $esc[$k]='';
        if ($with_html) $esc[$k.'_html']='';
      }
    }
    foreach ($keys_i as $k) {
      if (isset($data[$k])) $esc[$k]=$data[$k]*1;
      else $esc[$k]=0;
      if ($with_html) $esc[$k.'_html']=$esc[$k];
    }
    return $esc;
  }

  public function lock($write, $read='') {
    $rtabs=''; $wtabs=''; $sep='';
    if (is_array($read)) $rtabs=implode(' READ, ', $read).' READ';
    else if (strlen($read)) $rtabs=$read.' READ';

    if (is_array($write)) $wtabs=implode(' WRITE, ', $write).' WRITE';
    else if (strlen($write)) $wtabs=$write.' WRITE';
    if (strlen($rtabs) && strlen($wtabs)) $sep=', ';
    $this->q('LOCK TABLES '.$rtabs.$sep.$wtabs);
  }

  public function unlock() {
    $this->q('UNLOCK TABLES');
  }

  public function start_transaction() {
    if ($this->transaction) return;
    $this->q('START TRANSACTION');
    $this->transaction=true;
  }

  public function end_transaction($success=true) {
    if (!$this->transaction) return;
    $this->transaction=false;
    if ($success) $this->query('COMMIT');
    else $this->query('ROLLBACK');
  }
}

// DB object Singleton
class DB {
  private static $db=null;

  /**
   * Get the database configuration
   * @param string $configFile
   * @return array
   * @throws \Exception
   */
  public static function getConfig(string $configFile=null) {
    $configFile = $configFile ?? __DIR__.'/../../../config/DB.json';
    if (!file_exists($configFile)) {
      throw new \Exception('DB config file not found: ' . $configFile);
    }
    return json_decode(file_get_contents($configFile), true);
  }

  /**
   * Get database DSN for use with ex. PDO class
   * @param array|null $config
   * @return string
   */
  public static function getDSN(array $config=null) {
    $config = $config ?? static::getConfig();
    return 'mysql:host='.$config['host'].';dbname='.$config['db'];
  }

  /**
   * Obtain PDO connection
   * @param array|null $config
   * @return \PDO
   */
  public static function getPDO(array $config=null) {
    $config = $config ?? static::getConfig();
    $pdo = new \PDO(static::getDSN($config), $config['user'], $config['pass']);
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    return $pdo;
  }

  // obtain or create DB instance
  /**
   * @return MySQL
   */
  public static function getInstance() {
    if (is_null(static::$db)) {
      $dbConfig = static::getConfig();
      static::$db = new MySQL($dbConfig['host'], $dbConfig['user'], $dbConfig['pass'], $dbConfig['db']);
    }
    return static::$db;
  }

  // restrict these operations to enforce the singleton design pattern
  private function __construct() {}
  private function __wakeup() {}
  private function __clone () {}
}