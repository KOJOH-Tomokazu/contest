<?php
include_once 'Model.php';

/**
 * 管理者クラス
 * @author JJ4KME
 */
class Administrator extends Model {

	/** ユーザーＩＤ */
	private $user_id;
	/** パスワード */
	private $password;
	/** 全体管理者フラグ */
	private $global_admin;
	/** 最終更新日時 */
	private $lastupdate;
	/** 最終ログイン日時 */
	private $lastlogin;
	/** ログイン権限 */
	private $schema_names;

	/**
	 * コンストラクター
	 * @param array $source 元データ
	 */
	public function __construct(array $source = null) {
		if ($source !== null) {
			$this->user_id		= $source['user_id'];
			$this->password		= $source['password'];
			$this->global_admin	= boolval($source['global_admin']);
			$this->lastupdate	= empty($source['lastupdate'])	? NULL	: new DateTime($source['lastupdate']);
			$this->lastlogin	= empty($source['lastlogin'])	? NULL	: new DateTime($source['lastlogin']);
		}
	}

	/**
	 * {@inheritDoc}
	 * @see Model::__get()
	 */
	function __get($name) {

		return $this->{$name};
	}

	/**
	 * {@inheritDoc}
	 * @see Model::__set()
	 */
	function __set($name, $value) {

		if ($name == 'lastupdate' || $name == 'lastlogin') {
			if ($value instanceof DateTime) {
				$this->{$name} = $value;

			} else if (empty($value)) {
				$this->{$name} = NULL;

			} else {
				$this->{$name} = new DateTime($value);
			}

		} else if ($name == 'password') {
			$this->{$name} = password_hash($value, PASSWORD_BCRYPT);

		} else {
			$this->{$name} = $value;
		}
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
				'user_id'		=> $this->user_id,
				'global_admin'	=> boolval($this->global_admin),
				'lastupdate'	=> empty($this->lastupdate)	? NULL	: $this->lastupdate->format('Y/m/d H:i:s'),
				'lastlogin'		=> empty($this->lastlogin)	? NULL	: $this->lastlogin->format('Y/m/d H:i:s'),
				'schema_names'	=> $this->schema_names);
	}

	/**
	 * 管理者情報を更新する
	 * @param PDO $db ＤＢ接続
	 * @return number 更新された件数
	 */
	public function update(PDO $db) {

		$SQL = <<<EOF
UPDATE common.m_administrators
SET	password		= :password,
	global_admin	= :global_admin,
	lastlogin		= :lastlogin,
	lastupdate		= current_timestamp
WHERE
	user_id			= :user_id
EOF;

		$stmt = $db->prepare($SQL);
		$stmt->execute(array(
				':user_id'		=> $this->user_id,
				':password'		=> $this->password,
				':global_admin'	=> boolval($this->global_admin)	? 'TRUE'	: 'FALSE',
				':lastlogin'	=> empty($this->lastlogin)		? NULL		: $this->lastlogin->format('Y/m/d H:i:s')));

		return $stmt->rowCount();
	}

 	/**
 	 * 管理者情報を追加する
 	 * @param PDO $db ＤＢ接続
 	 */
	public function insert(PDO $db) {

		$SQL = <<<EOF
INSERT INTO common.m_administrators (
	user_id,
	password,
	global_admin,
	lastupdate)
VALUES (
	:user_id,
	:password,
	:global_admin,,
	current_timestamp)
EOF;

		$stmt = $db->prepare($SQL);
		$stmt->execute(array(
				':user_id'		=> $this->user_id,
				':password'		=> $this->password,
				':global_admin'	=> boolval($this->global_admin)	? 'TRUE'	: 'FALSE'));
	}

	/**
	 * ログイン対象かつパスワードが合致するか調べる
	 * @param string $password パスワード
	 * @param string $schema ログイン先スキーマ
	 * @return boolean 合致したらtrue、しなかったらfalse
	 */
	public function verify(string $password, string $schema) {

		return password_verify($password, $this->password) && ($this->global_admin || in_array($schema, $this->schema_names));
	}

	/**
	 * 管理者の一覧を取得
	 * @param PDO $db ＤＢ接続
	 * @param unknown $user_id ユーザーＩＤ
	 * @return NULL|Administrators 管理者情報の配列
	 */
	public static function get(PDO $db, $user_id = NULL) {

		$SQL = <<<EOF
SELECT
	MAD.user_id,
	MAD.password,
	MAD.global_admin,
	MAD.lastupdate,
	MAD.lastlogin,
	MAT.schema_name
FROM
	common.m_administrators MAD
	LEFT JOIN common.m_authority MAT
		ON	MAT.user_id	= MAD.user_id
EOF;
		$params = array();

		if ($user_id !== NULL) {
			$SQL .= ' WHERE MAD.user_id = :user_id';
			$params[':user_id'] = $user_id;
		}
		$SQL .= ' ORDER BY MAD.user_id, MAT.schema_name';

		$stmt = $db->prepare($SQL);
		$stmt->execute($params);

		$result = array();
		$schema_names = array();
		while ($record = $stmt->fetch()) {
			if (!isset($result[$record['user_id']])) {
				$result[$record['user_id']] = new Administrator($record);
			}
			$schema_names[$record['user_id']][] = $record['schema_name'];
		}
		$stmt->closeCursor();

		foreach ($schema_names as $user_id => $names) {
			$result[$user_id]->schema_names = $names;
		}

		return array_values($result);
	}

	/**
	 * 指定したコールサインの管理者を削除
	 * @param PDO $db ＤＢ接続
	 * @param unknown $user_id ユーザーＩＤ
	 */
	public static function delete(PDO $db, $user_id = NULL) {

		$SQL = <<<EOF
DELETE FROM common.m_administrators
WHERE
	user_id = :user_id
EOF;
		$params = array(
				':user_id'	=> $user_id);

		$stmt = $db->prepare($SQL);
		$stmt->execute($params);
	}
}
