<?php
include_once 'common.php';
include_once 'classes/Cabrillo.php';
include_once 'classes/Category.php';
include_once 'classes/CtestWin.php';
include_once 'classes/GlobalStatus.php';
include_once 'classes/HLTest7.php';
include_once 'classes/HLTest8.php';
include_once 'classes/Others.php';
include_once 'classes/LogData.php';
include_once 'classes/RegClub.php';
include_once 'classes/RTCL.php';
include_once 'classes/ZLog.php';
include_once 'classes/ZLogAll.php';
include_once 'libs/MyPDO.php';
if (empty($_REQUEST['SCHEMA'])) {
	$_REQUEST['SCHEMA'] = SCHEMA_CURRENT;
}
$db = null;
error_log(print_r($_REQUEST, true));
try {
	$db = new MyPDO('contest');
	$db->beginTransaction();
	$result = array(
			'RESULTCD'	=> 0,
			'MESSAGE'	=> '');

	if (isset($_REQUEST['CALL_AJAX'])) {
		// AJAX呼び出し
		if ($_REQUEST['CALL_AJAX'] == 'initialize') {
			// 初期処理
			// 全体ステータスの取得
			$result['status'] = GlobalStatus::getStatus($db);
			// 周波数帯の一覧を取得
			$result['BANDS'] = $bandList;
			// モードの一覧を取得
			$result['MODES'] = getModeList($db);
			// 登録クラブの一覧を取得
			$source = RegClub::get($db);
			foreach ($source as $record) {
				$result['CLUBS'][] = $record->toArray();
			}
			// 参加カテゴリの一覧を取得
			if (GlobalStatus::getStatus($db) == GlobalStatus::GLOBAL_ADDITIONAL) {
				// チェックログのみ
				$result['CATEGORIES'] = array(array(
					'code'		=> 'CHL',
					'disporder'	=> 0,
					'name'		=> '【その他】チェックログ'));

			} else {
				$source = Category::get($db);
				foreach ($source as $record) {
					$result['CATEGORIES'][] = $record->toArray();
				}
			}

		} else if ($_REQUEST['CALL_AJAX'] == 'checkCategoryDup') {
			// 同時提出可能なカテゴリーか調べる
			$result['RESULTCD'] = checkCategoryDup($db, $_REQUEST['callsign'], $_REQUEST['category']);
		}

	} else {
		// 通常呼び出し
		// 保存用ファイル名を作る
		$TempFile = TEMP_DIR. '/'. createUploadTime($_SERVER['REQUEST_TIME']). '.txt';

		if ($_REQUEST['method'] == 'file') {
			// ファイルアップロード
			// UTF-8に変換する
			$from	= fopen($_FILES['source']['tmp_name'], 'r');
			$to		= fopen($TempFile, 'w');
			while ($source = fgets($from)) {
				fputs($to, mb_convert_encoding($source, 'UTF-8', 'SJIS'));
			}
			fclose($to);
			fclose($from);

		} else if ($_REQUEST['method'] == 'text') {
			// テキスト貼り付け
			// ファイルに保存
			file_put_contents($TempFile, mb_convert_encoding($_REQUEST['source'], 'UTF-8'));

		} else if ($_REQUEST['method'] == 'register') {
			// 直接入力と登録実行
			// 同じカテゴリーのサマリーを削除
			deleteSameCategory($db, $_REQUEST['owner'], $_REQUEST['category']);

			$summary = new Summary(array(
					'sumid'			=> createUploadTime($_SERVER['REQUEST_TIME']),									// サマリーＩＤ
					'category'		=> substr($_REQUEST['category'], 0, 15),										// 参加部門
					'callsign'		=> $_REQUEST['owner'],															// コールサイン
					'name'			=> $_REQUEST['name'],															// 氏名
					'address'		=> (empty($_REQUEST['address'])			? null : $_REQUEST['address']),			// 住所
					'email'			=> (empty($_REQUEST['email'])			? null : $_REQUEST['email']),			// メールアドレス
					'comments'		=> (empty($_REQUEST['comments'])		? null : $_REQUEST['comments']),		// コメント
					'multioplist'	=> (empty($_REQUEST['multioplist'])		? null : $_REQUEST['multioplist']),		// 運用者リスト
					'regclubnumber'	=> (empty($_REQUEST['regclubnumber'])	? null : $_REQUEST['regclubnumber']),	// 登録クラブ番号
																													// パスワード
					'password'		=> (empty($_REQUEST['password'])		? null : password_hash($_REQUEST['password'], PASSWORD_BCRYPT)),
					'filename'		=> (empty($_REQUEST['filename'])		? null : $_REQUEST['filename']),		// ファイル名
					'status'		=> $_REQUEST['status'],															// 処理ステータス
					'uploadtime'	=> null));																		// アップロード日時

			if (isset($_REQUEST['workdate'])) {
				$callsign = (strpos($_REQUEST['owner'], '/') === FALSE ? $_REQUEST['owner'] : substr($_REQUEST['owner'], 0, strpos($_REQUEST['owner'], '/')));
				for ($i = 0; $i < count($_REQUEST['workdate']); $i++) {
					$summary->addLog(new Cabrillo($db, array(
							'sumid'			=> $summary->sumid,								// サマリーＩＤ
							'logid'			=> $i + 1,										// ログＩＤ
							'owner'			=> $_REQUEST['owner'],							// 自局コールサイン
							'callsign'		=> $_REQUEST['callsign'][$i],					// 相手局コールサイン
																							// 交信日時
							'datetime'		=> "{$_REQUEST['workdate'][$i]} {$_REQUEST['worktime'][$i]}{$_REQUEST['timezone']}",
							'freq'			=> $_REQUEST['freq'][$i],						// 周波数
							'mode'			=> $_REQUEST['mode'][$i],						// 電波の型式
							'modecat'		=> $_REQUEST['modecat'][$i],					// モード区分
							'recvrst'		=> substr($_REQUEST['recvrst'][$i],		0,  3),	// 受信リポート
							'recvmulti'		=> substr($_REQUEST['recvmulti'][$i],	0, 10),	// 受信マルチ
							'sentrst'		=> substr($_REQUEST['sentrst'][$i],		0,  3),	// 送信リポート
							'sentmulti'		=> substr($_REQUEST['sentmulti'][$i],	0, 10),	// 送信マルチ
							'input_id'		=> strtoupper($callsign),						// 入力者
							'verify_id'		=> strtoupper($callsign),						// 確認者
							'point'			=> null,										// 点数
							'error'			=> null)));										// 不照合理由
				}
			}

			$result['RESULTCD'] = $summary->register($db);
		}

		if ($_REQUEST['method'] != 'register') {
			// 直接入力(または登録実行)以外だったら
			if ($_REQUEST['fileType'] == 'cabrillo') {
				$summary = Cabrillo::readFile($db, $TempFile);
				$result['logType'] = 'Cabrillo';

			} else if ($_REQUEST['fileType'] == 'jarl') {
				$logType = LogData::checkLogType($TempFile);

				if ($logType == 'CTESTWIN') {
					$summary = CtestWin::readFile($db, $TempFile);
					$result['logType'] = 'CTESTWIN';

				} else if ($logType == 'ZLOG') {
					$summary = ZLog::readFile($db, $TempFile);
					$result['logType'] = 'zLog';

				} else if ($logType == 'ZLOGALL') {
					$summary = ZLogAll::readFile($db, $TempFile);
					$result['logType'] = 'zLogAll';

				} else if ($logType == 'HLTEST7') {
					$summary = HLTest7::readFile($db, $TempFile);
					$result['logType'] = 'HLTST(V7以前)';

				} else if ($logType == 'HLTEST8') {
					$summary = HLTest8::readFile($db, $TempFile);
					$result['logType'] = 'HLTST(V8以降)';

				} else if ($logType == 'RTCL') {
					$summary = RTCL::readFile($db, $TempFile);
					$result['logType'] = 'Radio Contest Log';

				} else if ($logType == 'Others') {
					$summary = Others::readFile($db, $TempFile);
					$result['logType'] = 'その他';
					$result['RESULTCD'] = 1;
				}
			}

			$result['summary']	= array(
					'category'		=> $summary->category,					// 参加部門コード
					'owner'			=> $summary->callsign,					// コールサイン
					'name'			=> $summary->name,						// 氏名
					'address'		=> $summary->address,					// 住所
					'email'			=> $summary->email,						// メールアドレス
					'comments'		=> $summary->comments,					// コメント
					'multioplist'	=> $summary->multioplist,				// 運用者リスト
					'regclubnumber'	=> $summary->regclubnumber,				// 登録クラブ番号
					'filename'		=> $summary->filename,					// ファイル名
					'status'		=> $summary->status,					// 処理ステータス
					'timezone'		=> $_REQUEST['timezone']);				// タイムゾーン
			$result['logCount']		= count($summary->logData);				// ログの件数
			$result['logData']		= array();

			foreach ($summary->logData as $logData) {
				$result['logData'][]	= array(
						'callsign'		=> $logData->callsign,							// 相手局コールサイン
						'datetime'		=> $logData->datetime->format('Y/m/d H:i'),		// 交信日時
						'workdate'		=> $logData->datetime->format('Y/m/d'),			// 交信日付
						'worktime'		=> $logData->datetime->format('H:i'),			// 交信時刻
						'band'			=> $bandList[$logData->freq],					// 周波数帯
						'freq'			=> $logData->freq,								// 周波数
						'mode'			=> $logData->mode,								// 電波の型式
						'modecat'		=> $logData->modecat,							// モード区分
						'recvrst'		=> $logData->recvrst,							// 受信リポート
						'recvmulti'		=> $logData->recvmulti,							// 受信マルチ
						'sentrst'		=> $logData->sentrst,							// 送信リポート
						'sentmulti'		=> $logData->sentmulti);						// 送信マルチ
			}
		}
	}

	$db->commit();

} catch (PDOException $pe) {
	error_log($pe->getMessage());
	error_log($pe->getTraceAsString());
	$db->rollBack();
	$result = array(
			'RESULTCD'	=> $pe->getCode(),
			'MESSAGE'	=> $pe->getMessage());

} finally {
	$db		= null;
}

echo	json_encode($result);

/**
 * 全体ステータスを取得
 * @param PDO $db ＤＢ接続
 * @return string|mixed 全体ステータス
 */
function getStatus(PDO $db) {

	$stmt = $db->prepare('SELECT MIN(starttime), MAX(endtime) FROM m_periods');
	$stmt->execute();
	$record = $stmt->fetch(PDO::FETCH_NUM);

	$nowDate	= (new DateTime())->format('U');
	$minDate	= (new DateTime($record[0]))->format('U');
	$maxDate	= (new DateTime($record[1]))->format('U');
	$limitDate1	= (new DateTime('2022/03/21 23:59:59'))->format('U');
	$limitDate2	= (new DateTime('2022/03/27 23:59:59'))->format('U');

	$result = GlobalStatus::GLOBAL_DEADLINE;
	if ($nowDate < $minDate) {
		$result = GlobalStatus::GLOBAL_NOTSTART;

	} else if ($nowDate < $maxDate) {
		$result = GlobalStatus::GLOBAL_PRESENT;

	} else if ($nowDate < $limitDate1) {
		$result = GlobalStatus::GLOBAL_FINISHED;

	} else if ($nowDate < $limitDate2) {
		$result = GlobalStatus::GLOBAL_ADDITIONAL;
	}

	return $result;
}

/**
 * 同時に提出できるカテゴリーか調べる
 * @param PDO $db ＤＢ接続
 * @param unknown $callsign コールサイン
 * @param unknown $category 参加カテゴリー
 * @return number 提出ＯＫだったら０、同じカテゴリーだったら１、同時提出できないカテゴリーだったら２、エラーだったら－１
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

function deleteSameCategory(PDO $db, $callsign, $category) {

	$stmtDeleteSummary	= $db->prepare('DELETE FROM summary WHERE sumid = :sumid');
	$stmtDeleteLog		= $db->prepare('DELETE FROM logdata WHERE sumid = :sumid');

	$stmt = $db->prepare('SELECT sumid FROM summary WHERE callsign = :callsign AND category = :category');
	$stmt->bindValue(':callsign',	$callsign);
	$stmt->bindValue(':category',	$category);
	$stmt->execute();

	while ($record = $stmt->fetch()) {
		$stmtDeleteSummary->bindValue(':sumid', $record['sumid']);
		$stmtDeleteSummary->execute();
		$stmtDeleteLog->bindValue(':sumid', $record['sumid']);
		$stmtDeleteLog->execute();
	}

	$stmt->closeCursor();
}
