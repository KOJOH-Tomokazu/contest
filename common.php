<?php
/**
 * コンテストログ　共通ファイル
 * @author JJ4KME
 */

// テンポラリフォルダー
define('TEMP_DIR',	'upload');

/** 個別ステータス(受付済) */
define('STATUS_ACCEPTED',		0);
/** 個別ステータス(ログ登録済) */
define('STATUS_LOGGED',			1);
/** 個別ステータス(照合済) */
define('STATUS_COLLATED',		2);
/** 個別ステータス(審査中) */
define('STATUS_INSPECTING',		3);
/** 個別ステータス(成績確定) */
define('STATUS_COMPLETE',		9);

$states = array(
		STATUS_ACCEPTED		=> '受付済',
		STATUS_LOGGED		=> 'ログ登録済',
		STATUS_COLLATED		=> '照合済',
		STATUS_INSPECTING	=> '審査中',
		STATUS_COMPLETE		=> '成績確定');

/** 点数(無得点) */
define('POINT_NONE',		0);
/** 点数(通常) */
define('POINT_NORMAL',		1);
/** 点数(マッチ) */
define('POINT_MATCHED',		2);

// 周波数帯のリスト
$bandList = array(
		1900	=> '1.9',
		3500	=> '3.5',
		7000	=> '7',
		14000	=> '14',
		21000	=> '21',
		28000	=> '28',
		50000	=> '50',
		144000	=> '144',
		430000	=> '430',
		1200000	=> '1200');

// 不照合理由
$errorList = array(
		'RS'	=> '送信したリポートが不正',
		'RR'	=> '相手の送ってきたリポートをミスコピー',
		'MR'	=> '相手が送ってきたマルチをミスコピー',
		'NF'	=> '相手のログに貴方のコールサインがない',
		'TM'	=> '±10分以上の時刻差',
		'WC'	=> '重複交信(受信)',
		'NL'	=> '相手のログが提出されていない',
		'OF'	=> 'バンド・モード不一致',
		'OR'	=> 'マルチ無効(または対象外交信)',
		'OT'	=> '時間外交信',
		'CL'	=> 'チェックログ');

/** 共通マスターのスキーマ */
define('SCHEMA_COMMON',		'common');

/**
 * ログ出力
 * @param unknown $buf 出力内容
 */
function fLog($buf){

	$fname = "/var/log/php/debug.log";

	$date = date("Y-m-d H:i:s.",time());
	$time = microtime();
	$time_list = explode(' ', $time);
	$time_micro = explode('.', $time_list[0]);
	$date_str = $date.substr($time_micro[1], 0, 3). "\t";

	error_log($date_str. $buf. "\n", 3, $fname);
}

/**
 * アップロード日時(＋乱数)を作る
 * @param unknown $timestamp UNIXタイムスタンプ
 * @return string YYYYMMDD_HHMMSS_nnnn
 */
function createUploadTime($timestamp) {

	return (new DateTime("@{$timestamp}"))->setTimezone(new DateTimeZone('Asia/Tokyo'))->format('Ymd_His'). sprintf('_%04d', rand(0, 9999));
}

/**
 * モードの一覧を作る
 * @param PDO $db ＤＢ接続
 * @return boolean|mixed[] モードの一覧
 */
function getModeList(PDO $db) {

	$result = array();
	$SQL = <<<EOF
SELECT
	category,
	disp_name AS name
FROM
	m_modecat
WHERE
	disp_name IS NOT NULL
ORDER BY
	disp_name
EOF;
	$stmt = $db->prepare($SQL);
	$stmt->execute();

	while ($record = $stmt->fetch()) {
		$result[$record['name']] = $record['category'];
	}

	$stmt->closeCursor();

	return $result;
}
