<?php
class MySQL extends mysqli {

  const MY_HOST='localhost';
  
  private $transaction;

  function __construct($h, $u, $p, $d) {
    $this->transaction=false;
    parent::__construct($h, $u, $p, $d);
  }

  public function mq_remove($str) {
    if (get_magic_quotes_gpc()) return stripslashes($str);
    else return $str;
  }

  public function q($q) {
    if (($res=$this->query($q)) === FALSE) throw new Exception('SQL query error: '.$this->error,4);
    else return $res;
  }

  public function getval($q) {
    $res=$this->q($q);
    $row=$res->fetch_row();
    $res->close();
    if (is_null($row)) return null;
    else return $row[0];
  }

  public function getrow($q) {
    $res=$this->q($q);
    if ($res->num_rows > 0)
      $row=$res->fetch_assoc();
    else $row=null;
    $res->close();
    return $row;
  }

  public function getrow2($q) {
    $res=$this->q($q);
    if ($res->num_rows > 0)
      $row=$res->fetch_row();
    else $row=null;
    $res->close();
    return $row;
  }

  public function getarray($q) {
    $res=$this->q($q);
    $arr=array();
    while($row=$res->fetch_assoc()) {
      $arr[]=$row;
    }
    $res->close();
    return $arr;
  }

  public function getarray2($q) {
    $res=$this->q($q);
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
  
  // Forward all static calls to the DB object instance
  public static function __callStatic($name, $params) {
    $db = static::getInstance();
    return call_user_func_array(array($db, $name), $params);
  }
  
  // obtain or create DB instance
  public static function getInstance() {
    if (is_null(static::$db)) {
      static::$db = new MySQL(MySQL::MY_HOST, 'games', '8HFVQVcKKBJzZXQN', 'games');
    }
    return static::$db;
  }
  
  // restrict these operations to enforce the singleton design pattern
  private function __construct() {}
  private function __wakeup() {}
  private function __clone () {}
}
