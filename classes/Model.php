<?php
/**
 * モデル抽象クラス
 * @author JJ4KME
 */
abstract class Model {

	/**
	 * コンストラクター
	 * @param array $source 元データ
	 */
	abstract function __construct(array $source = NULL);

	/**
	 * ゲッター
	 * @param unknown $name プロパティ名
	 * @return unknown プロパティ値
	 */
	abstract public function __get($name);

	/**
	 * セッター
	 * @param unknown $name プロパティ名
	 * @param unknown $value プロパティ値
	 */
	abstract public function __set($name, $value);

	/**
	 * 値がセットされているか調べる
	 * @param unknown $name プロパティ名
	 * @return boolean セットされていたらtrue、されていなかったらfalse
	 */
	abstract public function __isset($name);

	/**
	 * レコードを更新
	 * @param PDO $db ＤＢ接続
	 * @return number 更新された件数
	 */
	abstract public function update(PDO $db);

	/**
	 * レコードを挿入
	 * @param PDO $db ＤＢ接続
	 */
	abstract public function insert(PDO $db);

	/**
	 * クラスの内容を配列で返す
	 * @return array プロパティ名⇒プロパティ値
	 */
	abstract public function toArray();

	/**
	 * レコードを取得
	 * @param PDO $db ＤＢ接続
	 */
	abstract public static function get(PDO $db);

	/**
	 * レコードを削除
	 * @param PDO $db ＤＢ接続
	 */
	abstract public static function delete(PDO $db);

	/**
	 * レコードを登録
	 * @param PDO $db ＤＢ接続
	 */
	public function register(PDO $db) {

		if ($this->update($db) == 0) {
			$this->insert($db);
		}
 	}

}
