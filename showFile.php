<?php
include_once 'common.php';
include_once 'classes/Summary.php';
include_once 'libs/MyPDO.php';

$db = new MyPDO('contest');
$db->query("SET search_path TO {$_COOKIE['schema']}");

$summary = Summary::get($db, $_REQUEST['sumid']);

if (preg_match('/\.pdf$/', $summary->filename)) {
	header('Content-Type: application/pdf');

} else {
	header('Content-Type: text/plain');
}

echo	file_get_contents(TEMP_DIR. '/'. trim($summary->filename), 'rb');
