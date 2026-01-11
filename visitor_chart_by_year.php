<?php
/**
 * Grafik Kunjungan Per Tahun Berdasarkan Tipe Keanggotaan
 */

define('INDEX_AUTH', '1');
require '../../../../sysconfig.inc.php';
require LIB.'ip_based_access.inc.php';
do_checkIP('smc');
do_checkIP('smc-reporting');
require SB.'admin/default/session.inc.php';
require SB.'admin/default/session_check.inc.php';

$can_read = utility::havePrivilege('reporting','r');
if(!$can_read){ die('<div class="errorBox">'.__('You don\'t have enough privileges to access this area!').'</div>'); }

require SIMBIO.'simbio_GUI/form_maker/simbio_form_element.inc.php';

$reportView = isset($_GET['reportView']);

$default_start = 2020;
$default_end   = date('Y');

if(!$reportView): ?>
<fieldset>
 <div class="per_title"><h2><?php echo __('Laporan Grafik Kunjungan Per Tahun'); ?></h2></div>
 <div class="infoBox"><?php echo __('Report Filter'); ?></div>
 <div class="sub_section">
     <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>" target="reportView">
         <?php
         $current_year = date('Y');
         $year_options = [];
         for ($y = 2000; $y <= $current_year; $y++) {
             $year_options[] = [$y, $y];
         }
         ?>
         <div class="divRow">
             <div class="divRowLabel"><?php echo __('From Year'); ?></div>
             <div class="divRowContent"><?php echo simbio_form_element::selectList('start_year', $year_options, $default_start); ?></div>
         </div>
         <div class="divRow">
             <div class="divRowLabel"><?php echo __('To Year'); ?></div>
             <div class="divRowContent"><?php echo simbio_form_element::selectList('end_year', $year_options, $default_end); ?></div>
         </div>
         <div style="padding-top:10px; clear:both;">
             <input type="submit" name="applyFilter" value="<?php echo __('Apply Filter'); ?>" />
             <input type="hidden" name="reportView" value="true" />
         </div>
     </form>
 </div>
</fieldset>
<iframe name="reportView" id="reportView"
src="<?php echo $_SERVER['PHP_SELF'].'?reportView=true&start_year='.$default_start.'&end_year='.$default_end; ?>"
frameborder="0" style="width:100%; height:1000px;"></iframe>

<?php else:
ob_start();

$start_year = isset($_GET['start_year']) ? (int)$_GET['start_year'] : $default_start;
$end_year   = isset($_GET['end_year']) ? (int)$_GET['end_year'] : $default_end;
if ($end_year < $start_year) { $end_year = $start_year; }

$years = [];
for ($y = $start_year; $y <= $end_year; $y++) { $years[$y] = $y; }

function randColor(){ return sprintf('%06X', mt_rand(0, 0xFFFFFF)); }

$output = '<table class="border" cellpadding="3" cellspacing="0" style="width:100%">';
$output .= '<tr><td class="dataListHeaderPrinted">'.__('Member Type').'</td>';
foreach($years as $y){ $output .= '<td class="dataListHeaderPrinted">'.$y.'</td>'; }
$output .= '</tr>';

$member_types = [];
$qmt = $dbs->query("SELECT member_type_id, member_type_name FROM mst_member_type");
while($d=$qmt->fetch_row()){ $member_types[$d[0]] = $d[1]; }

$outvisit = '';
$total_year = array_fill_keys(array_keys($years), 0);
$r=1;

foreach($member_types as $id=>$name){
 $rc = ($r%2==0)?'alterCellPrinted':'alterCellPrinted2';
 $output .= '<tr><td class="'.$rc.'">'.$name.'</td>';

 $dataset = '{fillColor:"#'.randColor().'", title:"'.$name.'", data:[';

 foreach($years as $y){
  $sql = "SELECT COUNT(visitor_id) FROM visitor_count AS v
          LEFT JOIN member AS m ON m.member_id=v.member_id
          WHERE m.member_type_id=$id AND v.checkin_date LIKE '$y-%'";
  $q = $dbs->query($sql);
  $d = $q->fetch_row();
  $count = (int)$d[0];

  $output .= '<td class="'.$rc.'">'.$count.'</td>';

  $dataset .= $count.',';

  $total_year[$y] += $count;
 }
 $dataset .= ']},';
 $outvisit .= $dataset;

 $output .= '</tr>';
 $r++;
}

$output .= '<tr><td class="dataListHeaderPrinted"><strong>Total</strong></td>';
foreach($years as $y){ $output .= '<td class="dataListHeaderPrinted">'.$total_year[$y].'</td>'; }
$output .= '</tr></table>';

echo '<div class="printPageInfo">Grafik Kunjungan Per Tahun <a class="printReport" onclick="window.print()" href="#">Print</a></div>';
?>
<style>
@media print {
  body {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
}
</style>

<center><h2>Grafik Kunjungan Per Tahun Berdasarkan Tipe Keanggotaan</h2></center>

<canvas id="chart" width="1100" height="300"></canvas>
<div id="lChart" style="padding:6px 10px; border:1px solid #ccc; margin:10px 0;"></div>
<script src="<?php echo JWB; ?>chartjs/Chart.min.js"></script>
<script>
var barChartData = {
 labels:[<?php foreach($years as $y) echo '"'.$y.'",'; ?>],
 datasets:[<?php echo $outvisit; ?>]
};
new Chart(document.getElementById('chart').getContext('2d')).Bar(barChartData);
</script>
<?php
echo $output;
$content = ob_get_clean();
require SB.'/admin/'.$sysconf['admin_template']['dir'].'/printed_page_tpl.php';
endif;
?>
