<?php
/**
 * スキーマクラス
 * @author JJ4KME
 */
class Schemas {

	/** スキーマ名 */
	private $schema_name;
	/** コンテスト名 */
	private $description;
	/** 使用中フラグ */
	private $used;

	/**
	 * コンストラクター
	 * @param array $source 元データ
	 */
	public function __construct(array $source = NULL) {
		if ($source !== NULL) {
			$this->schema_name	= $source['schema_name'];
			$this->description	= $source['description'];
			$this->used			= boolval($source['used']);
		}
	}

	public function __get($name) {

		return $this->{$name};
	}

	public function __set($name, $value) {

		$this->{$name} = $value;
	}

	public function toArray() {

		return array(
				'schema_name'	=> $this->schema_name,
				'description'	=> $this->description,
				'used'			=> $this->used);
	}

	public static function create(PDO $db, string $schema_name, string $description) {

		$db->query("CREATE SCHEMA {$schema_name}");
		$db->query("COMMENT ON SCHEMA {$schema_name} IS '{$description}'");
	}

	public static function insert(PDO $db, array $records) {

		$stmt = $db->prepare('INSERT INTO common.m_schemas VALUES (:schema_name, :description, TRUE)');

		foreach ($records as $record) {
			$stmt->bindValue(':schema_name',	$record->schema_name);
			$stmt->bindValue(':description',	$record->description);
			$stmt->execute();
		}
	}

	public static function get(PDO $db, $schema_name = NULL) {

		$params = array();
		$SQL = <<<EOF
SELECT
	schema_name,
	description,
	used
FROM
	common.m_schemas
EOF;
		if ($schema_name !== NULL) {
			$SQL .= ' WHERE schema_name = :schema_name';
			$params[':schema_name'] = $schema_name;
		}
		$SQL .= ' ORDER BY schema_name';

		error_log(preg_replace('/\s+/', ' ', $SQL));
		error_log(preg_replace('/\s+/', ' ', print_r($params, TRUE)));
		$stmt = $db->prepare($SQL);
		$stmt->execute($params);

		$result = array();
		while ($record = $stmt->fetch()) {
			$result[] = new Schemas($record);
		}
		$stmt->closeCursor();

		return $result;
	}
}
