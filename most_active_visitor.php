<?php
/**
 *
 * 10 Pengunjung Teraktif Berdasarkan Rentang Tanggal
 * + Filter Jenis Keanggotaan
 *
 */

// key to authenticate
define('INDEX_AUTH', '1');

// main system configuration
require '../../../../sysconfig.inc.php';

// IP based access limitation
require LIB.'ip_based_access.inc.php';
do_checkIP('smc');
do_checkIP('smc-reporting');

// start the session
require SB.'admin/default/session.inc.php';
require SB.'admin/default/session_check.inc.php';

// privileges checking
$can_read = utility::havePrivilege('reporting', 'r');
if (!$can_read) {
    die('<div class="errorBox">'.__('You don\'t have enough privileges to access this area!').'</div>');
}

require SIMBIO.'simbio_GUI/form_maker/simbio_form_element.inc.php';

$page_title = 'Library Visitor Report';
$reportView = isset($_GET['reportView']);


// =====================================================
// FORM FILTER
// =====================================================
if (!$reportView) {

    // default tanggal
    $date_start = date('Y-m-01');
    $date_end   = date('Y-m-d');

    // ambil jenis keanggotaan
    $member_type_q = $dbs->query("SELECT member_type_id, member_type_name FROM mst_member_type ORDER BY member_type_name ASC");
    $member_type_options = array();
    $member_type_options[] = array('', __('Semua Jenis'));
    while ($mt = $member_type_q->fetch_assoc()) {
        $member_type_options[] = array($mt['member_type_id'], $mt['member_type_name']);
    }
?>
<fieldset>
    <div class="per_title">
        <h2><?php echo __('Laporan 10 Pengunjung Teraktif'); ?></h2>
    </div>

    <div class="infoBox"><?php echo __('Report Filter'); ?></div>

    <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>" target="reportView">

    <div style="display:flex; gap:20px; align-items:flex-end; flex-wrap:wrap;">
    
        <div id="filterForm">
            <div class="divRow">
                <div class="divRowLabel"><?php echo __('Tanggal Awal'); ?></div>
                <div class="divRowContent">
                    <input type="date" name="date_start" value="<?php echo $date_start; ?>" />
                </div>
            </div>
        </div>
    
        <div id="filterForm">
            <div class="divRow">
                <div class="divRowLabel"><?php echo __('Tanggal Akhir'); ?></div>
                <div class="divRowContent">
                    <input type="date" name="date_end" value="<?php echo $date_end; ?>" />
                </div>
            </div>
        </div>
    
        <div id="filterForm">
            <div class="divRow">
                <div class="divRowLabel"><?php echo __('Jenis Keanggotaan'); ?></div>
                <div class="divRowContent">
                    <?php
                    echo simbio_form_element::selectList(
                        'member_type',
                        $member_type_options,
                        ''
                    );
                    ?>
                </div>
            </div>
        </div>
    
    </div>


        <div style="padding-top:10px;clear:both;">
            <input type="submit" value="<?php echo __('Apply Filter'); ?>" />
            <input type="hidden" name="reportView" value="true" />
        </div>

    </form>
</fieldset>

<iframe name="reportView" id="reportView"
    src="<?php echo $_SERVER['PHP_SELF'].'?reportView=true'; ?>"
    frameborder="0" style="width:100%;height:1000px;"></iframe>

<?php
exit;
}

// =====================================================
// HALAMAN LAPORAN
// =====================================================
ob_start();

// ambil parameter
$date_start = isset($_GET['date_start']) ? $_GET['date_start'] : date('Y-m-01');
$date_end   = isset($_GET['date_end'])   ? $_GET['date_end']   : date('Y-m-d');
$member_type = isset($_GET['member_type']) ? (int)$_GET['member_type'] : '';

echo '<div class="printPageInfo">
Laporan Pengunjung Teraktif
<a class="printReport" onclick="window.print()" href="#">'.__('Print Current Page').'</a>
</div>';

echo "<center>
<h3>Laporan 10 Pengunjung Teraktif<br>
Periode $date_start s.d. $date_end
</h3>
</center>
<hr/>";

// query utama
$sql_str = "
SELECT
    v.member_id,
    COUNT(v.member_id) AS jml,
    m.member_name,
    m.pin
FROM visitor_count AS v
JOIN member AS m ON v.member_id = m.member_id
JOIN mst_member_type AS mt ON m.member_type_id = mt.member_type_id
WHERE DATE(v.checkin_date) BETWEEN '$date_start' AND '$date_end'
";

if ($member_type != '') {
    $sql_str .= " AND m.member_type_id = $member_type ";
}

$sql_str .= "
GROUP BY v.member_id
ORDER BY jml DESC
LIMIT 10
";

$visitor_q = $dbs->query($sql_str);
?>

<table class="table dataListPrinted" border="1" cellpadding="5" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>No.</th>
            <th>ID Anggota</th>
            <th>Nama Lengkap</th>
            <th>Kelas / Grup</th>
            <th>Jumlah Kunjungan</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $no = 1;
        if ($visitor_q && $visitor_q->num_rows > 0) {
            while ($data = $visitor_q->fetch_row()) {
                echo "
                <tr>
                    <td align='center'>{$no}</td>
                    <td>{$data[0]}</td>
                    <td>{$data[2]}</td>
                    <td>{$data[3]}</td>
                    <td align='center'>{$data[1]}</td>
                </tr>";
                $no++;
            }
        } else {
            echo "<tr><td colspan='5' align='center'>Tidak ada data</td></tr>";
        }
        ?>
    </tbody>
</table>

<?php
$content = ob_get_clean();
require SB.'/admin/'.$sysconf['admin_template']['dir'].'/printed_page_tpl.php';
