<?php
use Comp\Kacky\GUI;
use Comp\Kacky\DB;

$_escape_int=array('gid'=>0);
include('vendor/autoload.php');

function redirect($url) {
  echo "<script>window.location.replace('".$url."')</script>";
}
?><!DOCTYPE html>
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
<p>Development verzia [websocket]</p>
<?php

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

$ui = new GUI();

?>
<script>
function game_new() {
  var title = prompt("Zadaj meno hry");
  if ((title == null) || (title == '')) return;
	
  //noinspection JSUnresolvedFunction
  $.post("ajax.php", {
    cmd: "game_new",
    title: title
  }, function(data) {
    window.location.replace("?gid="+data);
  });
}
</script>
<?php
$db = DB::getInstance();
$uid=$_SESSION['uid'];
if ($gid==0) { // no game selected

	// cleanup inactive games after 60 minutes since creation
	$db->q("DELETE FROM game_kacky WHERE g_active=0 AND TIMESTAMPDIFF(MINUTE, g_ts, NOW()) > 60");
	// cleanup active games after 1 day since last activity, and finished games after 30 minutes since last activity
	$db->q(
	  "DELETE FROM game_kacky
    USING game_kacky
      LEFT JOIN (
        SELECT g_id, COALESCE(TIMESTAMPDIFF(MINUTE, MAX(m_ts), NOW()), 10000) AS age
        FROM message
        GROUP BY g_id
      ) AS t2 USING (g_id)
    WHERE (g_active=1 AND age > 1440) OR (g_active=2 AND age > 30)"
  );

	$res=$db->q(
	  "SELECT game_kacky.g_id, g_title, g_players, g_active, u_id IS NOT NULL AS in_game
		FROM game_kacky
		LEFT JOIN user2game ON game_kacky.g_id=user2game.g_id AND u_id=$uid
		LEFT JOIN (
			SELECT g_id, GROUP_CONCAT(u_name SEPARATOR ', ') AS g_players
			FROM user2game
			JOIN user USING (u_id)
			GROUP BY g_id
		) AS t2 ON game_kacky.g_id=t2.g_id
		WHERE g_active=0 OR u_id IS NOT NULL"
	);

	echo '<div style="margin:15px">';
	if ($res->num_rows > 10) echo '<button onclick="game_new()">Nová hra</button>';
	if ($res->num_rows) {
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
	} else echo 'Hra ešte nebola vytvorená.<br>';
	echo '<br><button onclick="game_new()">Nová hra</button>';
	echo '</div>';
} else { // game selected
  $row=$db->getrow(
    "SELECT g_count, g_active, g_data, u_id IS NOT NULL AS in_game
		FROM game_kacky
		LEFT JOIN user2game ON game_kacky.g_id=user2game.g_id AND u_id=$uid
		LEFT JOIN (
			SELECT g_id, COUNT(*) AS g_count
			FROM user2game
			JOIN user USING (u_id)
			GROUP BY g_id
		) AS t2 ON game_kacky.g_id=t2.g_id
		WHERE (g_active=0 OR u_id IS NOT NULL) AND game_kacky.g_id=$gid"
	);

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
?></body></html>