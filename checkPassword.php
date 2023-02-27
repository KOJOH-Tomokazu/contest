<?php
include_once 'libs/MyPDO.php';

$db = new MyPDO('ja4test');
$stmt = $db->query('SELECT * FROM m_administrators');
while ($record = $stmt->fetch()) {
	echo	"{$record['callsign']}⇒". (password_verify('abcd1234', $record['password']) ? 'OK' : 'NG'). "\n";
}
$stmt->closeCursor();
$db = NULL;

