<?php
include_once 'common.php';
include_once 'LogData.php';
include_once 'Summary.php';

/**
 * その他のデータ
 * @author JJ4KME
 */
class Others extends LogData {

	private const BANDS = array(
		  1900	=> '/^1\.[89]$/',
		  3500	=> '/^3\.5$/',
		  7000	=> '/^7$/',
		 14000	=> '/^14$/',
		 21000	=> '/^21$/',
		 28000	=> '/^2[89]$/',
		 50000	=> '/^5[0-4]$/',
		144000	=> '/^14[45]$/',
		430000	=> '/^43[0-9]$/');

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
				$sumData->status = STATUS_ACCEPTED;
				$summary = null;

			} else if ($summary !== null) {
				// サマリーの途中だったら
				$summary .= $line;
			}
		}
		fclose($fp);

		return $sumData;
	}
}
