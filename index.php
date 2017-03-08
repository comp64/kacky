<?php
use Comp\Kacky\GUI;
use Comp\Kacky\DB;
use Comp\Kacky\Model\User;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

include('vendor/autoload.php');

// prepare Session
$dbConfig = DB::getConfig();
$session = new Session(new NativeSessionStorage([], new PdoSessionHandler(DB::getPDO($dbConfig), ['db_table'=>'session'])));
$session->setName('kacky_wi');
$session->start();

$gameId = $_GET['gid'] ?? 0;
$gameId*=1;

function redirect($url) {
  echo "<script>window.location.replace('$url')</script>";
  exit();
}

// handle login form submission
if (isset($_POST['lsub'])) {
  $user = new User(null);
  $username = substr($_POST['uname'], 0, 16);
  $password = substr($_POST['upass'], 0, 16);
  try {
    $user->verifyFromDB($username, $password);
    $session->set('isLogged', true);
    $session->set('username', $username);
    $session->set('password', openssl_encrypt($password, 'AES-256-CBC', $dbConfig['crypt_pw'], 0, $dbConfig['crypt_iv']));
    redirect('?');
  } catch (\Exception $e) {
    $session->set('isLogged', false);
    $session->getFlashBag()->add('login_errors', $e->getMessage());
    sleep(3);
  }
}

// handle logout
if (isset($_GET['logout'])) {
  $session->clear();
  $session->set('isLogged', false);
  redirect('?');
}

?><!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <link rel="icon" type="image/png" href="i/duck.png">
  <link rel="stylesheet" type="text/css" href="style.css">
  <title>Kačice z našej police</title>
  <link rel="stylesheet" href="jquery-ui.min.css">
  <script src="jquery-2.1.4.min.js"></script>
  <script src="jquery-ui.min.js"></script>
  <script src="jquery.ui.touch-punch.min.js"></script>
</head>
<body>
<?php
if (!$session->get('isLogged', false)) {
  echo '<p>' . implode('<br>', $session->getFlashBag()->get('login_errors')) .'</p>';
  ?>
  <div style="margin: 15px">
    <form method="post">
      <table>
        <tr>
          <td><label for="uname">Login:</label></td>
          <td><input id="uname" type="text" name="uname" size="16" value=""></td>
        </tr>
        <tr>
          <td><label for="upass">Pass:</label></td>
          <td><input id="upass" type="password" name="upass" size="16" value=""></td>
        </tr>
        <tr>
          <td colspan="2"><input type="submit" name="lsub" value="Login"></td>
        </tr>
      </table>
    </form>
  </div>
<?php
  echo '</body></html>';
  exit();
}

echo '<div class="menicko">'; // meníčko
echo '<a href="?logout=1">Logout</a>&nbsp;&nbsp;';
echo '<a href="?">Zoznam hier</a>';
echo '</div>';

$ui = new GUI();

if ($gameId == 0) { // no game selected
	echo '<div style="margin:15px">';
		echo '<table class="simple">';
		echo '<thead>';
		echo '<tr><th>id</th><th>Názov</th><th>Hráči</th><th>Stav</th></tr>';
		echo '</thead><tbody>';
		while($row=$res->fetch_assoc()) {
			echo '<tr>';
			echo '<td>'.$row['g_id'].'</td>';
			echo '<td><a href="?gid='.$row['g_id'].'">'.$row['g_title'].'</a></td>';
			echo '<td>'.$row['g_players'].'</td>';
			echo '<td>'.($row['g_active']?(($row['g_active']>1)?'ukončená':'prebieha'):'pripravená').'</td>';
			echo '</tr>';
		}
		$res->close();
		echo '</tbody>';
		echo '</table>';
	echo '<br><button onclick="game_new()">Nová hra</button>';
	echo '</div>';
} else { // game selected

  if (is_null($row)) {
    echo '<p>Neplatná hra</p>';
    exit();
  }
  
  if ($row['g_active']) {
		$game=unserialize(base64_decode($row['g_data']));
		$pid=$game->get_player_id_by_name($_SESSION['uname']);
		if ($pid!==false) 
			$ui->show_game($gid, $pid);
  } else {
		$ui->show_game($gid, -1);
	}
}
?>
</body></html>