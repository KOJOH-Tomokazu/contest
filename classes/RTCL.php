<?php

/**
 * RTCLデータ
 * @author JJ4KME
 */
class RTCL extends \LogData {

	private const BANDS = array(
			   1900	=> '/^1[89]\d{2}$/',
			   3500	=> '/^3[56]\d{2}$/',
			   7000	=> '/^7[0-1]\d{2}$/',
			  14000	=> '/^14[0-3]\d{2}$/',
			  21000	=> '/^21[0-4]\d{2}$/',
			  28000	=> '/^2[89]\d{3}$/',
			  50000	=> '/^5[0-4]$/',
			 144000	=> '/^14[45]$/',
			 430000	=> '/^43[0-9]$/',
			1200000	=> '/^12\d{2}$/');

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
				$logData = new RTCL($db);
				$summary = null;

			} else if ($summary !== null) {
				// サマリーの途中だったら
				$summary .= $line;

			} else if (preg_match('/^\d/', $line)) {
				// ＱＳＯデータだったら
				$logData->initialize();

				// 周波数帯を調べる
				foreach (self::BANDS as $freq => $pattern) {
					if (preg_match($pattern, trim(substr($line, 16, 9)))) {
						$logData->freq = $freq;
						break;
					}
				}

				// モード区分を調べる
				$logData->mode = trim(substr($line, 25, 6));
				$logData->modecat = (isset(self::MODES[$logData->mode]) ? self::MODES[$logData->mode] : NULL);

				// 交信日時を作る
				$logData->datetime = new DateTime(substr($line, 0, 13). ':'. substr($line, 13, 2));
				// 送信リポート
				$logData->sentrst = trim(substr($line, 45, 4));
				// 送信マルチ
				$logData->sentmulti = trim(substr($line, 49, 9));
				// 自局コールサイン
				$logData->owner = $sumData->callsign;
				// 相手局コールサイン
				$logData->callsign = trim(substr($line, 31, 14));
				// 受信リポート
				$logData->recvrst = trim(substr($line, 58, 4));
				// 受信マルチ
				$logData->recvmulti = trim(substr($line, 62, 9));

				$sumData->addLog(clone $logData);
			}
		}
		fclose($fp);

		return $sumData;
	}
}

