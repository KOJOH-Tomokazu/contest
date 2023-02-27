<?php
include_once 'common.php';
include_once 'classes/Cabrillo.php';
include_once 'classes/Score.php';
include_once 'classes/Summary.php';
include_once 'libs/MyPDO.php';

try {
	$db = new MyPDO('ja4test');
	$ps4summary	= $db->prepare('SELECT COUNT(*) AS cnt FROM summary WHERE callsign = :callsign');
	$ps4search	= $db->prepare('SELECT * FROM logdata WHERE owner = :owner AND callsign = :callsign ORDER BY datetime');
	$ps4swl		= $db->prepare('SELECT * FROM logdata WHERE owner = :owner AND mode = :mode');
	$ps4multi	= $db->prepare('SELECT code FROM m_multipliers WHERE category = :category');
	$ps4period	= $db->prepare('SELECT MP.starttime, MP.endtime FROM m_bands MB INNER JOIN m_periods MP ON	MP.freq	= MB.freq WHERE MB.category	= :category AND MB.freq = :freq');

	$sentMultipliers = getSentMultipliers($db);

	$summaries = array();
	if (count($argv) == 1) {
		// サマリーＩＤが指定されていなかったら全て
		$summaries = Summary::get($db);

	} else if (count($argv) > 1) {
		// サマリーＩＤが指定されていたら
		$sumids = array();
		for ($i = 1; $i < count($argv); $i++) {
			$sumids[] = $argv[$i];
		}

		$summaries = Summary::get($db, $sumids);
	}

	foreach ($summaries as $summary) {
		if ($summary->status < STATUS_LOGGED) {
			// ログ登録済みより前のステータスだったら飛ばす
			continue;
		}

		$db->beginTransaction();
		if ($summary->category == 'CHL') {
			// 自身がチェックログだったらすべてチェックログ
			foreach ($summary->logData as $logData) {
				$logData->point = POINT_NONE;
				$logData->error = 'CL';
			}

		} else if (preg_match('/SWL$/', $summary->category)) {
			// ＳＷＬだったら
			$duplicateTable = array();
			foreach ($summary->logData as $logData) {
				if (isset($duplicateTable[$logData->duplicateKey])) {
					// 重複チェックテーブルに存在したら重複受信
					$logData->point = POINT_NONE;
					$logData->error = 'WC';
					continue;
				}

				// 有効交信時間帯を取得
				$period = getPeriod($db, $summary->category, $logData->freq);
				if (empty($period)) {
					// 集計対象外バンドだったらチェックログ
					$logData->point = POINT_NONE;
					$logData->error = 'CL';

				} else {
					// 集計対象バンドだったら
					if ($logData->datetime->format('U') < $period['start']->format('U') ||
						$logData->datetime->format('U') > $period['end']->format('U')) {
						// 時間帯を外れていたら
						$logData->point = POINT_NONE;
						$logData->error = 'OT';
						continue;
					}

					// 参加カテゴリーの有効マルチプライヤーを取得
					$multipliers = getMultipliers($db, $summary->category);

					if (existsSummary($db, $logData->callsign)) {
						// 相手方のログが提出済
						$destLogs = searchSWL($db, $logData->callsign, $logData->mode);
						if (in_array($logData->recvmulti, $multipliers)) {
							// マルチが有効
							$logData->point = POINT_MATCHED;
							$logData->error = NULL;

							$found = false;
							foreach ($destLogs as $destLog) {
								if ($logData->freq == $destLog->freq) {
									// バンドが一致したら
									$found = true;

									if ($logData->recvrst != $destLog->sentrst) {
										// 相手の送ってきたリポートをミスコピー
										$logData->point = POINT_NORMAL;
										$logData->error = 'RR';
										break;

									} else if ($logData->recvmulti != $destLog->sentmulti) {
										// 相手が送ってきたマルチをミスコピー
										$logData->point = POINT_NORMAL;
										$logData->error = 'MR';
										break;
									}

									break;
								}
							}

							if (!$found) {
								// バンドが一致しなかったら
								$logData->point = POINT_NORMAL;
								$logData->error = 'OF';
								continue;
							}
						} else {
							// マルチが無効
							$logData->point = POINT_NONE;
							$logData->error = 'OR';
							continue;
						}

					} else {
						// 相手方のログが未提出
						if (in_array($logData->recvmulti, $multipliers)) {
							// マルチが有効
							$logData->point = POINT_NORMAL;
							$logData->error = 'NL';
							continue;

						} else {
							// マルチが無効
							$logData->point = POINT_NONE;
							$logData->error = 'OR';
							continue;
						}
					}

					if ($logData->point != POINT_NONE) {
						// ０点でなかったら重複チェックテーブルに保存
						$duplicateTable[$logData->duplicateKey] = 1;
					}
				}
			}

		} else {
			$duplicateTable = array();
			foreach ($summary->logData as $logData) {
				try {
/* 2022/04/07 KOJOH */
#					if (isset($duplicateTable[$logData->duplicateKey])) {
#						// 重複チェックテーブルに存在したら重複交信
#						$logData->point = POINT_NONE;
#						$logData->error = 'WC';
#						throw new Exception();
#					}
/* 2022/04/07 KOJOH */
					// 有効交信時間帯を取得
					$period = getPeriod($db, $summary->category, $logData->freq);

					if (empty($period)) {
						// 集計対象外バンドだったらチェックログ
						$logData->point = POINT_NONE;
						$logData->error = 'CL';

					} else {
						// 集計対象バンドだったら
						if ($logData->datetime->format('U') < $period['start']->format('U') ||
							$logData->datetime->format('U') > $period['end']->format('U')) {
							// 時間帯を外れていたら
							$logData->point = POINT_NONE;
							$logData->error = 'OT';
							throw new Exception();
						}

						// 参加カテゴリーの有効マルチプライヤーを取得
						$multipliers = getMultipliers($db, $summary->category);
/* 2022/04/07 KOJOH */
#						if (!preg_match('/^[1-5][1-9]([1-9])?$/', $logData->sentrst)) {
#							// 送信リポートが無効だったら
#							$logData->point = POINT_NONE;
#							$logData->error = 'RS';
#							throw new Exception();
#
#						} else if (!in_array($logData->sentmulti, $sentMultipliers)) {
#							// 送信マルチが無効だったら
#							$logData->point = POINT_NONE;
#							$logData->error = 'OR';
#							throw new Exception();
#
#						} else if (!in_array($logData->recvmulti, $multipliers)) {
#							// 受信マルチが無効
#							$logData->point = POINT_NONE;
#							$logData->error = 'OR';
#							throw new Exception();
#
#						} else if (existsSummary($db, $logData->callsign)) {
#							// 相手方のログが提出済
#							$destLogs = searchLog($db, $logData->callsign, $logData->owner);
#							if (empty($destLogs)) {
#								// 相手方のログに見つからなかったら
#								$logData->point = POINT_NORMAL;
#								$logData->error = 'NF';
#								throw new Exception();
#
#							} else {
#								// 相手方のログに見つかったら
#								$logData->point = POINT_MATCHED;
#								$logData->error = NULL;
#
#								$found = false;
#								foreach ($destLogs as $destLog) {
#									if ($logData->freq == $destLog->freq && $logData->mode == $destLog->mode) {
#											// バンドとモードが一致したら
#										$found = true;
#
#										if (abs($logData->datetime->format('U') - $destLog->datetime->format('U')) > 600) {
#											// １０分以上の時間差
#											$logData->point = POINT_NORMAL;
#											$logData->error = 'TM';
#											break;
#
#										} else if ($logData->recvrst != $destLog->sentrst) {
#											// 相手の送ってきたリポートをミスコピー
#											$logData->point = POINT_NORMAL;
#											$logData->error = 'RR';
#											break;
#
#										} else if ($logData->recvmulti != $destLog->sentmulti) {
#											// 相手が送ってきたマルチをミスコピー
#											$logData->point = POINT_NORMAL;
#											$logData->error = 'MR';
#											break;
#										}
#
#										break;
#									}
#								}
#
#								if (!$found) {
#									// バンドが一致しなかったら
#									$logData->point = POINT_NORMAL;
#									$logData->error = 'OF';
#									throw new Exception();
#								}
#							}
#
#						} else {
#							// 相手方のログが未提出
#							if (in_array($logData->recvmulti, $multipliers)) {
#								// マルチが有効
#								$logData->point = POINT_NORMAL;
#								$logData->error = 'NL';
#								throw new Exception();
#
#							} else {
#								// マルチが無効
#								$logData->point = POINT_NONE;
#								$logData->error = 'OR';
#								throw new Exception();
#							}
#						}
						if (existsSummary($db, $logData->callsign)) {
							// 相手方のログが提出済み
							$destLogs = searchLog($db, $logData->callsign, $logData->owner);
							if (empty($destLogs)) {
								// 相手方のログに見つからなかったら
								$logData->point = POINT_NORMAL;
								$logData->error = 'NF';
								throw new Exception();

							} else {
								// 相手方のログに見つかったら
								$logData->point = POINT_MATCHED;
								$logData->error = NULL;

								$timeMatched = FALSE;
								$found = FALSE;
								foreach ($destLogs as $destLog) {
									if ($logData->freq == $destLog->freq && $logData->mode == $destLog->mode && abs($logData->datetime->format('U') - $destLog->datetime->format('U')) <= 600) {
										// バンドとモードが一致して１０分以内の時刻差だったら
										$timeMatched = TRUE;
										break;
									}
								}

								foreach ($destLogs as $destLog) {
									if ($logData->freq == $destLog->freq && $logData->mode == $destLog->mode) {
										// バンドとモードが一致したら
										$found = TRUE;

										if (!$timeMatched && abs($logData->datetime->format('U') - $destLog->datetime->format('U')) > 600) {
											// １０分以上の時刻差があったら
											$logData->point = POINT_NORMAL;
											$logData->error = 'TM';
											break;

										} else if ($logData->recvrst != $destLog->sentrst) {
											// 相手の送ってきたリポートをミスコピー
											$logData->point = POINT_NORMAL;
											$logData->error = 'RR';
											break;

										} else if ($logData->recvmulti != $destLog->sentmulti) {
											// 相手の送ってきたマルチをミスコピー
											$logData->point = POINT_NORMAL;
											$logData->error = 'MR';
											break;
										}

										break;
									}
								}

								if (!$found) {
									// バンド・モードが一致しなかったら
									$logData->point = POINT_NORMAL;
									$logData->error = 'OF';
									throw new Exception();
								}
							}

						} else {
							// 相手方のログが未提出
							if (!preg_match('/^[1-5][1-9]([1-9])?$/', $logData->sentrst)) {
								// 送信リポートが無効だったら
								$logData->point = POINT_NONE;
								$logData->error = 'RS';
								throw new Exception();

							} else if (!in_array($logData->sentmulti, $sentMultipliers)) {
								// 送信マルチが無効だったら
								$logData->point = POINT_NONE;
								$logData->error = 'OR';
								throw new Exception();

							} else if (!in_array($logData->recvmulti, $multipliers)) {
								// 受信マルチが無効だったら
								$logData->point = POINT_NORMAL;
								$logData->error = 'MR';
								throw new Exception();

							} else {
								// 通常交信
								$logData->point = POINT_NORMAL;
								$logData->error = 'NL';
								throw new Exception();
							}
						}
/* 2022/04/07 KOJOH */
					}
				} catch (Exception $px) {

				} finally {
/* 2022/04/07 KOJOH */
#					if ($logData->point != POINT_NONE) {
#						// ０点でなかったら重複チェックテーブルに保存
#						$duplicateTable[$logData->duplicateKey] = $logData->logid;
#					}
					if ($logData->point != POINT_NONE) {
						if (isset($duplicateTable[$logData->duplicateKey])) {
							if ($duplicateTable[$logData->duplicateKey] == POINT_MATCHED) {
								// 重複チェックテーブルに存在したら重複交信
								$logData->point = POINT_NONE;
								$logData->error = 'WC';
							}

						} else {
							// 重複チェックテーブルに存在しなかったら重複チェックテーブルに保存
							$duplicateTable[$logData->duplicateKey] = $logData->point;
						}
					}
/* 2022/04/07 KOJOH */
				}
			}
		}

		// ステータスを照合済みにして保存
		$summary->status = STATUS_COLLATED;
		$summary->update($db);

		// 合計点数と合計マルチを保存
		saveScore($db, $summary);
		$db->commit();

		echo	"{$summary->sumid}\n";
	}

} catch (PDOException $pe) {
	$db->rollBack();
	error_log($pe->getMessage());

} finally {
	$db = null;
}

/**
 * 指定したカテゴリー・周波数帯の開始・終了日時を取得
 * @param PDO $db ＤＢ接続
 * @param unknown $category カテゴリー
 * @param unknown $freq 周波数帯
 * @return array|DateTime[] 開始・終了日時
 */
function getPeriod(PDO $db, $category, $freq) {

	global $ps4period;

	$result = array();
	$ps4period->bindValue(':category',	$category);
	$ps4period->bindValue(':freq',		$freq);
	$ps4period->execute();

	while ($record = $ps4period->fetch()) {
		$result = array(
				'start'	=> new DateTime($record['starttime']),
				'end'	=> new DateTime($record['endtime']));
	}

	$ps4period->closeCursor();

	return $result;
}

/**
 * サマリーが存在するか調べる
 * @param PDO $db ＤＢ接続
 * @param unknown $callsign コールサイン
 * @return boolean 存在したらtrue、存在しなかったらfalse
 */
function existsSummary(PDO $db, $callsign) {

	global $ps4summary;

	$result = false;
	$ps4summary->bindValue(':callsign',	$callsign);
	$ps4summary->execute();

	$record = $ps4summary->fetch();
	if ($record['cnt'] > 0) {
		$result = true;
	}

	$ps4summary->closeCursor();

	return $result;
}

/**
 * ログを検索する
 * @param PDO $db ＤＢ接続
 * @param unknown $owner 送信者のコールサイン
 * @param unknown $callsign 相手方のコールサイン
 * @return Cabrillo[] ログデータ
 */
function searchLog(PDO $db, $owner, $callsign) {

	global $ps4search;

	$result = array();
	$ps4search->bindValue(':owner',		$owner);
	$ps4search->bindValue(':callsign',	$callsign);
	$ps4search->execute();

	while ($record = $ps4search->fetch()) {
		$result[] = new Cabrillo($db, $record);
	}

	$ps4search->closeCursor();

	return $result;
}

function searchSWL(PDO $db, $owner, $mode) {

	global $ps4swl;

	$result = array();

	$ps4swl->bindValue(':owner',	$owner);
	$ps4swl->bindValue(':mode',		$mode);
	$ps4swl->execute();

	while ($record = $ps4swl->fetch()) {
		$result[] = new Cabrillo($db, $record);
	}

	$ps4swl->closeCursor();

	return $result;
}

/**
 * 指定したカテゴリーの有効マルチプライヤーを取得
 * @param PDO $db ＤＢ接続
 * @param unknown $category カテゴリー
 * @return unknown[] マルチプライヤー
 */
function getMultipliers(PDO $db, $category) {

	global $ps4multi;

	$result = array();
	$ps4multi->bindValue(':category', $category);
	$ps4multi->execute();

	while ($record = $ps4multi->fetch()) {
		$result[] = $record['code'];
	}

	$ps4multi->closeCursor();

	return $result;
}

function getSentMultipliers(PDO $db) {

	$result = array();
	$stmt = $db->prepare('SELECT code FROM m_multipliers GROUP BY code');
	$stmt->execute();

	while ($record = $stmt->fetch()) {
		$result[] = $record['code'];
	}

	$stmt->closeCursor();

	return $result;
}

/**
 * 指定したサマリーの得点とマルチプライヤーを保存
 * @param PDO $db ＤＢ接続
 * @param Summary $summary サマリー
 */
function saveScore(PDO $db, Summary $summary) {

	$scores = array();
	$multipliers = array();
	foreach ($summary->logData as $logData) {
		if (!isset($scores[0])) {
			$scores[0] = new Score(array(
					'sumid'	=> $summary->sumid,
					'freq'	=> 0));
		}
		if (!isset($scores[$logData->freq])) {
			$scores[$logData->freq] = new Score(array(
					'sumid'	=> $logData->sumid,
					'freq'	=> $logData->freq));
		}

		// 交信件数を加算
		$scores[0]->numqso++;
		$scores[$logData->freq]->numqso++;
		// 点数を加算
		$scores[0]->point += $logData->point;
		$scores[$logData->freq]->point += $logData->point;
		// ミスコピー無ければマルチプライヤーを加算
		if ($logData->point > 0 && $logData->error != 'RR' && $logData->error != 'MR' && !isset($multipliers[$logData->freq][$logData->recvmulti])) {
			$multipliers[$logData->freq][$logData->recvmulti] = 1;
			$scores[0]->multi++;
			$scores[$logData->freq]->multi++;
		}
	}

	// 既存のスコアを削除
	Score::delete($db, $summary->sumid);
	// スコアを保存
	Score::insert($db, array_values($scores));
}
