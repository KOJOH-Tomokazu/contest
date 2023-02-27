<?php
/**
 * スコアクラス
 * @author JJ4KME
 */
class Score {

	/** サマリーＩＤ */
	private $sumid;
	/** 周波数帯 */
	private $freq;
	/** ＱＳＯ数 */
	private $numqso;
	/** 点数 */
	private $point;
	/** マルチプライヤー */
	private $multi;
	/** 得点 */
	private $score;

	/**
	 * コンストラクター
	 * @param array $source 元データ
	 */
	function __construct(array $source = null) {
		$this->numqso = 0;
		$this->point = 0;
		$this->multi = 0;
		$this->score = 0;

		if ($source !== null) {
			$this->sumid	= $source['sumid'];
			$this->freq		= $source['freq'];
			if (isset($source['numqso'])) {
				$this->numqso	= $source['numqso'];
			}
			if (isset($source['point'])) {
				$this->point	= $source['point'];
			}
			if (isset($source['multi'])) {
				$this->multi	= $source['multi'];
			}
			if (isset($source['score'])) {
				$this->score	= $source['score'];
			}
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
		if ($name == 'point' || $name == 'multi') {
			// 点数かマルチプライヤーが更新されたら得点を計算する
			$this->score = $this->point * $this->multi;
		}
	}

	/**
	 * スコアを計算
	 */
	public function calcScore() {

		$this->score = $this->point * $this->multi;
	}

	/**
	 * クラスの内容を配列で返す
	 * @return array プロパティ名⇒プロパティ値
	 */
	public function toArray() {

		return array(
			'sumid'		=> $this->sumid,
			'freq'		=> $this->freq,
			'numqso'	=> $this->numqso,
			'point'		=> $this->point,
			'multi'		=> $this->multi,
			'score'		=> $this->score);
	}

	/**
	 * 指定したサマリーＩＤのスコア情報を取得
	 * @param PDO $db ＤＢ接続
	 * @param unknown $sumid サマリーＩＤ
	 * @return Score[] スコア情報の配列
	 */
	public static function get(PDO $db, $sumid) {

		$result = array();
		$SQL = <<<EOF
SELECT
	sumid,
	freq,
	numqso,
	point,
	multi,
	score
FROM
	scores
WHERE
	sumid = :sumid
ORDER BY
	freq
EOF;
		$stmt = $db->prepare($SQL);
		$stmt->execute(array(
				':sumid'	=> $sumid));

		while ($record = $stmt->fetch()) {
			$result[$record['freq']] = new Score($record);
		}

		$stmt->closeCursor();

		return $result;
	}

	/**
	 * 指定したサマリーＩＤのスコア情報を削除
	 * @param PDO $db ＤＢ接続
	 * @param unknown $sumid サマリーＩＤ
	 */
	public static function delete(PDO $db, $sumid) {

		$stmt = $db->prepare('DELETE FROM scores WHERE sumid = :sumid');
		$stmt->execute(array(
				':sumid'	=> $sumid));
	}

	/**
	 * スコア情報を登録
	 * @param PDO $db ＤＢ接続
	 * @param array $scores 新しいスコア情報
	 */
	public static function insert(PDO $db, array $scores) {

		$stmt = $db->prepare('INSERT INTO scores VALUES (:sumid, :freq, :numqso, :point, :multi, :score)');
		foreach ($scores as $score) {
			$stmt->execute(array(
					':sumid'	=> $score->sumid,
					':freq'		=> $score->freq,
					':numqso'	=> $score->numqso,
					':point'	=> $score->point,
					':multi'	=> $score->multi,
					':score'	=> $score->score));
		}
	}

	/**
	 * スコアテーブルを作る
	 * @param PDO $db ＤＢ接続
	 */
	public static function create(PDO $db) {

		$SQL = array();
		$SQL[] = <<<EOF
CREATE TABLE scores (
	sumid	CHAR(20)	NOT NULL,
	freq	INTEGER		NOT NULL,
	numqso	INTEGER,
	point	INTEGER,
	multi	INTEGER,
	score	INTEGER)
EOF;
		$SQL[] = 'ALTER TABLE scores ADD PRIMARY KEY (sumid, freq)';
		$SQL[] = "COMMENT ON TABLE scores         IS 'スコアデータ'";
		$SQL[] = "COMMENT ON COLUMN scores.sumid  IS 'サマリーＩＤ'";
		$SQL[] = "COMMENT ON COLUMN scores.freq   IS '周波数'";
		$SQL[] = "COMMENT ON COLUMN scores.numqso IS '交信数'";
		$SQL[] = "COMMENT ON COLUMN scores.point  IS '点数'";
		$SQL[] = "COMMENT ON COLUMN scores.multi  IS 'マルチプライヤー'";
		$SQL[] = "COMMENT ON COLUMN scores.score  IS '得点'";

		foreach ($SQL as $temp) {
			$db->query($temp);
		}
	}
}
