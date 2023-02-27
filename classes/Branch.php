<?php
/**
 * 支部クラス
 * @author JJ4KME
 */
class Branch extends Model {

	/** 支部コード */
	private $code;
	/** 支部名 */
	private $name;

	/**
	 * {@inheritDoc}
	 * @see Model::__construct()
	 */
	public function __construct(array $source = NULL) {
		if ($source !== NULL) {
			$this->code	= $source['code'];
			$this->name	= $source['name'];
		}
	}

	/**
	 * {@inheritDoc}
	 * @see Model::__get()
	 */
	public function __get($name) {

		return $this->{$name};
	}

	/**
	 * {@inheritDoc}
	 * @see Model::__set()
	 */
	public function __set($name, $value) {

		$this->{$name} = $value;
	}

	/**
	 * {@inheritDoc}
	 * @see Model::__isset()
	 */
	public function __isset($name) {

		return isset($this->{$name});
	}

	/**
	 * {@inheritDoc}
	 * @see Model::toArray()
	 */
	public function toArray() {

		return array(
				'code'	=> $this->code,
				'name'	=> $this->name);
	}

	/**
	 * {@inheritDoc}
	 * @see Model::update()
	 */
	public function update(PDO $db) {

		$SQL = <<<EOF
UPDATE common._branches SET
	name	= :name
WHERE
	code	= :code
EOF;
		$params = array(
				':code'	=> $this->code,
				':name'	=> $this->name);

		error_log(preg_replace('/\s+/', ' ', $SQL));
		error_log(preg_replace('/\s+/', ' ', print_r($params, TRUE)));
		$stmt = $db->prepare($SQL);
		$stmt->execute($params);

		return $stmt->rowCount();
	}

	/**
	 * {@inheritDoc}
	 * @see Model::insert()
	 */
	public function insert(PDO $db) {

		$SQL = <<<EOF
INSERT INTO common.m_branches (code,  name)
               VALUES (:code, :name)
EOF;
		$params = array(
				':code'	=> $this->code,
				':name'	=> $this->name);

		error_log(preg_replace('/\s+/', ' ', $SQL));
		error_log(preg_replace('/\s+/', ' ', print_r($params, TRUE)));
		$stmt = $db->prepare($SQL);
		$stmt->execute($params);
	}

	/**
	 * {@inheritDoc}
	 * @see Model::get()
	 * @param string $code 支部コード
	 */
	public static function get(PDO $db, string $code = NULL) {

		$SQL = <<<EOF
SELECT
	code,
	name
FROM
	common.m_branches
EOF;
		$params = array();
		if ($code !== NULL) {
			$SQL .= ' WHERE code = :code';
			$params[':code'] = $code;
		}
		$SQL .= ' ORDER BY code';

		error_log(preg_replace('/\s+/', ' ', $SQL));
		error_log(preg_replace('/\s+/', ' ', print_r($params, TRUE)));
		$stmt = $db->prepare($SQL);
		$stmt->execute($params);

		$result = array();
		while ($record = $stmt->fetch()) {
			$result[] = new Branch($record);
		}

		return $result;
	}

	/**
	 * {@inheritDoc}
	 * @see Model::delete()
	 * @param string $code 支部コード
	 */
	public static function delete(PDO $db, string $code = NULL) {

		$SQL = 'DELETE FROM common.m_branches';
		$params = array();
		if ($code !== NULL) {
			$SQL .= ' WHERE code = :code';
			$params[':code'] = $code;
		}

		error_log(preg_replace('/\s+/', ' ', $SQL));
		error_log(preg_replace('/\s+/', ' ', print_r($params, TRUE)));
		$stmt = $db->prepare($SQL);
		$stmt->execute($params);
	}
}
