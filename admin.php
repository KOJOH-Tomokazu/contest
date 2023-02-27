<?php
include_once 'common.php';
include_once 'libs/MyPDO.php';
include_once 'classes/Administrator.php';
include_once 'classes/GlobalStatus.php';
include_once 'classes/Schemas.php';
error_log(preg_replace('/\s+/', ' ', print_r($_REQUEST, true)));
try {
	$db = new MyPDO('contest');
	$db->beginTransaction();

	if (isset($_REQUEST['CALL_AJAX'])) {
		// AJAX呼び出し
		$result = array('RESULTCD' => 0, 'MESSAGE' => '');

		if ($_REQUEST['CALL_AJAX'] == 'initialize') {
			// 初期処理
			$source = Schemas::get($db, empty($_REQUEST['schema']) ? NULL : $_REQUEST['schema']);
			$result['schemas'] = array();
			foreach ($source as $schema) {
				$result['schemas'][] = $schema->toArray();
			}

		} else if ($_REQUEST['CALL_AJAX'] == 'login') {
			// ログイン
			$users = Administrator::get($db, $_REQUEST['PHP_AUTH_USER']);
			if (count($users) == 0) {
				http_response_code(401);
				$result['MESSAGE'] = 'ユーザーＩＤまたはパスワードが違います';

			} else if (!$users[0]->verify($_REQUEST['PHP_AUTH_PASS'], $_REQUEST['schema'])) {
				http_response_code(401);
				$result['MESSAGE'] = 'ユーザーＩＤまたはパスワードが違います';

			} else {
				$users[0]->lastlogin = new DateTime();
				$users[0]->register($db);
				setcookie('schema',		$_REQUEST['schema']);
				setcookie('user_id',	$_REQUEST['PHP_AUTH_USER']);
			}

		} else if ($_REQUEST['CALL_AJAX'] == 'logout') {
			// ログアウト
			setcookie('schema', 	'', time() - 1);
			setcookie('user_id',	'', time() - 1);

		} else if ($_REQUEST['CALL_AJAX'] == 'get_user') {
			// ユーザー情報の取得
			$users = Administrator::get($db, $_COOKIE['user_id']);
			$result['user'] = $users[0]->toArray();

			$schemas = Schemas::get($db, $_COOKIE['schema']);
			$result['schema'] = $schemas[0]->toArray();

		} else if ($_REQUEST['CALL_AJAX'] == 'get_deadline') {
			// 提出期限を取得
			$db->query("SET search_path TO {$_COOKIE['schema']}");
			$result['deadlines'] = GlobalStatus::getDeadlines($db);

		} else if ($_REQUEST['CALL_AJAX'] == 'set_deadline') {
			// 提出期限を設定
			$db->query("SET search_path TO {$_COOKIE['schema']}");
			GlobalStatus::setDeadlines($db,
					"{$_REQUEST['deadline1']} 23:59:59",
					"{$_REQUEST['deadline2']} 23:59:59");
		}
	}

	$db->commit();

} catch (PDOException $pe) {
	$db->rollBack();
	error_log($pe->getTraceAsString());
	$result = array(
			'RESULTCD'	=> $pe->getCode(),
			'MESSAGE'	=> $pe->getMessage());

} finally {
	$db		= null;
}

echo	json_encode($result);
