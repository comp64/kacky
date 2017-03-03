<?php
session_name('kacicky');
session_start();

spl_autoload_register(function($classname) {
  $filename = './class_'.strtolower($classname).'.php';
  include_once($filename);
});

function redirect($url) {
  echo "<script>window.location.replace('".$url."')</script>";
}

function on_shutdown() {
  echo '</body></html>';
}

if (isset($_POST['lsub'])) { // form was sent
  $uname=DB::escape($_POST['uname']);
  $upass=DB::escape($_POST['upass']);
  $u_id=DB::getval("SELECT u_id FROM user WHERE u_name='$uname' AND u_pass=SHA1('$uname:game:$upass')");
  if (!is_null($u_id)) {
    $_SESSION['login']=1;
    $_SESSION['uid']=$u_id;
    $_SESSION['uname']=$uname;
  }
}

if (isset($_GET['logout']) && ($_GET['logout']==1)) {
  $_SESSION=array();
  session_destroy();
  redirect('?');
}

if (!isset($_escape_int)) $_escape_int=array();

foreach($_escape_int as $k=>$v) {
  if (isset($_GET[$k])) $$k = $_GET[$k]*1;
  elseif (isset($_POST[$k])) $$k = $_POST[$k]*1;
	elseif (isset($_SESSION[$k])) $$k = $_SESSION[$k]*1;
  else $$k=$v;
}

$_SESSION['debug'] = $debug;
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<link rel="icon" type="image/png" href="i/duck.png">
<link rel="stylesheet" type="text/css" href="style.css">
<title>Střelené kachny</title>
<link rel="stylesheet" href="jquery-ui.min.css">
<script src="jquery-2.1.4.min.js"></script>
<script src="jquery-ui.min.js"></script>
<script src="jquery.ui.touch-punch.min.js"></script>
</head>
<body>
<?php
register_shutdown_function('on_shutdown');

if (!isset($_SESSION['login']) || !$_SESSION['login']) {
  echo '<p>';
  echo '<form method="post">';
  echo '<table>';
  echo '<tr><td>Login:</td><td><input type="text" name="uname" size="16" value=""></td></tr>';
  echo '<tr><td>Pass:</td><td><input type="password" name="upass" size="16" value=""></td></tr>';
  echo '<tr><td colspan="2"><input type="submit" name="lsub" value="Login"></td></tr>';
  echo '</table>';
  echo '</form>';
  echo '</p>';
  exit();
} // not logged in

echo '<div class="menicko">'; // meníčko
echo '<a href="?logout=1">Logout</a>&nbsp;&nbsp;';
echo '<a href="?">Zoznam hier</a>';
echo '</div>';
// content
?>