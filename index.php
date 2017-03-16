<?php
use Comp\Kacky\DB;
use Comp\Kacky\Model\User;
use Comp\GameManager\Message;
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
if (isset($_POST['login'])) {
  header('Content-Type: application/json');
  $user = new User(null);
  try {
    switch($_POST['login']) {
      case 'form':
        $username = substr($_POST['uname'], 0, 16);
        $password = substr($_POST['upass'], 0, 16);
        $user->verifyFromDB($username, $password);
        break;

      case 'facebook':
        $user->verifyFacebook($_POST['token']);
        break;

      case 'google':
        $user->verifyGoogle($_POST['token']);
        break;
    }
    $session->set('isLogged', true);
    $session->set('username', $user->getName());
    $session->set('userId', $user->getId());
    echo Message::ok('Logged in');
  }
  catch (\Exception $e) {
    $session->set('isLogged', false);
    echo Message::error($e->getMessage());
    sleep(3);
  }
  exit();
}

// handle logout
if (isset($_POST['logout'])) {
  $session->clear();
  $session->set('isLogged', false);
  exit();
}

?><!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <link rel="icon" type="image/png" href="assets/i/duck.png">
  <link rel="stylesheet" type="text/css" href="assets/style.css?v=3">
  <title>Kačice z našej police</title>
  <link rel="stylesheet" href="assets/jquery/jquery-ui.min.css">
  <script src="assets/jquery/jquery-2.1.4.min.js"></script>
  <script src="assets/jquery/jquery-ui.min.js"></script>
  <script src="assets/jquery/jquery.ui.touch-punch.min.js"></script>
  <script src="https://apis.google.com/js/platform.js?onload=initGLogin" async defer></script>
  <script src="assets/login.js?v=1"></script>
  <script src="assets/gui.js?v=7"></script>
</head>
<body>
<!-- FB login support code -->
<div id="fb-root"></div>
<script>(function(d, s, id) {
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) return;
    js = d.createElement(s); js.id = id;
    js.src = "//connect.facebook.net/sk_SK/sdk.js#xfbml=1&version=v2.8&appId=1833022813615910";
    fjs.parentNode.insertBefore(js, fjs);
  }(document, 'script', 'facebook-jssdk'));</script>
<!-- End of FB login support code -->
<?php
if (!$session->get('isLogged', false)) {
  ?>
  <div class="maincontent" style="width:266px">
    <h2>Kačice z našej police</h2>
    <?php
    if (count($session->getFlashBag()->get('login_errors'))) {
      echo '<p>' . implode('<br>', $session->getFlashBag()->get('login_errors')) . '</p>';
    }
    ?>
    <form method="post">
      <input id="uname" type="text" name="uname" size="16" value="" placeholder="Prihlasovacie meno"><br>
      <input id="upass" type="password" name="upass" size="16" value="" placeholder="Heslo"><br>
      <button id="loginButton" type="button" name="lsub" style="width: 266px" onclick="submitFormLogin()">Prihlásiť sa</button>
    </form>
    <hr>
    <button type="button" class="button-fb" onclick="submitFBLogin()"><img src="assets/i/fb-logo.png" alt="f-logo"/><span>Facebook prihlásenie</span></button>
    <button type="button" class="button-g" onclick="submitGLogin()"><img src="assets/i/g-logo.svg" alt="g-logo"/><span>Google+ prihlásenie</span></button>
  </div>
<?php
}

else {
?>
  <div class="menicko">
    <a href="?"><img src="assets/i/duck_logo_flip.png" alt="logo"/> <?= $session->get('username') ?></a>
    <a href="javascript:back_to_gameList()">Zoznam hier</a>
    <a href="javascript:logout()">Logout</a>
    <a href="?"><img src="assets/i/duck_logo.png" alt="logo"/></a>
  </div>

  <div id="conn-alert" style="display: none">Pripojenie k serveru zlyhalo. <a id="conn-alert-try" href="javascript:ws_connect()">Skúsiť znova</a></div>

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
            <input id="message-input" type="text" name="msg-input" value="" placeholder="vylej si srdce&hellip;">
          </div>

        </td>
        <!-- column 2 -->
      </tr>
    </table>
  </div>

  <!-- Cakanie na vsetkych hracov pred spustenim hry -->
  <div class="maincontent" data-phase="beforeGame" style="display:none">
    <h2 id="gtitle"></h2>

    <table id="tplayers"></table>
    <br>
    <button type="button" onclick="unsubscribe()">Odhlásiť sa z hry</button>
    <button id="bstart" type="button" style="display:none" onclick="game_start()">Začať hru</button>
  </div>

  <!-- Zoznam hier -->
  <div class="maincontent" data-phase="noGame">
    <h2>Kačice z našej police</h2>
    <table class="simple">
      <tbody id="tb-game-list">
      </tbody>
    </table>
    <br><button id="btNew" type="button" onclick="game_new()" style="display:none">Nová hra</button>
  </div>

  <script>
    $(function() {
      gui_start(<?= $gameId ?>, '<?= $dbConfig['ws_uri'] ?>');
    });
  </script>
  <?php
}
?>
</body>
</html>
