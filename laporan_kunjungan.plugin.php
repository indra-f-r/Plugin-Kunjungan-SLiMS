<?php
/**
 * Plugin Name: Laporan Kunjungan Perpustakaan
 * Plugin URI: https://github.com/indra-f-r/Plugin-Kunjungan-SLiMS
 * Description: Plugin untuk Menampilkan laporan kunjungan perpustakaan
 * Version: 1.0.0
 * Author: Indra Febriana Rulliawan (indra.f.rulliawan@gmail.com)
 * Author URI: https://github.com/indra-f-r
 */

use SLiMS\Plugins;

// Register menu menggunakan pattern official
$plugin = Plugins::getInstance();
$plugin->registerMenu('reporting', 'Pengunjung Teraktif', __DIR__ . '/most_active_visitor.php');
$plugin->registerMenu('reporting', 'Kelas Pengunjung Teraktif', __DIR__ . '/most_visitor_class.php');
$plugin->registerMenu('reporting', 'Laporan Kunjungan Per Hari', __DIR__ . '/visitor_report_day.php');
$plugin->registerMenu('reporting', 'Laporan Kunjungan Per Bulan', __DIR__ . '/visitor_chart_by_month.php');
$plugin->registerMenu('reporting', 'Laporan Kunjungan Per Tahun', __DIR__ . '/visitor_chart_by_year.php');