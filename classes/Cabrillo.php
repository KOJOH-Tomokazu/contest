<?php
set_include_path('/home/contest/public_html/classes');
include_once 'common.php';
include_once 'LogData.php';
include_once 'Summary.php';

/**
 * キャブリロデータ
 * @author JJ4KME
 */
class Cabrillo extends LogData {

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
		'PH'	=> 'P',
		'CW'	=> 'G',
		'RY'	=> 'D',
		'FM'	=> 'P');

	public static function readFile(PDO $db, $path) {

		$sumData = new Summary();
		$sumData->filename = basename($path);
		$sumData->status = STATUS_LOGGED;
		$logData = new Cabrillo($db);

		$fp = fopen($path, 'r');
		while (!feof($fp)) {
			$line = fgets($fp);
			$line = trim($line);
			$line = preg_replace('/\n$/', '', $line);

			if (preg_match('/^START-OF-LOG:/', $line)) {
				// ログ開始行だったら
				// バージョンを取得
				$version = intval(trim(substr($line, 13)));

			} else if ($version == 2 && preg_match('/^CATEGORY:/', $line)) {
				// カテゴリーコードだったら
				$sumData->category = trim(substr($line, 9));

			} else if ($version == 3 && preg_match('/^CATEGORY\-STATION:/', $line)) {
				$sumData->category .= ' '. trim(substr($line, 17));

			} else if ($version == 3 && preg_match('/^CATEGORY\-BAND:/', $line)) {
				$sumData->category .= ' '. trim(substr($line, 14));

			} else if ($version == 3 && preg_match('/^CATEGORY\-OPERATOR:/', $line)) {
				$sumData->category .= ' '. trim(substr($line, 18));

			} else if (preg_match('/^CALLSIGN:/', $line)) {
				// コールサインだったら
				$sumData->callsign = trim(substr($line, 9));

			} else if (preg_match('/^NAME:/', $line)) {
				// 氏名だったら
				$sumData->name = trim(substr($line, 5));

			} else if (preg_match('/^ADDRESS:/', $line, $temp)) {
				// 住所だったら
				$sumData->address .= trim(substr($line, 8));

			} else if (preg_match('/^EMAIL:/', $line)) {
				// メールアドレスだったら
				$sumData->email = trim(substr($line, 6));

			} else if (preg_match('/^OPERATORS:/', $line)) {
				// 運用者リストだったら
				$sumData->multioplist = trim(substr($line, 10));

			} else if (preg_match('/^CLUB:/', $line)) {
				// 登録クラブ番号だったら
				$sumData->multioplist = trim(substr($line, 5));

			} else if (preg_match('/^QSO:/', $line)) {
				// ＱＳＯデータだったら
				$logData->initialize();

				$line = trim(substr($line, 4));
				$temp = preg_split('/\s+/', $line);

				// 周波数帯を調べる
				foreach (self::BANDS as $freq => $pattern) {
					if (preg_match($pattern, $temp[0])) {
						$logData->freq = $freq;
						break;
					}
				}

				// モード区分を調べる
				$logData->mode = $temp[1];
				$logData->modecat = (isset(self::MODES[$temp[1]]) ? self::MODES[$temp[1]] : null);

				// 交信日時を作る
				$logData->datetime = new DateTime($temp[2]. ' '. substr($temp[3], 0, 2). ':'. substr($temp[3], 2, 2));
				// 自局コールサイン
				$logData->owner = $temp[4];
				// 送信リポート
				$logData->sentrst = self::correctRst($temp[5]);
				// 送信マルチ
				$logData->sentmulti = self::correctMulti($temp[6]);
				// 相手局コールサイン
				$logData->callsign = $temp[7];
				// 受信リポート
				$logData->recvrst = self::correctRst($temp[8]);
				// 受信マルチ
				$logData->recvmulti = self::correctMulti($temp[9]);
				$sumData->addLog(clone $logData);
			}
		}
		fclose($fp);

		return $sumData;
	}
}
