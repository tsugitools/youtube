<?php
require_once "../config.php";

// The Tsugi PHP API Documentation is available at:
// http://do1.dr-chuck.com/tsugi/phpdoc/

use \Tsugi\Util\Net;
use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Settings;

// Allow this to just be launched as a naked URL w/o LTI
$LTI = LTIX::session_start();

// No tracking if they are not logged in
if ( ! isset($USER->id) || ! isset($LINK->id) ) {
    Net::send403('Not logged in');
    return;
}

$state = (int) U::get($_POST, 'state', 'unknown');
$duration = (int) U::get($_POST, 'duration', false);
$interval = (int) U::get($_POST, 'interval', false);
$vector = U::get($_POST, 'vector', false);
$rate = U::get($_POST, 'rate', false);

if ( $duration && $interval && is_array($vector) ) {
    // Happy
} else {
    Net::send400('Missing POST data');
    return;
}

function zpad($i) {
    return str_pad($i."", 3, "0", STR_PAD_LEFT);
}

// TODO: Add ellapsed time sanity checking...

$insert_columns = "";
$insert_values = "";
$update_sql = "";
$values = array();
$i = 0;
foreach($vector as $k => $v ) {
    if ( $k != $i || $k < 0 || $k >= 120 ) {
        Net::send400('Invalid bucket index');
        return;
    }
    $i++;
    if ( ! is_numeric($v) ) {
        Net::send400('Non-numeric bucket value');
        return;
    }
    if ( $v == 0 ) continue;

    // Got a live one!
    $col = 'b'.zpad($k);
    if ( strlen($insert_columns) > 0 ) $insert_columns .= ", ";
    if ( strlen($insert_values) > 0 ) $insert_values .= ", ";
    if ( strlen($update_sql) > 0 ) $update_sql .= ", ";

    $insert_columns .= $col;
    $insert_values .= ':'.$col;
    $update_sql .= $col.'='.$col.'+:'.$col;
    $values[':'.$col] = $v;
    
}

header("Content-Type: text/plain");

// If we did not find any suitable data to put in the DB.
if (count($values) < 1 ) {
    echo("no new data...\n");
    return;
}

// Prepare for database...
$values[':link_id'] = $LINK->id;
$values[':seconds'] = $duration;
$values[':width'] = $interval;

// We don't track instructor views in the overall...
if ( $USER->instructor ) {
    $sql = "UPDATE {$CFG->dbprefix}youtube_views SET 
        seconds = :seconds, width = :width
    WHERE link_id = :link_id";
    $PDOX->queryDie($sql, array(
        ':link_id' => $LINK->id,
        ':seconds' => $duration,
        ':width' => $interval
    ));
} else {
    $sql = "INSERT into {$CFG->dbprefix}youtube_views 
    ( link_id, seconds, width, 
    ".$insert_columns.")
    VALUES (:link_id, :seconds, :width,
    ".$insert_values.")
    ON DUPLICATE KEY UPDATE updated_at = NOW(), \n"
    . $update_sql;

    echo($sql); echo("\n"); var_dump($values);echo("\n");

    $PDOX->queryDie($sql, $values);
}

// Insert the user record
$sql = "INSERT into {$CFG->dbprefix}youtube_views_user
( link_id, user_id, seconds, width, 
".$insert_columns.")
VALUES (:link_id, :user_id, :seconds, :width,
".$insert_values.")
ON DUPLICATE KEY UPDATE updated_at = NOW(), \n";

$sql .= "seconds = :seconds, width = :width,\n";

$sql .=  $update_sql;

$values[':user_id'] = $USER->id;
/*
echo($sql);
echo("\n");
var_dump($values);
*/

$PDOX->queryDie($sql, $values);

// Check to see if we are to award grade based on % watched
$watched = Settings::linkGet('watched', false);
if ( ! $watched ) return;

if ( ! $RESULT->id || $RESULT->grade >= 1.0 ) return;

// Only send every 30 seconds
$last_grade_send = isset($_SESSION['last_grade_send']) ? $_SESSION['last_grade_send'] : 0;
if ( $state == 'playing' && time() < ($last_grade_send + 30) ) {
    echo("Grade not sent last=$last_grade_send time=".time()."\n");
    return;
}

$sql = "SELECT * FROM {$CFG->dbprefix}youtube_views_user
WHERE link_id = :link_id AND user_id = :user_id LIMIT 1";

$row = $PDOX->rowDie($sql, array(
        ':link_id' => $LINK->id,
        ':user_id' => $USER->id
));

if ( ! $row ) return;

$ticks = 0;
for($i=0; $i<120;$i++) {
    $col = 'b'.zpad($i);
    if ( $row[$col] > 0 ) $ticks++;
}

$watched = ($ticks / 120.0);
$grade = $watched;
if ( $grade > 0.9) $grade = 1.0;
echo("ticks=$ticks duration=$duration\n");
echo("watched=$watched grade=".($grade*100)."\n");

if ( $grade > 1.0 ) $grade = 1.0;
if ( $RESULT->grade >= $grade ) return;

$RESULT->gradeSend($grade, false);
echo("Grade sent $grade\n");

$_SESSION['last_grade_send'] = time();


