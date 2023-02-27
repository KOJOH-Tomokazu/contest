<?php
/**
 * 登録クラブクラス
 * @author JJ4KME
 */
class RegClub {

	/** 番号 */
	private $number;
	/** 名称 */
	private $name;

	/**
	 * コンストラクター
	 * @param array $source 元データ
	 */
	function __construct(array $source = null) {
		if ($source !== null) {
			$this->number	= $source['number'];
			$this->name		= $source['name'];
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
	function toArray() {

		return array(
			'number'	=> $this->number,
			'name'		=> $this->name);
	}

	/**
	 * 登録クラブ情報を取得
	 * @param PDO $db ＤＢ接続
	 * @return RegClub[] 登録クラブ情報の配列
	 */
	public static function get(PDO $db) {

		$result = array();
		$SQL = <<<EOF
SELECT
	number,
	name
FROM
	common.m_clubs
ORDER BY
	number
EOF;
		$stmt = $db->prepare($SQL);
		$stmt->execute();

		while ($record = $stmt->fetch()) {
			$result[] = new RegClub($record);
		}

		$stmt->closeCursor();

		return $result;
	}

	/**
	 * 登録クラブ情報をクリア
	 * @param PDO $db ＤＢ接続
	 */
	public static function delete(PDO $db) {

		$stmt = $db->prepare('DELETE FROM common.m_clubs');
		$stmt->execute();
	}

	/**
	 * 登録クラブ情報を登録
	 * @param PDO $db ＤＢ接続
	 * @param array $clubs 新しい登録クラブ情報
	 */
	public static function insert(PDO $db, array $clubs) {

		$stmt = $db->prepare('INSERT INTO common.m_clubs (number, name) VALUES (:number, :name)');
		foreach ($clubs as $club) {
			$stmt->bindValue(':number',	$club->number);
			$stmt->bindValue(':name',	$club->name);
			$stmt->execute();
		}
	}
}
