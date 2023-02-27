<?php
include_once 'common.php';
include_once 'LogData.php';
include_once 'Summary.php';

/**
 * HLTEST(V8以降)データ
 * @author JJ4KME
 */
class HLTest8 extends LogData {

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
				$logData = new HLTest8($db);
				$summary = null;

			} else if ($summary !== null) {
				// サマリーの途中だったら
				$summary .= $line;

			} else if (preg_match('/^\d/', $line)) {
				// ＱＳＯデータだったら
				$logData->initialize();

				$temp = preg_split('/\s+/', $line);

				// 周波数帯を調べる
				foreach (self::BANDS as $freq => $pattern) {
					if (preg_match($pattern, trim($temp[2]))) {
						$logData->freq = $freq;
						break;
					}
				}

				// モード区分を調べる
				$logData->mode = trim($temp[3]);
				$logData->modecat = (isset(self::MODES[trim($temp[3])]) ? self::MODES[trim($temp[3])] : null);

				// 交信日時を作る
				$logData->datetime = new DateTime("{$temp[0]} {$temp[1]}");
				// 送信リポート
				$logData->sentrst = self::correctRst($temp[5]);
				// 送信マルチ
				$logData->sentmulti = self::correctMulti($temp[6]);
				// 自局コールサイン
				$logData->owner = $sumData->callsign;
				// 相手局コールサイン
				$logData->callsign = trim($temp[4]);
				// 受信リポート
				$logData->recvrst = self::correctRst($temp[7]);
				// 受信マルチ
				$logData->recvmulti = self::correctMulti($temp[8]);

				$sumData->addLog(clone $logData);
			}
		}
		fclose($fp);

		return $sumData;
	}
}
