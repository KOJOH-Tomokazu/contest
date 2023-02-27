<?php
include_once 'common.php';
include_once 'libs/MyPDO.php';
include_once 'classes/Category.php';
include_once 'classes/Summary.php';
include_once 'classes/Score.php';

try {
	$db = new MyPDO('contest');
	$db->beginTransaction();

	if (isset($_REQUEST['CALL_AJAX'])) {
		// AJAX呼び出し
		$result = array('sucess' => TRUE);

		if ($_REQUEST['CALL_AJAX'] == 'showDetail') {
			// 詳細表示
			$summary = Summary::get($db, $_REQUEST['sumid']);
			if ($summary === null) {
				$result = array(
					'success'	=> FALSE,
					'code'		=> -1,
					'message'	=> 'サマリーが登録されていません(不正アクセス)');

			} else {
				if (isset($_COOKIE['PHP_AUTH_USER']) && isset($_COOKIE['PHP_AUTH_PASS']) &&
					$summary->status != STATUS_COMPLETE && $summary->status == STATUS_COLLATED) {
					$summary->status = STATUS_INSPECTING;
					$summary->update($db);
				}

				$result['categories'] = array();
				$source = Category::get($db);
				foreach ($source as $disp_order => $category) {
					$result['categories'][] = $category->toArray();
				}
				// 周波数帯の一覧
				$result['bands'] = $bandList;
				// エラーの一覧
				$result['errors'] = $errorList;
				$result['summary'] = $summary->toArray();
				$result['scores'] = array();
				foreach (Score::get($db, $_REQUEST['sumid']) as $freq => $score) {
					$result['scores'][$freq] = $score->toArray();
				}
				$result['ADMIN'] = isset($_COOKIE['PHP_AUTH_USER']);
			}

		} else if ($_REQUEST['CALL_AJAX'] == 'fixScore') {
			// 成績確定
			$summary = Summary::get($db, $_REQUEST['sumid']);
			if ($summary->status == STATUS_INSPECTING) {
				$summary->status = STATUS_COMPLETE;
				$summary->update($db);
			}
		}
	}

	$db->commit();

} catch (PDOException $pe) {
	$db->rollBack();
	$result = array(
		'success'	=> FALSE,
		'code'		=> $pe->getCode(),
		'message'	=> $pe->getMessage());

} finally {
	$db		= null;
}

echo	json_encode($result);
