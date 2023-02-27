<?php
include_once 'common.php';
include_once 'libs/MyPDO.php';
include_once 'classes/Administrator.php';
include_once 'classes/Cabrillo.php';
include_once 'classes/Category.php';
include_once 'classes/LogData.php';
include_once 'classes/RegClub.php';
include_once 'classes/Schemas.php';
include_once 'classes/Summary.php';
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
			// 周波数帯の一覧を取得
			$result['BANDS'] = $bandList;
			// モードの一覧を取得
			$result['MODES'] = getModeList($db);
			// 登録クラブの一覧を取得
			$source = RegClub::get($db);
			foreach ($source as $record) {
				$result['CLUBS'][] = $record->toArray();
			}

		} else if ($_REQUEST['CALL_AJAX'] == 'getSummaries') {
			// サマリーの一覧を取得
			$result['SUMMARIES'] = getSummaries($db, $_REQUEST['filter']);

		} else if ($_REQUEST['CALL_AJAX'] == 'checkCategoryDup') {
			// 同時提出可能なカテゴリーか調べる
			$result = array(
				'success'	=> FALSE,
				'code'		=> checkCategoryDup($db, $_REQUEST['callsign'], $_REQUEST['category']));

		} else if ($_REQUEST['CALL_AJAX'] == 'getLogdata') {
			// 登録済みログデータの取得
			$summary = Summary::get($db, $_REQUEST['sumid']);
			if ($summary === null) {
				$result = array(
					'success'	=> FALSE,
					'code'		=> -1,
					'message'	=> 'サマリーが登録されていません(データ異常)');

			} else {
				$result['summary'] = $summary->toArray();
				// 周波数帯の一覧
				$result['bands'] = $bandList;
				// エラーの一覧
				$result['errors'] = $errorList;
			}

		} else if ($_REQUEST['CALL_AJAX'] == 'update') {
			// ログデータの修正
			$result['LOGDATA'] = confirmLog($db,
					$_REQUEST['sumid'],
					$_REQUEST['logid'],
					$_REQUEST['owner'],
					$_REQUEST['callsign'],
					$_REQUEST['workdate'],
					$_REQUEST['worktime'],
					$_REQUEST['timezone'],
					$_REQUEST['freq'],
					$_REQUEST['mode'],
					$_REQUEST['modecat'],
					$_REQUEST['recvrst'],
					$_REQUEST['recvmulti'],
					$_REQUEST['sentrst'],
					$_REQUEST['sentmulti'],
					$_REQUEST['input_id']);

		} else if ($_REQUEST['CALL_AJAX'] == 'confirm') {
			// ログデータの確認
			$user = Administrator::get($db, $_COOKIE['user_id']);
			$result['LOGDATA'] = confirmLog($db,
					$_REQUEST['sumid'],
					$_REQUEST['logid'],
					$_REQUEST['owner'],
					$_REQUEST['callsign'],
					$_REQUEST['workdate'],
					$_REQUEST['worktime'],
					$_REQUEST['timezone'],
					$_REQUEST['freq'],
					$_REQUEST['mode'],
					$_REQUEST['modecat'],
					$_REQUEST['recvrst'],
					$_REQUEST['recvmulti'],
					$_REQUEST['sentrst'],
					$_REQUEST['sentmulti'],
					$_REQUEST['input_id'],
					$user[0]->user_id);

		} else if ($_REQUEST['CALL_AJAX'] == 'append') {
			// ログデータの追加
			$summary = Summary::get($db, $_REQUEST['sumid']);
			if ($summary === null) {
				$result = array(
					'success'	=> FALSE,
					'code'		=> -1,
					'message'	=> 'サマリーが登録されていません(データ異常)');

			} else {
				$user = Administrator::get($db, $_COOKIE['user_id']);
				$logData = new Cabrillo($db, array(
						'sumid'		=> $summary->sumid,								// サマリーＩＤ
						'logid'		=> count($summary->logData) + 1,				// ログＩＤ
						'owner'		=> $_REQUEST['owner'],							// 自局コールサイン
						'callsign'	=> $_REQUEST['callsign'],						// 相手局コールサイン
																					// 交信日時
						'datetime'	=> "{$_REQUEST['workdate']} {$_REQUEST['worktime']}{$_REQUEST['timezone']}",
						'freq'		=> $_REQUEST['freq'],							// 周波数
						'mode'		=> $_REQUEST['mode'],							// 電波の型式
						'modecat'	=> $_REQUEST['modecat'],						// モード区分
						'recvrst'	=> substr($_REQUEST['recvrst'],		0,  3),		// 受信リポート
						'recvmulti'	=> substr($_REQUEST['recvmulti'],	0, 10),		// 受信マルチ
						'sentrst'	=> substr($_REQUEST['sentrst'],		0,  3),		// 送信リポート
						'sentmulti'	=> substr($_REQUEST['sentmulti'],	0, 10),		// 送信マルチ
						'input_id'	=> $user[0]->user_id,							// 入力者
						'verify_id'	=> null,										// 確認者
						'point'		=> null,										// 点数
						'error'		=> null));										// 不照合理由
				$summary->addLog($logData);
				$summary->status = STATUS_LOGGED;
				$summary->update($db);

				$result['LOGDATA'] = $logData->toArray();
			}

		} else if ($_REQUEST['CALL_AJAX'] == 'showNewSummary') {
			// サマリーの新規作成開始
			$result['CATEGORIES'] = array();
			$source = Category::get($db);
			foreach ($source as $disp_order => $record) {
				$result['CATEGORIES'][$disp_order] = $record->toArray();
			}
			// 登録クラブの一覧
			$result['CLUBS'] = array();
			$source = RegClub::get($db);
			foreach ($source as $record) {
				$result['CLUBS'][] = $record->toArray();
			}

		} else if ($_REQUEST['CALL_AJAX'] == 'searchSummary') {
			// サマリーの検索
			$summary = Summary::search($db, $_REQUEST['callsign']);
			if ($summary === null) {
				$result['SUMMARY'] = null;

			} else {
				$result['SUMMARY'] = $summary->toArray();
			}

		} else if ($_REQUEST['CALL_AJAX'] == 'createSummary') {
			// サマリーの新規作成
			(new Summary(array(
					'sumid'			=> createUploadTime($_SERVER['REQUEST_TIME']),									// サマリーＩＤ
					'category'		=> substr($_REQUEST['category'], 0, 15),										// 参加部門
					'callsign'		=> $_REQUEST['owner'],															// コールサイン
					'name'			=> $_REQUEST['name'],															// 氏名
					'address'		=> (empty($_REQUEST['address'])			? null : $_REQUEST['address']),			// 住所
					'email'			=> (empty($_REQUEST['email'])			? null : $_REQUEST['email']),			// メールアドレス
					'multioplist'	=> (empty($_REQUEST['multioplist'])		? null : $_REQUEST['multioplist']),		// 運用者リスト
					'regclubnumber'	=> (empty($_REQUEST['regclubnumber'])	? null : $_REQUEST['regclubnumber']),	// 登録クラブ番号
					'status'		=> STATUS_ACCEPTED)))->register($db);												// 処理ステータス(受付済)

		} else if ($_REQUEST['CALL_AJAX'] == 'updateSummary') {
			// サマリーの更新
			$summary = Summary::get($db, $_REQUEST['sumid']);
			$summary->name			= $_REQUEST['name'];															// 氏名
			$summary->address		= (empty($_REQUEST['address'])			? null : $_REQUEST['address']);			// 住所
			$summary->email			= (empty($_REQUEST['email'])			? null : $_REQUEST['email']);			// メールアドレス
			$summary->multioplist	= (empty($_REQUEST['multioplist'])		? null : $_REQUEST['multioplist']);		// 運用者リスト
			$summary->comments		= (empty($_REQUEST['comments'])			? null : $_REQUEST['comments']);		// コメント
			$summary->regclubnumber	= (empty($_REQUEST['regclubnumber'])	? null : $_REQUEST['regclubnumber']);	// 登録クラブ番号
			$summary->update($db);
			$result['SUMMARY'] = $summary->toArray();

		} else if ($_REQUEST['CALL_AJAX'] == 'deleteSummary') {
			// サマリーの削除
			deleteSummary($db, $_REQUEST['sumid']);
		}

	} else {
		// 通常呼び出し
		// アップロード日時を作る
		$UploadTime	= createUploadTime($_SERVER['REQUEST_TIME']);
		// 保存用ファイル名を作る
		if (preg_match('/\/pdf$/', $_FILES['source']['type'])) {
			// ＰＤＦファイルだったら
			$TempFile	= "{$UploadTime}.pdf";

		} else {
			// それ以外だったら
			$TempFile	= "{$UploadTime}.txt";
		}

		if ($_FILES['source']['error'] == UPLOAD_ERR_OK) {
			// アップロードに成功していたら一時ファイルをコピー
			if (copy($_FILES['source']['tmp_name'], TEMP_DIR. "/{$TempFile}")) {
				$summary = Summary::get($db, $_REQUEST['sumid']);
				if ($summary === null) {
					$result = array(
						'success'	=> FALSE,
						'code'		=> -1,
						'message'	=> 'サマリーが登録されていません(データ異常)');

				} else {
					$summary->filename = $TempFile;
					$summary->update($db);

					$result['SUMID'] = $_REQUEST['sumid'];
					$result['FILENAME'] = $TempFile;
				}

			} else {
				$result = array(
					'success'	=> FALSE,
					'code'		=> -1,
					'message'	=> '一時ファイルのコピーに失敗しました');
			}

		} else {
			// 失敗していたらメッセージを作る
			$result['success']	= FALSE;
			$result['code']		= -1;
			if ($_FILES['source']['error'] == UPLOAD_ERR_INI_SIZE) {
				$result['message'] = 'ファイルが大きすぎます';

			} else if ($_FILES['source']['error'] == UPLOAD_ERR_FORM_SIZE) {
				$result['message'] = 'ファイルが大きすぎます';

			} else if ($_FILES['source']['error'] == UPLOAD_ERR_PARTIAL) {
				$result['message'] = '一部のみしかアップロードされていません';

			} else if ($_FILES['source']['error'] == UPLOAD_ERR_NO_FILE) {
				$result['message'] = 'ファイルはアップロードされませんでした';

			} else if ($_FILES['source']['error'] == UPLOAD_ERR_NO_TMP_DIR) {
				$result['message'] = 'テンポラリフォルダがありません';

			} else if ($_FILES['source']['error'] == UPLOAD_ERR_CANT_WRITE) {
				$result['message'] = 'ディスクへの書き込みに失敗しました';

			} else if ($_FILES['source']['error'] == UPLOAD_ERR_EXTENSION) {
				$result['message'] = '拡張モジュールがファイルのアップロードを中止しました';
			}
		}
	}

	$db->commit();

} catch (PDOException $pe) {
	$db->rollBack();
	$result = array(
		'sucess'	=> FALSE,
		'code'		=> $pe->getCode(),
		'message'	=> $pe->getMessage());

} finally {
	$db		= null;
}

echo	json_encode($result);

/**
 * サマリーの一覧を取得
 * @param PDO $db ＤＢ接続
 * @param unknown $filter フィルター条件
 * @return array|boolean コールサインのリスト、失敗したらfalse
 */
function getSummaries(PDO $db, $filter) {

	$result = array();
	$SQL		= 'SELECT SUM.callsign, SUM.sumid, MC.name FROM summary SUM LEFT JOIN m_categories MC ON MC.code = SUM.category';
	$orderSQL	= 'ORDER BY SUM.callsign, MC.disporder';
	$whereSQL	= '';
	if ($filter == 'empty') {
		// ログ未入力だったら
		$whereSQL	= 'WHERE sumid NOT IN (SELECT sumid FROM logdata GROUP BY sumid)';

	} else if ($filter == 'verify') {
		// 未確認ありだったら
		$whereSQL	= 'WHERE sumid IN (SELECT sumid FROM logdata WHERE verify_id IS NULL GROUP BY sumid)';
	}

	$stmt = $db->prepare("{$SQL} {$whereSQL} {$orderSQL}");
	$stmt->execute();
	while ($record = $stmt->fetch()) {
		$result[$record['callsign']][$record['sumid']] = $record['name'];
	}

	$stmt->closeCursor();

	return $result;
}

/**
 * 登録済みのログデータを取得
 * @param PDO $db ＤＢ接続
 * @param unknown $sumid サマリーＩＤ
 * @param unknown $logid ログＩＤ
 * @return boolean|string[] ログデータ、失敗したらfalse
 */
function getLogdata(PDO $db, $sumid, $logid = null) {

	global	$bandList, $errorList;

	$result = array();
	$SQL = <<<EOF
SELECT
	LOG.sumid,
	LOG.logid,
	LOG.owner,
	LOG.callsign,
	TO_CHAR(LOG.datetime, 'YYYY/MM/DD')	AS workdate,
	TO_CHAR(LOG.datetime, 'HH24:MI')	AS worktime,
	LOG.freq,
	LOG.mode,
	LOG.modecat,
	LOG.recvrst,
	LOG.recvmulti,
	LOG.sentrst,
	LOG.sentmulti,
	LOG.input_id,
	LOG.verify_id,
	LOG.point,
	LOG.error
FROM
	summary SUM
	LEFT JOIN logdata LOG
		ON	LOG.sumid	= SUM.sumid
WHERE
	SUM.sumid	= :sumid
EOF;
	if ($logid !== null) {
		$SQL .= ' AND LOG.logid = :logid';
	}
	$SQL .= <<<EOF
 ORDER BY
	LOG.datetime
EOF;
	$stmt = $db->prepare($SQL);
	$stmt->bindValue(':sumid',		$sumid);
	if ($logid !== null) {
		$stmt->bindValue(':logid',	$logid);
	}
	$stmt->execute();

	while ($record = $stmt->fetch()) {
		$record['comment'] = (isset($errorList[$record['error']]) ? $errorList[$record['error']] : null);
		$record['band'] = $bandList[$record['freq']];
		$result[] = $record;
	}

	$stmt->closeCursor();

	return $result;
}

/**
 * ログデータの修正・確認
 * @param PDO $db ＤＢ接続
 * @param unknown $sumid サマリーＩＤ
 * @param unknown $logid ログＩＤ
 * @param unknown $owner 自局コールサイン
 * @param unknown $callsign 相手局コールサイン
 * @param unknown $workdate 交信日付
 * @param unknown $worktime 交信時刻
 * @param unknown $timezone タイムゾーン
 * @param unknown $freq 周波数帯
 * @param unknown $mode 電波の型式
 * @param unknown $modecat モード区分
 * @param unknown $recvrst 受信リポート
 * @param unknown $recvmulti 受信マルチ
 * @param unknown $sentrst 送信リポート
 * @param unknown $sentmulti 送信マルチ
 * @param unknown $input_id 入力者ＩＤ
 * @param unknown $verify_id 確認者ＩＤ
 * @return unknown 入力したログデータ
 */
function confirmLog(PDO $db, $sumid, $logid, $owner, $callsign, $workdate, $worktime, $timezone, $freq, $mode, $modecat, $recvrst, $recvmulti, $sentrst, $sentmulti, $input_id, $verify_id = null) {

	$logData = new Cabrillo($db, array(
			'sumid'			=> $sumid,									// サマリーＩＤ
			'logid'			=> $logid,									// ログＩＤ
			'owner'			=> $owner,									// 自局コールサイン
			'callsign'		=> $callsign,								// 相手局コールサイン
			'datetime'		=> "{$workdate} {$worktime}{$timezone}",	// 交信日時
			'freq'			=> $freq,									// 周波数
			'mode'			=> $mode,									// 電波の型式
			'modecat'		=> $modecat,								// モード区分
			'recvrst'		=> substr($recvrst,		0,  3),				// 受信リポート
			'recvmulti'		=> substr($recvmulti,	0, 10),				// 受信マルチ
			'sentrst'		=> substr($sentrst,		0,  3),				// 送信リポート
			'sentmulti'		=> substr($sentmulti,	0, 10),				// 送信マルチ
			'input_id'		=> $input_id,								// 入力者
			'verify_id'		=> $verify_id,								// 確認者
			'point'			=> null,									// 点数
			'error'			=> null));									// 不照合理由
	$logData->update($db);

	return getLogdata($db, $sumid, $logid);
}

/**
 * 同時に提出できるカテゴリーか調べる
 * @param PDO $db ＤＢ接続
 * @param unknown $callsign コールサイン
 * @param unknown $category 参加カテゴリー
 * @return number 提出ＯＫだったら０、同じカテゴリーだったら１、同時提出できないカテゴリーだったら２
 */
function checkCategoryDup(PDO $db, $callsign, $category) {

	$result = 0;
	$SQL = <<<EOF
SELECT
	SUM.category,
	CAT.bandcat,
	DUP.enabled
FROM
	summary SUM
	LEFT JOIN m_categories CAT
		ON	CAT.code	= SUM.category
	LEFT JOIN m_categories_dup DUP
		ON	DUP.code1	= SUM.category
		AND	DUP.code2	= :category
WHERE
	SUM.callsign	= :callsign
ORDER BY
	DUP.enabled
EOF;

	$stmt = $db->prepare($SQL);
	$stmt->bindValue(':callsign',	$callsign);
	$stmt->bindValue(':category',	$category);
	$stmt->execute();

	if ($stmt->rowCount() > 0) {
		$singleBand = 0;
		while ($record = $stmt->fetch()) {
			if ($record['category'] == $category) {
				// 既に提出済みのカテゴリーだったら
				$result = 1;
				break;

			} else if (!$record['enabled']) {
				// 同時提出できないカテゴリーだったら
				$result = 2;
				break;

			} else if ($record['bandcat'] == 'S') {
				// シングルバンドだったら
				$singleBand++;
			}

			if ($singleBand == 2) {
				// 既にシングルバンド２個あったら
				$result = 3;
				break;
			}
		}
	}

	$stmt->closeCursor();

	return $result;
}

/**
 * サマリーとログを削除する
 * @param PDO $db ＤＢ接続
 * @param unknown $sumid サマリーＩＤ
 */
function deleteSummary(PDO $db, $sumid) {

	$stmtSummary = $db->prepare('DELETE FROM summary WHERE sumid = :sumid');
	$stmtSummary->bindValue(':sumid', $sumid);
	$stmtSummary->execute();

	$stmtLogdata = $db->prepare('DELETE FROM logdata WHERE sumid = :sumid');
	$stmtLogdata->bindValue(':sumid', $sumid);
	$stmtLogdata->execute();
}
