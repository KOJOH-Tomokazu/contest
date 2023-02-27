<?php
/**
 * 周波数帯クラス
 * @author JJ4KME
 */
class Band {

	/** カテゴリー */
	private $category;
	/** 周波数帯 */
	private $freq;

	/**
	 * コンストラクター
	 * @param array $source 元データ
	 */
	function __construct(array $source = null) {
		if ($source != null) {
			$this->category	= $source['category'];
			$this->freq		= $source['freq'];
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
				'category'	=> $this->category,
				'freq'		=> $this->freq);
	}

	/**
	 * 指定したカテゴリーの周波数帯を取得
	 * @param PDO $db ＤＢ接続
	 * @param unknown $category カテゴリー
	 * @return Band[] 周波数帯の配列
	 */
	public static function get(PDO $db, $category) {

		$result = array();
		$SQL = <<<EOF
SELECT
	category,
	freq
FROM
	m_bands
WHERE
	category = :category
ORDER BY
	freq
EOF;
		$stmt = $db->prepare($SQL);
		$stmt->bindValue(':category', $category);
		$stmt->execute();

		while ($record = $stmt->fetch()) {
			$result[] = new Band($record);
		}

		$stmt->closeCursor();

		return $result;
	}

	/**
	 * 周波数帯情報をクリア
	 * @param PDO $db ＤＢ接続
	 */
	public static function delete(PDO $db) {

		$stmt = $db->prepare('DELETE FROM m_bands');
		$stmt->execute();
	}

	/**
	 * 周波数帯情報を登録
	 * @param PDO $db ＤＢ接続
	 * @param array $bands 新しい周波数帯情報
	 */
	public static function insert(PDO $db, array $bands) {

		$stmt = $db->prepare('INSERT INTO m_bands (category, freq) VALUES (:category, :freq)');

		foreach ($bands as $band) {
			$stmt->bindValue(':category',	$band->category);
			$stmt->bindValue(':freq',		$band->freq);
			$stmt->execute();
		}
	}

	/**
	 * 周波数帯マスターテーブルを作る
	 * @param PDO $db ＤＢ接続
	 */
	public static function create(PDO $db) {

		$SQL = array();
		$SQL[] = <<<EOF
CREATE TABLE m_bands (
	category	CHARACTER VARYING(5)	NOT NULL,
	freq		INTEGER					NOT NULL)
EOF;
		$SQL[] = "COMMENT ON TABLE m_bands				IS '周波数帯マスター'";
		$SQL[] = "COMMENT ON COLUMN m_bands.category	IS 'カテゴリーコード'";
		$SQL[] = "COMMENT ON COLUMN m_bands.freq		IS '周波数'";

		foreach ($SQL as $temp) {
			$db->query($temp);
		}
	}
}
