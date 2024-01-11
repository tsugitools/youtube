<?php
require_once "../config.php";

// The Tsugi PHP API Documentation is available at:
// http://do1.dr-chuck.com/tsugi/phpdoc/

use \Tsugi\Util\Net;
use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Settings;
use \Tsugi\Core\User;
use \Tsugi\UI\SettingsForm;

// Allow this to just be launched as a naked URL w/o LTI
$LTI = LTIX::requireData();

if ( ! $USER->id || ! $LINK->id || ! $USER->instructor ) {
    Net::send403();
    return;
}

$user_id = U::get($_GET, 'user_id', 0);
if ( $USER->instructor ) {
    $sql = "SELECT V.user_id AS user_id, displayname, email
        FROM {$CFG->dbprefix}youtube_views_user AS V
        JOIN {$CFG->dbprefix}lti_user AS U ON V.user_id = U.user_id
        WHERE link_id = :link_id";

    $rows = $PDOX->allRowsDie($sql, array(
        ':link_id' => $LINK->id
    ));
}


$menu = new \Tsugi\UI\MenuSet();
$menu->addLeft(__('Back'), 'index.php');

// Render view
$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav($menu);
// https://codepen.io/team/css-tricks/pen/pvamy
// https://css-tricks.com/seamless-responsive-photo-grid/

if ( count($rows) > 0 ) {
    echo("<form>\n");
    echo('<select name="user_id" onchange="this.form.submit()">'."\n");
    echo('   <option value="0">All Users</option>'."\n");
    foreach($rows as $row) {
        $display = User::getDisplay($row['user_id'], $row['displayname'], $row['email']);
        echo('   <option value="'.$row['user_id'].'"');
        if ( $row['user_id'] == $user_id ) echo (" selected ");
        echo('>'.htmlentities($display).'</option>'."\n");
    }
    echo("</select><form>\n");
}
?>
<div id="chartWrapper"><div id="chart_div" style="width: 90%; height: 400px;">Loading View Stats</div></div>

<?php

$data_url = 'viewdata.php';
if ( $user_id > 0 ) $data_url .= '?user_id=' . $user_id;
$data_url = addSession($data_url);

$OUTPUT->footerStart();
?>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script>

function drawBasic() {
    var chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
    var graphData = [['% Video Time', 'Views']];
    $.getJSON("<?= $data_url ?>", function(functionData) {
            console.log(functionData);
            if ( functionData.error ) {
                $('#chart_div').text(functionData.error);
                return;
            }
            if ( ! functionData.vector ) {
                $('#chart_div').text('No data returned');
                return;
            }

            var vector = functionData.vector;
            console.log(vector);
            var options = {
                    title: functionData.title,
                    hAxis: {title: functionData.hAxis},
                    vAxis: {title: functionData.vaxis},
                    legend: 'none'
            };
            chart.draw(google.visualization.arrayToDataTable(vector), options);
            setInterval(drawBasic, 15000);  // Auto refresh
    });
}

$(document).ready(function() {
    google.charts.load('current', {packages: ['corechart', 'bar']});
    google.charts.setOnLoadCallback(drawBasic);
});
</script>
<?php
$OUTPUT->footerEnd();
