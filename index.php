<?php
require_once "../config.php";

// The Tsugi PHP API Documentation is available at:
// http://do1.dr-chuck.com/tsugi/phpdoc/

use \Tsugi\Util\Net;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Settings;
use \Tsugi\UI\SettingsForm;

// We don't need much
$LTI = \Tsugi\Core\LTIX::requireData(array('link_id'));

// Handle the incoming post first
if ( SettingsForm::handleSettingsPost() ) {
    header('Location: '.addSession('index.php') ) ;
    return;
}

// Get the video
$v = Settings::linkGet('v', false);
if ( ! $v ) $v = isset($_GET['v']) ? $_GET['v'] : false;
if ( ! $v ) $v = isset($_SESSION['v']) ? $_SESSION['v'] : false;
if ( $v ) $_SESSION['v'] = $v;

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

if ( $USER->instructor ) {
echo "<p style='text-align:right;'>";
if ( $CFG->launchactivity ) {
    echo('<a href="analytics" class="btn btn-default">Analytics</a> ');
}
SettingsForm::button(false);
SettingsForm::start();
SettingsForm::text('v','Please enter a YouTube video ID');
SettingsForm::end();
}
if ( ! $v ) {
    echo("<p>Video has not yet been configured</p>\n");
} else {
?>
<div class="container">
<iframe src="//www.youtube.com/embed/<?= urlencode($v) ?>" 
frameborder="0" allowfullscreen class="video"></iframe>
</div>
<?php
}
$OUTPUT->footerStart();
$OUTPUT->footerEnd();
