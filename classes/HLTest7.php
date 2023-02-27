<?php
include_once 'common.php';
include_once 'LogData.php';
include_once 'Summary.php';

/**
 * HLTEST(V7以前)データ
 * @author JJ4KME
 */
class HLTest7 extends LogData {

	private const BANDS = array(
		   1900	=> '/^1\.[89]$/',
		   3500	=> '/^3\.5$/',
		   7000	=> '/^7$/',
		  14000	=> '/^14$/',
		  21000	=> '/^21$/',
		  28000	=> '/^2[89]$/',
		  50000	=> '/^5[0-4]$/',
		 144000	=> '/^14[45]$/',
		 430000	=> '/^43[0-9]$/',
		1200000 => '/^12\d{2}$/');

	private const MODES = array(
		'SSB'	=> 'P',
		'CW'	=> 'G',
		'RTTY'	=> 'D',
		'FM'	=> 'P');

	public static function readFile(PDO $db, $path) {

		$summary = null;
		$fp = fopen($path, 'r');
		while (!feof($fp)) {
			$line = fgets($fp);
			$line = trim($line);
			$line = preg_replace('/\n$/', '', $line);

			if ($summary === null && preg_match('/^<SUMMARYSHEET/', $line)) {
				// サマリーの始まりだったら
				$summary = '';

			} else if ($summary !== null && preg_match('/^<\/SUMMARYSHEET>$/', $line)) {
				// サマリーの終わりだったら
				$sumData = self::readSummary($summary);
				$sumData->filename = basename($path);
				$sumData->status = STATUS_LOGGED;
				$logData = new HLTest7($db);
				$summary = null;

			} else if ($summary !== null) {
				// サマリーの途中だったら
				$summary .= $line;

			} else if (preg_match('/^\d/', $line)) {
				// ＱＳＯデータだったら
				$logData->initialize();

				// 周波数帯を調べる
				foreach (self::BANDS as $freq => $pattern) {
					if (preg_match($pattern, trim(substr($line, 63, 6)))) {
						$logData->freq = $freq;
						break;
					}
				}

				// モード区分を調べる
				$logData->mode = trim(substr($line, 69));
				$logData->modecat = (isset(self::MODES[trim(substr($line, 69))]) ? self::MODES[trim(substr($line, 69))] : null);

				// 交信日時を作る
				$logData->datetime = new DateTime("{$year}/". substr($line, 0, 11));
				// 送信リポート
				$logData->sentrst = self::correctRst(substr($line, 23, 4));
				// 送信マルチ
				$logData->sentmulti = self::correctMulti(substr($line, 27, 8));
				// 自局コールサイン
				$logData->owner = $sumData->callsign;
				// 相手局コールサイン
				$logData->callsign = trim(substr($line, 12, 11));
				// 受信リポート
				$logData->recvrst = self::correctRst(substr($line, 35, 4));
				// 受信マルチ
				$logData->recvmulti = self::correctMulti(substr($line, 39, 8));
				$sumData->addLog(clone $logData);
			}
		}
		fclose($fp);

		return $sumData;
	}
}
