<?php
include_once 'common.php';
include_once 'libs/MyPDO.php';
include_once 'classes/Category.php';
include_once 'classes/Schemas.php';
include_once 'classes/Summary.php';
include_once 'classes/Score.php';
error_log(preg_replace('/\s+/', ' ', print_r($_REQUEST, TRUE)));
try {
	$db = new MyPDO('contest');
	$db->query("SET search_path TO {$_COOKIE['schema']}");
	$db->beginTransaction();

	if (isset($_REQUEST['CALL_AJAX'])) {
		// AJAX呼び出し
		$result = array('success' => TRUE);

		if ($_REQUEST['CALL_AJAX'] == 'initialize') {
			// 初期処理
			$source = Schemas::get($db, empty($_COOKIE['schema']) ? NULL : $_COOKIE['schema']);
			$result['schemas'] = array();
			foreach ($source as $schema) {
				$result['schemas'][] = $schema->toArray();
			}

		} else if ($_REQUEST['CALL_AJAX'] == 'listLogs') {
			// 提出ログの一覧を取得
			$result['LOGS'] = array();
			$source = listLogs($db, isset($_REQUEST['sort_keys']) ? $_REQUEST['sort_keys'] : NULL);
			if ($source === false) {
				$result['success'] = FALSE;

			} else {
				foreach ($source as $record) {
					$result['LOGS'][] = array(
							'sumid'				=> $record['sumid'],
							'callsign'			=> $record['callsign'],
							'category'			=> $record['category'],
							'uploadtime'		=> (new DateTime($record['uploadtime']))->format('Y/m/d H:i:s'),
							'status'			=> $record['status'],
							'status_name'		=> $states[$record['status']],
							'empty_password'	=> boolval($record['empty_password']));
				}
			}

		} else if ($_REQUEST['CALL_AJAX'] == 'getComment') {
			// コメントをランダムに取得
			$comment = getCommentAtRandom($db);
			if (empty($comment)) {
				$result['COMMENT'] = null;

			} else {
				$result['COMMENT'] = $comment;
			}

		} else if ($_REQUEST['CALL_AJAX'] == 'clearCollate') {
			// 照合クリア
			clearCollate($db, $_REQUEST['sumids']);

		} else if ($_REQUEST['CALL_AJAX'] == 'startCollate') {
			// 照合開始
			$result['sumids'] = startCollate($_REQUEST['sumids']);

		} else if ($_REQUEST['CALL_AJAX'] == 'setPassword') {
			// パスワードをセット
			$summary = Summary::get($db, $_REQUEST['sumid']);
			$summary->password = password_hash($_REQUEST['password'], PASSWORD_BCRYPT);
			$summary->update($db);

		} else if ($_REQUEST['CALL_AJAX'] == 'verifyPassword') {
			// パスワード照合
			$summary = Summary::get($db, $_REQUEST['sumid']);
			$result = array(
				'success'	=> FALSE,
				'code'		=> (password_verify($_REQUEST['password'], $summary->password) ? 0 : -1),
				'sumid'		=> $_REQUEST['sumid']);
		}
	}

	$db->commit();

} catch (PDOException $pe) {
	error_log($pe->getMessage());
	$db->rollBack();
	$result = array(
		'success'	=> FALSE,
		'code'		=> $pe->getCode(),
		'message'	=> $pe->getMessage());

} finally {
	$db		= null;
}

echo	json_encode($result);

/**
 * 提出状況一覧を取得
 * @param PDO $db ＤＢ接続
 * @return boolean|array 提出状況一覧、エラーだったらfalse
 */
function listLogs(PDO $db, array $sort_keys = NULL) {

	$result = array();
	$SQL = <<<EOF
SELECT
	SUM.sumid,
	SUM.callsign,
	SUM.category,
	SUM.uploadtime,
	SUM.status,
	CASE WHEN SUM.password IS NULL THEN 1 ELSE 0 END AS empty_password,
	COALESCE(PFX.disporder, 999) AS disporder
FROM
	summary SUM
	LEFT JOIN common.m_prefix_order PFX
		ON PFX.prefix = SUBSTR(SUM.callsign, 1, 3)
EOF;
	if ($sort_keys === NULL) {
		$SQL .= ' ORDER BY disporder, SUM.callsign, SUM.category';

	} else {
		$SQL .= ' ORDER BY '. implode(', ', $sort_keys);
	}
	$stmt = $db->prepare($SQL);
	$stmt->execute();

	$result = $stmt->fetchAll();
	$stmt->closeCursor();

	return $result;
}

function getCommentAtRandom(PDO $db) {

	$result = array();
	$stmt = $db->prepare('SELECT callsign, comments FROM summary WHERE comments IS NOT NULL');
	$stmt->execute();

	if ($stmt->rowCount() > 0) {
		$source = $stmt->fetchAll();
		$result = $source[rand(0, count($source) - 1)];

		$stmt->closeCursor();
	}

	return $result;
}

/**
 * 照合結果をクリアする
 * @param PDO $db ＤＢ接続
 * @param array $sumids サマリーＩＤ
 */
function clearCollate(PDO $db, array $sumids) {

	$stmtSummary	= $db->prepare('UPDATE summary SET status = '. STATUS_LOGGED. ' WHERE sumid = :sumid');
	$stmtLogdata	= $db->prepare('UPDATE logdata SET point = null, error = null   WHERE sumid = :sumid');
	$stmtScores		= $db->prepare('DELETE FROM scores WHERE sumid = :sumid');

	foreach ($sumids as $sumid) {
		$stmtSummary->bindValue(':sumid', $sumid);
		$stmtSummary->execute();
		$stmtLogdata->bindValue(':sumid', $sumid);
		$stmtLogdata->execute();
		$stmtScores->bindValue(':sumid', $sumid);
		$stmtScores->execute();
	}
}

/**
 * ログの称号を開始する
 * @param array $sumids 照合するサマリーＩＤ
 * @return array 照合したサマリーＩＤ
 */
function startCollate(array $sumids) {

	$result = array();
	exec('php collateLog.php '. implode(' ', $sumids), $result);

	return $result;
}
