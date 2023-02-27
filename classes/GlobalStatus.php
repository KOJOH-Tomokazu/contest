<?php
/**
 * 全体ステータスクラス
 * @author JJ4KME
 */
class GlobalStatus {

	private const STATUS_FILE = 'status.json';
	/** 全体ステータス(開始前) */
	public const GLOBAL_NOTSTART = 0;
	/** 全体ステータス(開催中) */
	public const GLOBAL_PRESENT = 1;
	/** 全体ステータス(終了) */
	public const GLOBAL_FINISHED = 2;
	/** 全体ステータス(チェックログのみ) */
	public const GLOBAL_ADDITIONAL = 3;
	/** 全体ステータス(ログ締め切り) */
	public const GLOBAL_DEADLINE = 4;
	/** 全体ステータスと表示ラベル */
	public static $globals = array(
			self::GLOBAL_NOTSTART	=> 'コンテスト開始前',
			self::GLOBAL_PRESENT	=> 'コンテスト開催中',
			self::GLOBAL_FINISHED	=> 'コンテスト終了',
			self::GLOBAL_ADDITIONAL	=> 'チェックログのみ',
			self::GLOBAL_DEADLINE	=> 'ログ締め切り');

	public static function getStatus(PDO $db) {

		$stmtSettings = $db->query('SELECT deadline1, deadline2 FROM settings');
		$record = $stmtSettings->fetch();
		if ($record === FALSE) {
			return GlobalStatus::GLOBAL_NOTSTART;
		}
		$deadline1	= (new DateTime($record['deadline1']))->format('U');
		$deadline2	= (new DateTime($record['deadline2']))->format('U');

		$stmtPeriod = $db->query('SELECT MIN(starttime), MAX(endtime) FROM m_periods');
		$record = $stmtPeriod->fetch(PDO::FETCH_NUM);

		$nowDate	= (new DateTime())->format('U');
		$minDate	= (new DateTime($record[0]))->format('U');
		$maxDate	= (new DateTime($record[1]))->format('U');

		$result = GlobalStatus::GLOBAL_DEADLINE;
		if ($nowDate < $minDate) {
			$result = GlobalStatus::GLOBAL_NOTSTART;

		} else if ($nowDate < $maxDate) {
			$result = GlobalStatus::GLOBAL_PRESENT;

		} else if ($nowDate < $deadline1) {
			$result = GlobalStatus::GLOBAL_FINISHED;

		} else if ($nowDate < $deadline2) {
			$result = GlobalStatus::GLOBAL_ADDITIONAL;
		}

		return $result;
	}

	public static function getDeadlines(PDO $db) {

		$result = array(
				'deadline1'	=> '',
				'deadline2'	=> '');

		$stmt = $db->query("SELECT TO_CHAR(deadline1, 'YYYY-MM-DD') AS deadline1, TO_CHAR(deadline2, 'YYYY-MM-DD') AS deadline2 FROM settings");
		$record = $stmt->fetch();
		if ($record !== FALSE) {
			// 登録済みだったら
			$result = $record;
		}

		return $result;
	}

	public static function setDeadlines(PDO $db, string $deadline1, string $deadline2) {

		$db->query('DELETE FROM settings');
		$stmt = $db->prepare('INSERT INTO settings VALUES(:deadline1, :deadline2)');
		$stmt->bindValue(':deadline1',	$deadline1);
		$stmt->bindValue(':deadline2',	$deadline2);
		$stmt->execute();
	}
}
