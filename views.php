<?php
require_once "../config.php";

// The Tsugi PHP API Documentation is available at:
// http://do1.dr-chuck.com/tsugi/phpdoc/

use \Tsugi\Util\Net;
use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Settings;
use \Tsugi\UI\SettingsForm;

// Allow this to just be launched as a naked URL w/o LTI
$LTI = LTIX::session_start();

if ( ! $USER->id || ! $LINK->id || ! $USER->instructor ) {
    Net::send403();
    return;
}

// Render view
$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
// https://codepen.io/team/css-tricks/pen/pvamy
// https://css-tricks.com/seamless-responsive-photo-grid/

?>
<p>
<a href="index" class="btn btn-default">Back</a>
</p>
<div id="chartWrapper"><div id="chart_div" style="width: 90%; height: 400px;">Loading View Stats</div></div>

<?php

$OUTPUT->footerStart();
?>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script>

function drawBasic() {
    var chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
    var graphData = [['% Video Time', 'Views']];
    $.getJSON("<?= addSession('viewdata.php') ?>", function(functionData) {
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
