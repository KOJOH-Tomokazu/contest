<?php
/**
 * 時間帯クラス
 * @author JJ4KME
 */
class Period {

	/** 周波数帯 */
	private $freq;
	/** 開始日時 */
	private $starttime;
	/** 終了日時 */
	private $endtime;

	/**
	 * コンストラクター
	 * @param array $source 元データ
	 */
	public function __construct(array $source = null) {
		if ($source != null) {
			$this->freq			= $source['freq'];
			$this->starttime	= new DateTime($source['starttime']);
			$this->endtime		= new DateTime($source['endtime']);
		}
	}

	/**
	 * ゲッター
	 * @param unknown $name プロパティ名
	 * @return unknown プロパティ値
	 */
	function __get($name) {

		return $this->{$name};
	}

	/**
	 * セッター
	 * @param unknown $name プロパティ名
	 * @param unknown $value プロパティ値
	 */
	function __set($name, $value) {

		$this->{$name} = $value;
	}

	/**
	 * クラスの内容を配列で返す
	 * @return array プロパティ名⇒プロパティ値
	 */
	public function toArray() {

		return array(
			'freq'		=> $this->freq,
			'starttime'	=> $this->starttime->format('Y/m/d H:i'),
			'endtime'	=> $this->endtime->format('Y/m/d H:i'));
	}

	/**
	 * 時間帯情報を取得
	 * @param PDO $db ＤＢ接続
	 * @return Period[] 時間帯情報の配列
	 */
	public static function get(PDO $db) {

		$result = array();
		$SQL = <<<EOF
SELECT
	freq,
	starttime,
	endtime
FROM
	m_periods
ORDER BY
	freq
EOF;
		$stmt = $db->query($SQL);
		$stmt->execute();

		while ($record = $stmt->fetch()) {
			$result[] = new Period($record);
		}

		$stmt->closeCursor();

		return $result;
	}

	/**
	 * 時間帯情報をクリア
	 * @param PDO $db ＤＢ接続
	 */
	public static function delete(PDO $db) {

		$stmt = $db->prepare('DELETE FROM m_periods');
		$stmt->execute();
	}

	/**
	 * 時間帯情報を登録
	 * @param PDO $db ＤＢ接続
	 * @param array $bands 新しい時間帯情報
	 */
	public static function insert(PDO $db, array $periods) {

		$stmt = $db->prepare('INSERT INTO m_periods (freq, starttime, endtime) VALUES (:freq, :starttime, :endtime)');

		foreach ($periods as $period) {
			$stmt->execute(array(
					':freq'			=> $period->freq,
					':starttime'	=> $period->starttime->format('Y-m-d H:i:s'),
					':endtime'		=> $period->endtime->format('Y-m-d H:i:s')));
		}
	}

	/**
	 * 時間帯マスターテーブルを作る
	 * @param PDO $db ＤＢ接続
	 */
	public static function create(PDO $db) {

		$SQL = array();
		$SQL[] = <<<EOF
CREATE TABLE m_periods (
	freq		INTEGER		NOT NULL,
	starttime	TIMESTAMPTZ	NOT NULL,
	endtime		TIMESTAMPTZ	NOT NULL)
EOF;
		$SQL[] = 'ALTER TABLE m_periods ADD PRIMARY KEY (freq)';
		$SQL[] = "COMMENT ON TABLE m_periods            IS '時間帯マスター'";
		$SQL[] = "COMMENT ON COLUMN m_periods.freq      IS '周波数'";
		$SQL[] = "COMMENT ON COLUMN m_periods.starttime IS '開始日時'";
		$SQL[] = "COMMENT ON COLUMN m_periods.endtime   IS '終了日時'";

		foreach ($SQL as $temp) {
			$db->query($temp);
		}
	}
}
