<?php
include_once 'common.php';
include_once 'LogData.php';
include_once 'Summary.php';

/**
 * CTESTWINデータ
 * @author JJ4KME
 */
class CtestWin extends LogData {

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
				$logData = new CtestWin($db);
				$summary = null;

			} else if ($summary !== null) {
				// サマリーの途中だったら
				$summary .= $line;

			} else if (isset($sumData) && preg_match('/^\d/', $line)) {
				// ＱＳＯデータだったら
				$logData->initialize();
#-- START
				$temp = preg_split('/\s+/', $line);

				// 周波数帯を調べる
				foreach (self::BANDS as $freq => $pattern) {
					if (preg_match($pattern, $temp[2])) {
						$logData->freq = $freq;
						break;
					}
				}

				// モード区分を調べる
				$logData->mode = $temp[3];
				$logData->modecat = (isset(self::MODES[$temp[3]]) ? self::MODES[$temp[3]] : null);

				// 交信日時を作る
				$logData->datetime = new DateTime("{$temp[0]} {$temp[1]}");
				// 送信リポート
				$logData->sentrst = self::correctRst($temp[5]);
				// 送信マルチ
				$logData->sentmulti = self::correctMulti($temp[6]);
				// 自局コールサイン
				$logData->owner = $sumData->callsign;
				// 相手局コールサイン
				$logData->callsign = $temp[4];
				// 受信リポート
				$logData->recvrst = self::correctRst($temp[7]);
				// 受信マルチ
				$logData->recvmulti = self::correctMulti($temp[8]);
				$sumData->addLog(clone $logData);
/*
				// 周波数帯を調べる
				foreach (self::BANDS as $freq => $pattern) {
					if (preg_match($pattern, trim(substr($line, 17, 4)))) {
						$logData->freq = $freq;
						break;
					}
				}

				// モード区分を調べる
				$logData->mode = trim(substr($line, 23, 6));
				$logData->modecat = (isset(self::MODES[$logData->mode]) ? self::MODES[$logData->mode]) : NULL);

				// 交信日時を作る
				$logData->datetime = new Datetime(substr($line, 0, 16). ':00');
				// 送信リポート
				$logData->sentrst = rtrim(substr($line, 43, 4));
				// 送信マルチ
				$logData->sentmulti = rtrim(substr($line, 47, 8));
				// 自局コールサイン
				$logData->owner = $sumData->callsign;
				// 相手局コールサイン
				$logData->callsign = rtrim(substr($line, 29, 14));
				// 受信リポート
				$logData->recvrst = rtrim(substr($line, 55, 4));
				// 受信マルチ
				$logData->recvmulti = rtrim(substr($line, 59, 8));
				$logData->addLog(clone $logData);
*/
#-- END
			}
		}
		fclose($fp);

		return $sumData;
	}
}
/*
DATE (JST) TIME   BAND MODE  CALLSIGN      SENTNo      RCVDNo      Mlt    Pts
2022-03-13 12:17    7  CW    JR4QED        599 20      599 3505    3505    1
2022-03-13 12:19    7  CW    JS3CGH/4      599 20      599 31019   31019   1
2022-03-13 12:24    7  CW    JA4MSM        599 20      599 3403    3403    1
2022-03-13 12:49    7  CW    JA4VPS        599 20      599 3514    3514    1
2022-03-13 12:52    7  CW    JA4RQO        599 20      599 3203    3203    1
2022-03-13 12:54    7  CW    JH4IGT        599 20      599 310103  310103  1
2022-03-13 13:01    7  CW    JA4FDZ        599 20      599 3513    3513    1
2022-03-13 13:12    7  CW    JO4JFH        599 20      599 3117    3117    1
2022-03-13 13:20    7  SSB   JA4HIP        59  20      59  3117    -       1
2022-03-13 13:26    7  SSB   JO4HKO        59  20      59  3403    -       1
2022-03-13 13:27    7  SSB   JS3CGH/4      59  20      59  31019   -       1
2022-03-13 13:31    7  SSB   JM4JFB        59  20      59  3302    3302    1
2022-03-13 13:33    7  SSB   JG4IBI        59  20      59  3513    -       1
2022-03-13 13:35    7  SSB   JN4JJJ/4      59  20      59  32003   32003   1
2022-03-13 13:51    7  CW    JR6KBF/4      599 20      599 3313    3313    1
2022-03-13 13:59    7  CW    JE4EZP/4      599 20      599 3316    3316    1
2022-03-13 14:04    7  CW    JK3HFN/4      599 20      599 3114    3114    1
2022-03-13 14:05    7  CW    JH4EYD        599 20      599 3401    3401    1
2022-03-13 14:08    7  CW    JH1MTR/4      599 20      599 3401    -       1
2022-03-13 14:09    7  CW    JA4IAQ        599 20      599 3306    3306    1
2022-03-13 14:11    7  CW    JA4CZM        599 20      599 310101  310101  1
2022-03-13 14:14    7  CW    JE4UNT        599 20      599 3203    -       1
2022-03-13 14:18    7  CW    JQ3BCT        599         599         -       0
2022-03-13 14:20    7  CW    JH4LRD        599 20      599 32004   32004   1
2022-03-13 14:21    7  CW    JL7GZH        599         599         -       0
2022-03-13 14:23    7  CW    JL4ENS        599 20      599 3508    3508    1
2022-03-13 14:26    7  CW    JK1KSV        599         599         -       0
2022-03-13 14:28    7  CW    JJ4KME        599 20      599 3502    3502    1
2022-03-13 14:34    7  CW    JA1EOG        599         599         -       0
2022-03-13 14:37    7  CW    JH8LGU        599         599         -       0
2022-03-13 14:37    7  CW    JH4JUK        599 20      599 3503    3503    1
2022-03-13 14:39    7  CW    JE6CIY/4      599 20      599 3301    3301    1
2022-03-13 14:41    7  CW    JR4JRP        599 20      599 3404    3404    1
2022-03-13 14:43    7  CW    JM4AOA        599 20      599 3508    -       1
2022-03-13 14:50    7  CW    JR4DTG        599 20      599 3308    3308    1
2022-03-13 15:04    7  CW    7N3WEJ        599         599         -       0
2022-03-13 15:12    7  CW    JE1OFR        599         599         -       0
2022-03-13 15:13    7  CW    JR8KQS        599         599         -       0
2022-03-13 15:15    7  CW    JA6EXD        599         599         -       0
2022-03-13 15:17    7  CW    JJ3CDK        599         599         -       0
2022-03-13 15:23   14  CW    JA4LCI        599 20      599 3304    3304    1
2022-03-13 16:16    7  CW    JH4WXV        599 20      599 3310    3310    1
2022-03-13 16:49  3.5  CW    JA4RQO        599 20      599 3203    3203    1
2022-03-13 16:52    7  SSB   JH4FUF        59  20      59  3502    -       1
2022-03-13 16:54    7  SSB   JH1MTR/4      59  20      59  3401    -       1
2022-03-13 16:55    7  SSB   JO4KUZ        59  20      59  350104  350104  1
2022-03-13 16:58    7  SSB   JO4GFZ        59  20      59  35016   35016   1
2022-03-13 17:01    7  SSB   JM4CNU        59  20      59  3502    -       1
2022-03-13 17:04    7  SSB   JR4GGT        59  20      59  3308    -       1
2022-03-13 17:15    7  CW    JH4FUF        599 20      599 3502    -       1
2022-03-13 17:18    7  CW    JH4OYD        599 20      599 3301    -       1
2022-03-13 17:33    7  CW    JN4THO        599 20      599 3509    3509    1
2022-03-13 17:35  3.5  CW    JK3HFN/4      599 20      599 3114    3114    1
2022-03-13 17:37  3.5  CW    JH1MTR/4      599 20      599 3401    3401    1
2022-03-13 17:38  3.5  CW    JR4ZUZ        599 20      599 3110    3110    1
2022-03-13 17:39  3.5  CW    JO4MAN/4      599 20      599 350105  350105  1
2022-03-13 18:21  3.5  CW    JH4LRD        599 20      599 32004   32004   1
2022-03-13 18:22  3.5  CW    JA4YWH        599 20      599 3313    3313    1
2022-03-13 18:23  3.5  CW    JE6CIY/4      599 20      599 3301    3301    1
2022-03-13 18:32  1.8  CW    JH4OYD        599 20      599 3301    3301    1
2022-03-13 18:35  1.8  CW    JK3HFN/4      599 20      599 3114    3114    1
2022-03-13 19:53  3.5  CW    JR6KBF/4      599 20      599 3313    -       1
2022-03-13 19:54  3.5  CW    JH4FUF        599 20      599 3502    3502    1
2022-03-13 19:55  3.5  CW    JA4VNE        599 20      599 3301    -       1
2022-03-13 19:57  3.5  CW    JA4MMO        599 20      599 3316    3316    1
2022-03-13 20:00  3.5  CW    JN4THO        599 20      599 3509    3509    1
2022-03-13 20:01  1.8  CW    JA4FVC        599 20      599 3502    3502    1
2022-03-13 20:06  1.8  SSB   JR4ZUZ        59  20      59  3110    3110    1
2022-03-13 20:07  1.8  SSB   JA4CZM        59  20      59  310101  310101  1
2022-03-13 20:12  3.5  CW    JA7TJ         599         599         -       0
*/

