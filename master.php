<?php
include_once 'common.php';
include_once 'libs/MyPDO.php';
include_once 'classes/Administrator.php';
include_once 'classes/Branch.php';
include_once 'classes/Category.php';
include_once 'classes/Period.php';
include_once 'classes/RegClub.php';
include_once 'classes/Schemas.php';
error_log(preg_replace('/\s+/', ' ', print_r($_REQUEST, TRUE)));
try {
	$db = new MyPDO('contest');
	$db->query("SET search_path TO {$_COOKIE['schema']}");
	$db->beginTransaction();

	if (isset($_REQUEST['CALL_AJAX'])) {
		// AJAX呼び出し
		$result = array('RESULTCD' => 0, 'MESSAGE' => '');

		if ($_REQUEST['CALL_AJAX'] == 'get_user') {
			// ユーザー情報の取得
			$users = Administrator::get($db, $_COOKIE['user_id']);
			$result['user'] = $users[0]->toArray();

			$schemas = Schemas::get($db, $_COOKIE['schema']);
			$result['schema'] = $schemas[0]->toArray();

		} else if ($_REQUEST['CALL_AJAX'] == 'getBands') {
			// 周波数帯の一覧を取得
			$result['BANDS'] = array();
			foreach ($bandList as $value => $label) {
				$result['BANDS'][] = array(
						'value'	=> $value,
						'label'	=> "{$label} MHz");
			}

		} else if ($_REQUEST['CALL_AJAX'] == 'getCategories') {
			// 参加カテゴリの一覧
			$result['categories'] = array();
			$source = Category::get($db);
			foreach ($source as $disp_order => $record) {
				$result['categories'][$disp_order] = $record->toArray();
			}

		} else if ($_REQUEST['CALL_AJAX'] == 'registCategories') {
			// 参加カテゴリーの一覧を登録
			registCategories($db, $_REQUEST);

		} else if ($_REQUEST['CALL_AJAX'] == 'getDuplicates') {
			// 同時提出可否マスターを取得
			$result['categories'] = array();
			$source = Category::get($db);
			foreach ($source as $disp_order => $record) {
				$result['categories'][$disp_order] = $record->toArray();
			}
			$result['duplicates'] = getDuplicates($db);

		} else if ($_REQUEST['CALL_AJAX'] == 'registDuplicates') {
			// 同時提出可否マスターを登録
			registDuplicates($db, $_REQUEST);

		} else if ($_REQUEST['CALL_AJAX'] == 'getMultipliers') {
			// マルチプライヤーの一覧を取得
			$result['categories'] = array();
			$source = Category::get($db);
			foreach ($source as $disp_order => $record) {
				$result['categories'][$disp_order] = $record->toArray();
			}
			$result['multipliers'] = getMultipliers($db);

		} else if ($_REQUEST['CALL_AJAX'] == 'registMultipliers') {
			// マルチプライヤーの一覧を登録
			registMultipliers($db, $_REQUEST);

		} else if ($_REQUEST['CALL_AJAX'] == 'getPeriods') {
			// 時間帯の一覧
			$result['periods'] = array();
			$source = Period::get($db);
			foreach ($source as $record) {
				$result['periods'][] = $record->toArray();
			}

		} else if ($_REQUEST['CALL_AJAX'] == 'registPeriods') {
			// 時間帯の登録
			registPeriods($db, $_REQUEST);

		} else if ($_REQUEST['CALL_AJAX'] == 'getModecats') {
			// モード区分の一覧
			$result['modecats'] = getModecats($db);

		} else if ($_REQUEST['CALL_AJAX'] == 'registModecats') {
			// モード区分の登録
			registModecats($db, $_REQUEST);

		} else if ($_REQUEST['CALL_AJAX'] == 'getClubs') {
			// 登録クラブの一覧
			$result['branches'] = array();
			$source = Branch::get($db);
			foreach ($source as $record) {
				$result['branches'][] = $record->toArray();
			}

			$result['clubs'] = array();
			$source = RegClub::get($db);
			foreach ($source as $record) {
				$result['clubs'][] = $record->toArray();
			}

		} else if ($_REQUEST['CALL_AJAX'] == 'registClubs') {
			// 登録クラブの一覧を更新
			registClub($db, $_REQUEST);

		} else if ($_REQUEST['CALL_AJAX'] == 'getPrefixOrders') {
			// プリフィクス表示順の一覧を取得
			$result['prefixOrders'] = getPrefixOrders($db);

		} else if ($_REQUEST['CALL_AJAX'] == 'registPrefixOrders') {
			// プリフィクス表示順の一覧を登録
			registPrefixOrders($db, $_REQUEST);
		}
	}

	$db->commit();

} catch (PDOException $pe) {
	$db->rollBack();
	$result = array(
			'RESULTCD'	=> $pe->getCode(),
			'MESSAGE'	=> $pe->getMessage());

} finally {
	$db		= null;
}

echo	json_encode($result);

/* ========================================================================== */
/* 参加カテゴリーと対象周波数帯                                                 */
/* ========================================================================== */
/**
 * 参加カテゴリーの一覧を登録
 * @param PDO $db ＤＢ接続
 * @param array $data 登録データ
 * @throws PDOException エラー時
 */
function registCategories(PDO $db, array $data) {

	// 既存のカテゴリーを削除
	Category::delete($db);

	$categories = array();
	for ($i = 0; $i < count($data['codes']); $i++) {
		$category = new Category(array(
				'code'		=> $data['codes'][$i],
				'disporder'	=> $i + 1,
				'name'		=> $data['names'][$i]));

		$temp = array();
		foreach ($data["bands_{$i}"] as $band) {
			$temp[] = new Band(array(
					'category'	=> $data['codes'][$i],
					'freq'		=> $band));
		}
		$category->bands = $temp;
		$categories[] = $category;
	}

	Category::insert($db, $categories);
}

/* ========================================================================== */
/* 同時提出可否マスター                                                       */
/* ========================================================================== */
/**
 * 同時提出可否の一覧を取得
 * @param PDO $db ＤＢ接続
 * @return array 同時提出可否の一覧
 */
function getDuplicates(PDO $db) {

	$result = array();
	$SQL = <<<EOF
SELECT
	REPLACE(code1, '.', 'r') AS code1,
	REPLACE(code2, '.', 'r') AS code2,
	enabled
FROM
	m_categories_dup
EOF;
	$stmt = $db->prepare($SQL);
	$stmt->execute();
	$result = $stmt->fetchAll();
	$stmt->closeCursor();

	return $result;
}

/**
 * 同時提出可否の一覧を登録
 * @param PDO $db ＤＢ接続
 * @param unknown $data 登録データ
 * @throws PDOException エラー時
 */
function registDuplicates(PDO $db, $data) {

	$stmt = $db->prepare('DELETE FROM m_categories_dup');
	$stmt->execute();

	$stmt = $db->prepare('INSERT INTO m_categories_dup (code1, code2, enabled) VALUES (:code1, :code2, :enabled)');

	for ($i = 0; $i < count($data['keys']); $i++) {
		list($code1, $code2) = explode('_', $data['keys'][$i]);
		$code1 = str_replace('r', '.', $code1);
		$code2 = str_replace('r', '.', $code2);

		$stmt->bindValue(':code1',		$code1);
		$stmt->bindValue(':code2',		$code2);
		$stmt->bindValue(':enabled',	$data['values'][$i]);
		$stmt->execute();

		if ($code1 != $code2) {
			$stmt->bindValue(':code1',		$code2);
			$stmt->bindValue(':code2',		$code1);
			$stmt->bindValue(':enabled',	$data['values'][$i]);
			$stmt->execute();
		}
	}
}

/* ========================================================================== */
/* マルチプライヤー                                                           */
/* ========================================================================== */
/**
 * マルチプライヤーの一覧を取得
 * @param PDO $db ＤＢ接続
 * @return array[][]|unknown[][]|mixed マルチプライヤーの一覧
 */
function getMultipliers(PDO $db) {

	$result = array();
	$SQL = <<<EOF
SELECT
	MM.code,
	MM.name,
	MM.point,
	REPLACE(MM.category, '.', 'r') AS category,
	CASE WHEN LENGTH(MM.code) = 3 THEN 0 ELSE 1 END AS key
FROM
	m_multipliers  MM
	LEFT JOIN m_categories MC
		ON	MC.code = MM.category
ORDER BY
	key,
	MM.code,
	MC.disporder
EOF;
	$stmt = $db->prepare($SQL);
	$stmt->execute();

	$i = -1;
	$oldCode = '';
	while ($record = $stmt->fetch()) {
		if ($oldCode !== $record['code']) {
			$result[++$i] = array(
					'code'			=> $record['code'],
					'name'			=> $record['name'],
					'point'			=> $record['point'],
					'categories'	=> array());
			$oldCode = $record['code'];
		}
		$result[$i]['categories'][] = $record['category'];
	}
	$stmt->closeCursor();

	return $result;
}

/**
 * マルチプライヤーの一覧を登録
 * @param PDO $db ＤＢ接続
 * @param unknown $data 登録データ
 * @throws PDOException エラー時
 */
function registMultipliers(PDO $db, $data) {

	$stmt = $db->prepare('DELETE FROM m_multipliers');
	$stmt->execute();

	$stmt = $db->prepare('INSERT INTO m_multipliers (category, code, name, point) VALUES (:category, :code, :name, :point)');

	for ($i = 0; $i < count($data['codes']); $i++) {

		foreach ($data["cats_{$i}"] as $category) {
			$stmt->bindValue(':category',	$category);
			$stmt->bindValue(':code',		$data['codes'][$i]);
			$stmt->bindValue(':name',		$data['names'][$i]);
			$stmt->bindValue(':point',		$data['points'][$i]);
			$stmt->execute();
		}
	}
}

/* ========================================================================== */
/* 周波数帯ごとの交信時間帯                                                   */
/* ========================================================================== */
/**
 * 周波数帯ごとの交信時間帯の一覧を登録
 * @param PDO $db ＤＢ接続
 * @param array $data 登録データ
 * @throws PDOException エラー時
 */
function registPeriods(PDO $db, array $data) {

	Period::delete($db);

	$periods = array();
	foreach ($data['freqs'] as $freq) {
		$periods[] = new Period(array(
			'freq'		=> $freq,
			'starttime'	=> "{$data['starttimes'][$freq]}+09",
			'endtime'	=> "{$data['endtimes'][$freq]}+09"));
	}
	Period::insert($db, $periods);
}

/* ========================================================================== */
/* モード区分                                                                 */
/* ========================================================================== */
/**
 * モード区分の一覧を取得
 * @param PDO $db ＤＢ接続
 * @return array モード区分の一覧
 */
function getModecats(PDO $db) {

	$result = array();
	$SQL = <<<EOF
SELECT
	mode,
	category,
	disp_name AS disp
FROM
	m_modecat
ORDER BY
	mode
EOF;
	$stmt = $db->query($SQL);
	$result = $stmt->fetchAll();
	$stmt->closeCursor();

	return $result;
}

/**
 * モード区分の一覧を登録
 * @param PDO $db ＤＢ接続
 * @param array $data 登録データ
 * @throws PDOException エラー時
 */
function registModecats(PDO $db, array $data) {

	$stmt = $db->prepare('DELETE FROM m_modecat');
	$stmt->execute();

	$stmt = $db->prepare('INSERT INTO m_modecat (mode, category, disp_name) VALUES (:mode, :category, :disp_name)');

	for ($i = 0; $i < count($data['modes']); $i++) {
		$stmt->bindValue(':mode',		$data['modes'][$i]);
		$stmt->bindValue(':category',	$data['categories'][$i]);
		$stmt->bindValue(':disp_name',	(empty($data['disp_names'][$i]) ? null : $data['disp_names'][$i]));
		$stmt->execute();
	}
}

/* ========================================================================== */
/* 登録クラブ一覧                                                             */
/* ========================================================================== */
/**
 * 登録クラブの一覧を登録
 * @param PDO $db ＤＢ接続
 * @param array $data 登録データ
 * @throws PDOException エラー時
 */
function registClub(PDO $db, array $data) {

	// 既存の登録クラブを削除
	RegClub::delete($db);

	$clubs = array();
	for ($i = 0; $i < count($data['numbers']); $i++) {
		$clubs[] = new RegClub(array(
			'number'	=> $data['numbers'][$i],
			'name'		=> $data['names'][$i]));
	}

	RegClub::insert($db, $clubs);
}


/* ========================================================================== */
/* プリフィクス表示順                                                         */
/* ========================================================================== */
/**
 * プリフィクスの表示順を取得
 * @param PDO $db ＤＢ接続
 * @return array プリフィクスの表示順
 */
function getPrefixOrders(PDO $db) {

	$result = array();
	$SQL = <<<EOF
SELECT
	disporder,
	prefix
FROM
	common.m_prefix_order
ORDER BY
	disporder
EOF;
	$stmt = $db->query($SQL);
	$result = $stmt->fetchAll();
	$stmt->closeCursor();

	return $result;
}

/**
 * プリフィクスの表示順を登録
 * @param PDO $db ＤＢ接続
 * @param unknown $data 登録データ
 * @throws PDOException エラー発生時
 */
function registPrefixOrders(PDO $db, $data) {

	$stmt = $db->prepare('DELETE FROM common.m_prefix_order');
	$stmt->execute();

	$stmt = $db->prepare('INSERT INTO common.m_prefix_order (disporder, prefix) VALUES (:disporder, :prefix)');

	for ($i = 0; $i < count($data['prefixes']); $i++) {
		$stmt->bindValue(':disporder',	$i + 1);
		$stmt->bindValue(':prefix',		$data['prefixes'][$i]);
		$stmt->execute();
	}
}
