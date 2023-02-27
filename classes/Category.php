<?php
include_once 'Band.php';

/**
 * カテゴリークラス
 * @author JJ4KME
 */
class Category {

	/** コード */
	private $code;
	/** 表示順 */
	private $disporder;
	/** 名称 */
	private $name;
	/** 対象周波数帯 */
	private $bands;

	/**
	 * コンストラクター
	 * @param array $source 元データ
	 */
	function __construct(array $source = NULL) {
		if ($source !== NULL) {
			$this->code			= $source['code'];
			$this->disporder	= $source['disporder'];
			$this->name			= $source['name'];
		}
	}

	/**
	 * ゲッター
	 * @param unknown $name プロパティ名
	 * @return unknown プロパティ値
	 */
	function __get($name) {
		if ($name == 'bandcat') {
			if (count($this->bands) > 1) {
				return 'M';

			} else {
				return 'S';
			}

		} else {
			return $this->{$name};
		}
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

		$bands = array();
		foreach ($this->bands as $band) {
			$bands[] = $band->toArray();
		}

		return array(
				'code'		=> $this->code,
				'disporder'	=> $this->disporder,
				'name'		=> $this->name,
				'bands'		=> $bands);
	}

	/**
	 * 指定したカテゴリーのカテゴリー情報を取得
	 * @param PDO $db ＤＢ接続
	 * @param unknown $category カテゴリー
	 * @return Category[] カテゴリー情報の配列
	 */
	public static function get(PDO $db, $category = NULL) {

		$result = array();
		$params = array();
		$SQL = <<<EOF
SELECT
	code,
	disporder,
	name
FROM
	m_categories
EOF;
		if ($category !== NULL) {
			$SQL .= ' WHERE code = :code';
			$params[':code'] = $category;
		}
		$SQL .= ' ORDER BY disporder';

		$stmt = $db->prepare($SQL);
		foreach ($params as $key => $value) {
			$stmt->bindValue($key, $value);
		}
		$stmt->execute();

		while ($record = $stmt->fetch()) {
			$category = new Category($record);
			$category->bands = Band::get($db, $category->code);

			$result[$category->disporder] = $category;
		}

		return $result;
	}

	/**
	 * カテゴリー情報をクリア
	 * @param PDO $db ＤＢ接続
	 */
	public static function delete(PDO $db) {

		$stmt = $db->prepare('DELETE FROM m_categories');
		$stmt->execute();

		Band::delete($db);
	}

	/**
	 * カテゴリー情報を登録
	 * @param PDO $db ＤＢ接続
	 * @param array $bands 新しいカテゴリー情報
	 */
	public static function insert(PDO $db, array $categories) {

		$stmt = $db->prepare('INSERT INTO m_categories (code, disporder, bandcat, name) VALUES (:code, :disporder, :bandcat, :name)');

		foreach ($categories as $category) {
			$stmt->bindValue(':code',		$category->code);
			$stmt->bindValue(':disporder',	$category->disporder);
			$stmt->bindValue(':bandcat',	$category->bandcat);
			$stmt->bindValue(':name',		$category->name);
			$stmt->execute();

			Band::insert($db, $category->bands);
		}
	}

	/**
	 * カテゴリーマスターテーブルを作る
	 * @param PDO $db ＤＢ接続
	 */
	public static function create(PDO $db) {

		$SQL = array();
		$SQL[] = <<<EOF
CREATE TABLE m_categories (
	code		CHARACTER VARYING(5)	NOT NULL,
	disporder	SMALLINT				NOT NULL,
	bandcat		CHARACTER(1)			NOT NULL,
	name		TEXT)
EOF;
		$SQL[] = <<<EOF
CREATE TABLE m_categories_dup (
	code1		CHARACTER VARYING(5)	NOT NULL,
	code2		CHARACTER VARYING(5)	NOT NULL,
	enabled		BOOLEAN					NOT NULL)
EOF;
		$SQL[] = 'ALTER TABLE m_categories ADD PRIMARY KEY (code)';
		$SQL[] = "COMMENT ON TABLE m_categories				IS 'カテゴリーマスター'";
		$SQL[] = "COMMENT ON COLUMN m_categories.code		IS 'カテゴリーコード'";
		$SQL[] = "COMMENT ON COLUMN m_categories.disporder	IS '表示順'";
		$SQL[] = "COMMENT ON COLUMN m_categories.bandcat	IS 'バンド区分'";
		$SQL[] = "COMMENT ON COLUMN m_categories.name		IS '名称'";

		$SQL[] = "COMMENT ON TABLE m_categories_dup				IS '同時提出可否マスター'";
		$SQL[] = "COMMENT ON COLUMN m_categories_dup.code1		IS 'カテゴリーコード１'";
		$SQL[] = "COMMENT ON COLUMN m_categories_dup.code2		IS 'カテゴリーコード２'";
		$SQL[] = "COMMENT ON COLUMN m_categories_dup.enabled	IS '可否フラグ'";

		foreach ($SQL as $temp) {
			$db->query($temp);
		}
	}
}
