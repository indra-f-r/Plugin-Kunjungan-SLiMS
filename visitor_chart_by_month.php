<?php
/**
 * Laporan Grafik Kunjungan Per Bulan Berdasarkan Tipe Keanggotaan
 */

define('INDEX_AUTH', '1');
require '../../../../sysconfig.inc.php';
require LIB.'ip_based_access.inc.php';
do_checkIP('smc');
do_checkIP('smc-reporting');

require SB.'admin/default/session.inc.php';
require SB.'admin/default/session_check.inc.php';

$can_read = utility::havePrivilege('reporting', 'r');
if (!$can_read) {
    die('<div class="errorBox">'.__('You don\'t have enough privileges to access this area!').'</div>');
}

require SIMBIO.'simbio_GUI/form_maker/simbio_form_element.inc.php';

$reportView = isset($_GET['reportView']);

if (!$reportView): ?>

<fieldset>
    <div class="per_title"><h2><?php echo __('Laporan Grafik Kunjungan Berdasarkan Tipe Keanggotaan'); ?></h2></div>
    <div class="infoBox"><?php echo __('Report Filter'); ?></div>
    <div class="sub_section">
        <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>" target="reportView">
            <div class="divRow">
                <div class="divRowLabel"><?php echo __('Year'); ?></div>
                <div class="divRowContent">
                    <?php
                    $current_year = date('Y');
                    $year_options = [];
                    for ($y = $current_year; $y > 1999; $y--) {
                        $year_options[] = [$y, $y];
                    }
                    echo simbio_form_element::selectList('year', $year_options, $current_year);
                    ?>
                </div>
            </div>
            <div style="padding-top:10px; clear:both;">
                <input type="submit" name="applyFilter" value="<?php echo __('Apply Filter'); ?>" />
                <input type="hidden" name="reportView" value="true" />
            </div>
        </form>
    </div>
</fieldset>
<iframe name="reportView" id="reportView" src="<?php echo $_SERVER['PHP_SELF'].'?reportView=true'; ?>" frameborder="0" style="width:100%; height:1000px;"></iframe>

<?php
else:

ob_start();

$months = [
    '01' => __('Jan'), '02' => __('Feb'), '03' => __('Mar'), '04' => __('Apr'),
    '05' => __('May'), '06' => __('Jun'), '07' => __('Jul'), '08' => __('Aug'),
    '09' => __('Sep'), '10' => __('Oct'), '11' => __('Nov'), '12' => __('Dec')
];

function randColor() {
    return sprintf('%06X', mt_rand(0, 0xFFFFFF));
}

$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// ambil tipe anggota
$types_q = $dbs->query("SELECT member_type_id, member_type_name FROM mst_member_type ORDER BY member_type_name");
$member_types = [];
while ($t = $types_q->fetch_row()) {
    $member_types[$t[0]] = $t[1];
}

$output = '<table class="border" cellpadding="3" cellspacing="0" style="width:100%;">';
$output .= '<tr><td class="dataListHeaderPrinted">'.__('Tipe Anggota').'</td>';
$total_month = [];
foreach ($months as $mnum => $mname) {
    $total_month[$mnum] = 0;
    $output .= '<td class="dataListHeaderPrinted">'.$mname.'</td>';
}
$output .= '</tr>';

$outvisit = '';
$rnum = 1;

foreach ($member_types as $type_id => $type_name) {
    $row_class = ($rnum % 2 == 0) ? 'alterCellPrinted' : 'alterCellPrinted2';
    $output .= '<tr><td class="'.$row_class.'">'.$type_name.'</td>';

    $dataset = '{fillColor:"#'.randColor().'", title:"'.$type_name.'", data:[';

    foreach ($months as $mnum => $mname) {
        $sql = "SELECT COUNT(visitor_id)
                FROM visitor_count AS v
                INNER JOIN member AS m ON v.member_id = m.member_id
                WHERE m.member_type_id = $type_id
                AND v.checkin_date LIKE '$selected_year-$mnum-%'";

        $q = $dbs->query($sql);
        $d = $q->fetch_row();
        $count = (int)$d[0];

        $output .= '<td class="'.$row_class.'">'.$count.'</td>';
        $dataset .= $count.',';
        $total_month[$mnum] += $count;
    }

    $dataset .= ']},';
    $outvisit .= $dataset;

    $output .= '</tr>';
    $rnum++;
}

$output .= '<tr><td class="dataListHeaderPrinted"><strong>Total</strong></td>';
foreach ($months as $mnum => $mname) {
    $output .= '<td class="dataListHeaderPrinted">'.$total_month[$mnum].'</td>';
}
$output .= '</tr></table>';

echo '<div class="printPageInfo">Grafik Kunjungan Tahun <strong>'.$selected_year.'</strong> <a class="printReport" onclick="window.print()" href="#">Print</a></div>';
?>

<center><h2>Grafik Kunjungan Tahun <?php echo $selected_year; ?></h2></center>

<canvas id="chart" height="300" width="1100"></canvas>
<div id="lChart" style="padding:6px 10px; border:1px solid #ccc; margin:10px 0;"></div>

<script src="<?php echo JWB; ?>chartjs/Chart.min.js"></script>
<script>
    var barChartData = {
        labels: [<?php foreach ($months as $m) echo '"'.$m.'",'; ?>],
        datasets: [<?php echo $outvisit; ?>]
    };

    var ctx = document.getElementById('chart').getContext('2d');
    new Chart(ctx).Bar(barChartData);
</script>

<?php
echo $output;
$content = ob_get_clean();
require SB.'/admin/'.$sysconf['admin_template']['dir'].'/printed_page_tpl.php';
endif;
?>
