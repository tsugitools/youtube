<?php
require_once "../config.php";

// The Tsugi PHP API Documentation is available at:
// http://do1.dr-chuck.com/tsugi/phpdoc/

use \Tsugi\Util\Net;
use \Tsugi\Util\U;
use \Tsugi\Util\PS;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Settings;
use \Tsugi\UI\SettingsForm;

$LTI = LTIX::requireData();

$oldv = Settings::linkGet('v', false);
// Handle the incoming post first
$newv = U::get($_POST,'v',false);
if ( $newv && PS::s($newv)->startsWith('http://') || PS::s($newv)->startsWith('https://') ) {
    $_SESSION['error'] = __('Please enter a YouTube ID, not a YouTube URL');
    header('Location: '.addSession('index') ) ;
    return;
}

if ( isset($LINK->id) && SettingsForm::handleSettingsPost() ) {
    if ( $newv && $newv !== $oldv ) {
        $PDOX->queryDie("DELETE FROM {$CFG->dbprefix}youtube_views WHERE link_id = :LI",
            array(':LI' => $LINK->id)
        );
        $PDOX->queryDie("DELETE FROM {$CFG->dbprefix}youtube_views_user WHERE link_id = :LI",
            array(':LI' => $LINK->id)
        );
        $_SESSION['success'] = __('Video ID changed, view tracking analytics reset.');
    }
    header('Location: '.addSession('index') ) ;
    return;
}

// Get the video
$v = Settings::linkGet('v', false);
if ( ! $v ) $v = isset($_GET['v']) ? $_GET['v'] : false;
if ( ! $v ) $v = isset($_SESSION['v']) ? $_SESSION['v'] : false;
if ( $v ) $_SESSION['v'] = $v;
$grade = Settings::linkGet('grade', false);

// Render view
$OUTPUT->header();
// https://www.h3xed.com/web-development/how-to-make-a-responsive-100-width-youtube-iframe-embed
?>
<style>
.container {
    position: relative;
    width: 100%;
    height: 0;
    padding-bottom: 56.25%;
}
.video {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}
</style>
<?php
$OUTPUT->bodyStart();
$OUTPUT->topNav();
// https://codepen.io/team/css-tricks/pen/pvamy
// https://css-tricks.com/seamless-responsive-photo-grid/

if ( $LTI->user && $LTI->user->instructor ) {
echo "<p style='text-align:right;'>";
if ( $CFG->launchactivity ) {
    echo('<a href="analytics" class="btn btn-default">Launches</a> ');
}
echo('<a href="views" class="btn btn-default">Views</a> ');
SettingsForm::button(false);
SettingsForm::start();
SettingsForm::text('v','Please enter a YouTube video ID.  If you change the video ID, time-based view tracking will be reset.');
SettingsForm::checkbox('grade','Give the student a 100% grade as soon as they view this video.');
SettingsForm::checkbox('watched','Give the student a grade from 0-100% based on the time spent viewing this video.');
SettingsForm::end();
$OUTPUT->flashMessages();
}
if ( ! $v ) {
    echo("<p>Video has not yet been configured</p>\n");
} else {
?>
<div class="container">
<?php
if ( $LTI->link ) {
    if ( $grade && $LTI->result && $LTI->result->id && $RESULT->grade < 1.0 ) {
        $RESULT->gradeSend(1.0, false);
    }
?>
<div id="player" class="video">&nbsp;</div>
<?php
} else {
?>
<iframe src="//www.youtube.com/embed/<?= urlencode($v) ?>" 
frameborder="0" allowfullscreen class="video"></iframe>
<?php
}
?>
</div>
<?php
}

// Turn off translate for non-instructors since there is no UI
if ( ! isset($USER) || ! $USER->instructor ) {
    $CFG->google_translate = false;
}

$OUTPUT->footerStart();
if ( $LTI->link ) {
?>
<script>
VIDEO_ID = "<?= urlencode($v) ?>";
TRACKING_URL = "<?= addSession('tracker.php') ?>";
</script>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script src="video.js?v=<?=rand()?>"></script>
<?php
}
$OUTPUT->footerEnd();
