<?php
/**
 * Laporan 3 Kelas/Rombel dengan Kunjungan Terbanyak
 * Berdasarkan Rentang Tanggal
 * SLiMS Compatible - AMAN CETAK
 */

define('INDEX_AUTH', '1');
require '../../../../sysconfig.inc.php';
require LIB.'ip_based_access.inc.php';
do_checkIP('smc');
do_checkIP('smc-reporting');
require SB.'admin/default/session.inc.php';
require SB.'admin/default/session_check.inc.php';

// Hak akses
$can_read = utility::havePrivilege('reporting', 'r');
if (!$can_read) {
    die('<div class="errorBox">Anda tidak memiliki hak akses.</div>');
}

$page_title = 'Laporan 3 Kelas dengan Kunjungan Terbanyak';
$reportView = isset($_GET['reportView']);

/* ===============================
   FORM FILTER
=============================== */
if (!$reportView) {

$date_start = date('Y-m-01');
$date_end   = date('Y-m-d');
?>
<fieldset>
    <div class="per_title">
        <h2>Laporan Kelas/Rombel dengan Kunjungan Terbanyak</h2>
    </div>
    <div class="infoBox">Pilih rentang tanggal kunjungan.</div>

    <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>" target="reportView">
        <div style="display:flex;gap:20px;align-items:center;margin-bottom:10px;">
            <div>
                <b>Tanggal Awal</b><br>
                <input type="date" name="date_start" value="<?php echo $date_start; ?>">
            </div>
            <div>
                <b>Tanggal Akhir</b><br>
                <input type="date" name="date_end" value="<?php echo $date_end; ?>">
            </div>
        </div>

        <input type="submit" value="Tampilkan Laporan">
        <input type="hidden" name="reportView" value="true">
    </form>
</fieldset>

<iframe name="reportView" id="reportView"
    src="<?php echo $_SERVER['PHP_SELF'].'?reportView=true'; ?>"
    frameborder="0" style="width:100%;height:1000px;"></iframe>
<?php
exit;
}

/* ===============================
   HALAMAN LAPORAN
=============================== */
ob_start();

$date_start = isset($_GET['date_start']) ? $_GET['date_start'] : date('Y-m-01');
$date_end   = isset($_GET['date_end'])   ? $_GET['date_end']   : date('Y-m-d');

$date_start = $dbs->escape_string($date_start);
$date_end   = $dbs->escape_string($date_end);

// Judul CETAK (WAJIB DI SINI)
echo '<div class="printPageInfo">
<b>Laporan 3 Kelas/Rombel dengan Kunjungan Terbanyak</b>
<a class="printReport" onclick="window.print()" href="#">Cetak Halaman Ini</a>
</div>';

echo '<center>
<h3>
Laporan 10 Kelas/Rombel dengan Kunjungan Terbanyak<br>
Periode '.$date_start.' s.d '.$date_end.'
</h3>
</center><hr/>';

/* ===============================
   QUERY TOP 3 KELAS
=============================== */
$sql_kelas = "
    SELECT 
        m.pin AS kelas,
        COUNT(v.visitor_id) AS total_kunjungan
    FROM visitor_count v
    JOIN member m ON v.member_id = m.member_id
    WHERE v.checkin_date BETWEEN '{$date_start} 00:00:00' AND '{$date_end} 23:59:59'
      AND m.pin IS NOT NULL
      AND m.pin != ''
    GROUP BY m.pin
    ORDER BY total_kunjungan DESC
    LIMIT 10
";

$q_kelas = $dbs->query($sql_kelas);
?>

<table class="table dataListPrinted" border="1" cellpadding="5" cellspacing="0" width="100%">
<thead>
<tr style="background:#ddd;text-align:center;font-weight:bold">
    <th width="40">No</th>
    <th>Kelas / Rombel</th>
    <th width="150">Total Kunjungan</th>
</tr>
</thead>
<tbody>

<?php
$no = 1;
if ($q_kelas && $q_kelas->num_rows > 0) {
    while ($kelas = $q_kelas->fetch_assoc()) {

        // ===============================
        // QUERY TOP 3 ANGGOTA (RINGKAS)
        // ===============================
        $anggota = [];

        $sql_detail = "
            SELECT 
                m.member_name,
                COUNT(v.visitor_id) AS jml
            FROM visitor_count v
            JOIN member m ON v.member_id = m.member_id
            WHERE m.pin = '".$dbs->escape_string($kelas['kelas'])."'
              AND v.checkin_date BETWEEN '{$date_start} 00:00:00' AND '{$date_end} 23:59:59'
            GROUP BY m.member_id
            ORDER BY jml DESC
            LIMIT 3
        ";

        $q_detail = $dbs->query($sql_detail);
        while ($d = $q_detail->fetch_assoc()) {
            $anggota[] = $d['member_name'].' ('.$d['jml'].')';
        }

        echo '
        <tr>
            <td align="center">'.$no.'</td>
            <td><b>'.$kelas['kelas'].'</b></td>
            <td align="center">'.$kelas['total_kunjungan'].'</td>
        </tr>
        <tr>
            <td></td>
            <td colspan="2">
                <b>Top 3 Anggota:</b>
                '.(!empty($anggota) ? implode(', ', $anggota) : '-').'
            </td>
        </tr>';

        $no++;
    }
} else {
    echo '<tr><td colspan="3" align="center">Tidak ada data kunjungan.</td></tr>';
}
?>

</tbody>
</table>

<?php
$content = ob_get_clean();
require SB.'/admin/'.$sysconf['admin_template']['dir'].'/printed_page_tpl.php';
