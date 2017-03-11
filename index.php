<?php
use Comp\Kacky\DB;
use Comp\Kacky\Model\User;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

include('vendor/autoload.php');

// prepare Session
$dbConfig = DB::getConfig();
$session = new Session(new NativeSessionStorage([], new PdoSessionHandler(DB::getPDO($dbConfig), ['db_table'=>$dbConfig['session_table']])));
$session->setName($dbConfig['session_name']);

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
    $session->set('userId', $user->getId());
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
  <link rel="icon" type="image/png" href="assets/i/duck.png">
  <link rel="stylesheet" type="text/css" href="assets/style.css">
  <title>Kačice z našej police</title>
  <link rel="stylesheet" href="assets/jquery/jquery-ui.min.css">
  <script src="assets/jquery/jquery-2.1.4.min.js"></script>
  <script src="assets/jquery/jquery-ui.min.js"></script>
  <script src="assets/jquery/jquery.ui.touch-punch.min.js"></script>
</head>
<body>
<?php
if (!$session->get('isLogged', false)) {
  ?>
  <div class="maincontent">
    <h2>Kačice z našej police</h2>
    <?php
    echo '<p>' . implode('<br>', $session->getFlashBag()->get('login_errors')) .'</p>';
    ?>
    <form method="post">
      <table>
        <tr>
          <td><label for="uname">Username:</label></td>
          <td><input id="uname" type="text" name="uname" size="16" value=""></td>
        </tr>
        <tr>
          <td><label for="upass">Password:</label></td>
          <td><input id="upass" type="password" name="upass" size="16" value=""></td>
        </tr>
        <tr>
          <td colspan="2"><input type="submit" name="lsub" value="Login"></td>
        </tr>
      </table>
    </form>
  </div>
<?php
}

else {
?>
  <div class="menicko">
    <a href="?logout=1">Logout</a>&nbsp;&nbsp;
    <a href="?">Zoznam hier</a>
  </div>

  <!-- Herna plocha pocas hry -->
  <div data-phase="inGame" style="display:none">
    <!-- statusbar -->
    <div class="statusbar">
      <table class="lives">
        <tr id="lives-row">
          <td class="player"></td>
          <td class="player"></td>
          <td class="player"></td>
          <td class="player"></td>
          <td class="player"></td>
          <td class="player"></td>
        </tr>
      </table>
    </div>

    <!-- column 1 - game -->
    <table class="content">
      <tr>
        <td class="column1">
          <!-- river -->
          <div id="river" class="river"></div>

          <!-- pile -->
          <div id="pile" class="pile">
            <div class="pile-card"></div>
          </div>

          <!-- hand -->
          <div class="hand">
            <div id="hand-inner" class="hand-inner"></div>
          </div>

          <!-- deck -->
          <div id="deck" class="deck">
            <div class="deck-card"></div>
          </div>

          <!-- debug -->
          <div id="debug0" class="debug"></div>

        </td>
        <!-- end column 1 - game -->
        <!-- column 2 - messages -->
        <td class="column2">

          <!-- text messaging -->
          <div id="message-box" class="message-box"></div>
          <div class="message-input">
            <input id="message-input" type="text" name="msg-input" value="" placeholder="vylej si srdce&hellip;" disabled="disabled">
          </div>

        </td>
        <!-- column 2 -->
      </tr>
    </table>
  </div>

  <!-- Cakanie na vsetkych hracov pred spustenim hry -->
  <div class="maincontent" data-phase="beforeGame" style="display:none">
    <h2 id="gtitle"></h2>
    <p id="gcount"></p>

    <table id="tplayers"></table>
    <button type="button" onclick="unsubscribe()">Odhlásiť sa z hry</button>
    <button id="bstart" type="button" style="display:none" onclick="game_start()">Začať hru</button>
  </div>

  <!-- Zoznam hier -->
  <div class="maincontent" data-phase="noGame">
    <h2>Kačice z našej police</h2>
    <table class="simple">
      <thead>
      <tr><th>id</th><th>Názov</th><th>Hráči</th><th>Stav</th></tr>
      </thead>
      <tbody id="tb-game-list">
      <tr class="game-template">
        <td class="game-id"></td>
        <td class="game-title"></td>
        <td class="game-players"></td>
        <td class="game-active"></td>
      </tr>
      </tbody>
    </table>
    <br><button type="button" onclick="game_new()">Nová hra</button>
  </div>

  <script>
    var gid = <?= $gameId ?>;
    var ws_uri = '<?= $dbConfig['ws_uri'] ?>';
  </script>
  <!-- javascript gui functionality -->
  <script src="assets/gui.js"></script>

  <?php
}
?>
</body>
</html>