<?php
include_once 'LogData.php';
include_once 'Cabrillo.php';

/**
 * サマリークラス
 * @author JJ4KME
 */
final class Summary {

	/** サマリーＩＤ */
	private $sumid;
	/** 参加部門 */
	private $category;
	/** コールサイン */
	private $callsign;
	/** 氏名 */
	private $name;
	/** 住所 */
	private $address;
	/** メールアドレス */
	private $email;
	/** コメント */
	private $comments;
	/** 運用者リスト */
	private $multioplist;
	/** 元ファイル名 */
	private $filename;
	/** 処理ステータス */
	private $status;
	/** 登録クラブ番号 */
	private $regclubnumber;
	/** パスワード */
	private $password;
	/** 登録日時 */
	private $uploadtime;
	/** ログデータ */
	private $logData;

	/**
	 * コンストラクター
	 * @param array $source 元データ
	 */
	function __construct(array $source = null) {
		$this->logData = array();

		if ($source !== null) {
			$this->sumid			= $source['sumid'];						// サマリーＩＤ
			$this->category			= $source['category'];					// 参加部門
			$this->callsign			= $source['callsign'];					// コールサイン
			$this->name				= $source['name'];						// 氏名
			$this->address			= $source['address'];					// 住所
			$this->email			= $source['email'];						// メールアドレス
			$this->comments			= $source['comments'];					// コメント
			$this->multioplist		= $source['multioplist'];				// 運用者リスト
			$this->filename			= $source['filename'];					// 元ファイル名
			$this->status			= $source['status'];					// 処理ステータス
			$this->regclubnumber	= $source['regclubnumber'];				// 登録クラブ番号
			$this->password			= $source['password'];					// パスワード
			$this->uploadtime		= new DateTime($source['uploadtime']);	// 登録日時
		}
	}

	/**
	 * ゲッター
	 * @param unknown $name プロパティ名
	 * @return unknown 値
	 */
	function __get($name) {

		return $this->{$name};
	}

	/**
	 * セッター
	 * @param unknown $name プロパティ名
	 * @param unknown $value 値
	 */
	function __set($name, $value) {

		if ($name == 'sumid' || $name == 'input_id' || $name == 'verify_id') {
			$this->{$name} = $value;
			foreach ($this->logData as $logData) {
				$logData->{$name} = $value;
			}

		} else {
			$this->{$name} = $value;
		}
	}

	/**
	 * ログデータを追加する
	 * @param LogData $logData ログデータ
	 */
	public function addLog(LogData $logData) {

		$logData->sumid = $this->sumid;
		$this->logData[] = $logData;
	}

	public function toArray() {

		$logData = array();
		foreach ($this->logData as $log) {
			$logData[] = $log->toArray();
		}

		return array(
			'sumid'			=> $this->sumid,
			'category'		=> $this->category,
			'callsign'		=> $this->callsign,
			'name'			=> $this->name,
			'address'		=> $this->address,
			'email'			=> $this->email,
			'comments'		=> $this->comments,
			'multioplist'	=> $this->multioplist,
			'filename'		=> $this->filename,
			'status'		=> $this->status,
			'regclubnumber'	=> $this->regclubnumber,
			'password'		=> $this->password,
			'uploadtime'	=> $this->uploadtime,
			'logData'		=> $logData);
	}

	/**
	 * レコードを登録する
	 * @return number 成功したら０
	 */
	public function register(PDO $db) {

		$stmt = $db->prepare('INSERT INTO summary (
 sumid,  category,  callsign,  name,  email,  address,  comments,  multioplist,  regclubnumber,  password,  filename,  status, uploadtime) VALUES (
:sumid, :category, :callsign, :name, :email, :address, :comments, :multioplist, :regclubnumber, :password, :filename, :status, current_timestamp)');
		$stmt->bindValue(':sumid',			$this->sumid);
		$stmt->bindValue(':category',		$this->category);
		$stmt->bindValue(':callsign',		$this->callsign);
		$stmt->bindValue(':name',			$this->name);
		$stmt->bindValue(':address',		$this->address);
		$stmt->bindValue(':email',			$this->email);
		$stmt->bindValue(':comments',		$this->comments);
		$stmt->bindValue(':multioplist',	$this->multioplist);
		$stmt->bindValue(':regclubnumber',	$this->regclubnumber);
		$stmt->bindValue(':password',		$this->password);
		$stmt->bindValue(':filename',		$this->filename);
		$stmt->bindValue(':status',			$this->status);
		$stmt->execute();

		$logid = 1;
		foreach ($this->logData as $logData) {
			$logData->logid = $logid++;
			$logData->register($db);
		}

		return 0;
	}

	/**
	 * レコードを更新する
	 */
	public function update(PDO $db) {

		$stmt = $db->prepare('UPDATE summary SET
	name			= :name,
	address			= :address,
	email			= :email,
	comments		= :comments,
	multioplist		= :multioplist,
	filename		= :filename,
	status			= :status,
	regclubnumber	= :regclubnumber,
	password		= :password
WHERE sumid = :sumid');
		$stmt->bindValue(':sumid',			$this->sumid);
		$stmt->bindValue(':name',			$this->name);
		$stmt->bindValue(':address',		$this->address);
		$stmt->bindValue(':email',			$this->email);
		$stmt->bindValue(':comments',		$this->comments);
		$stmt->bindValue(':multioplist',	$this->multioplist);
		$stmt->bindValue(':filename',		$this->filename);
		$stmt->bindValue(':status',			$this->status);
		$stmt->bindValue(':regclubnumber',	$this->regclubnumber);
		$stmt->bindValue(':password',		$this->password);
		$stmt->execute();

		foreach ($this->logData as $logData) {
			if ($logData->update($db) == 0) {
				$logData->register($db);
			}
		}
	}

	/**
	 * 指定されたコールサインのログを取得
	 * @param unknown $callsign コールサイン
	 * @return unknown[] ログ
	 */
	public function searchLog($callsign) {

		$result = array();
		foreach ($this->logData as $logData) {
			if ($logData->callsign == $callsign) {
				$result[] = $logData;
			}
		}

		return $result;
	}

	/**
	 * サマリーデータを取得
	 * @param PDO $db ＤＢ接続
	 * @param unknown $sumid サマリーＩＤ
	 * @return NULL|Summary サマリーデータ、見つからなかったらNULL
	 */
	public static function get(PDO $db, $sumid = null) {

		$stmtLog = $db->prepare('SELECT * FROM logdata WHERE sumid = :sumid ORDER BY freq, datetime');

		$params = array();
		$SQL = <<<EOF
SELECT
	sumid,
	category,
	callsign,
	name,
	address,
	email,
	comments,
	multioplist,
	filename,
	status,
	regclubnumber,
	password,
	uploadtime
FROM
	summary
EOF;

		if (is_array($sumid)) {
			// サマリーＩＤが配列だったら
			$SQL .= ' WHERE sumid IN (';
			$comma = '';
			for ($i = 0; $i < count($sumid); $i++) {
				$params[":sumid{$i}"] = $sumid[$i];
				$SQL .= "{$comma}:sumid{$i}";
				$comma = ', ';
			}
			$SQL .= ')';

		} else if ($sumid !== null) {
			// サマリーＩＤが指定されていたら
			$SQL .= ' WHERE sumid = :sumid';
			$params[':sumid'] = $sumid;
		}

		$result = null;
		$stmtSummary = $db->prepare($SQL);
		foreach ($params as $key => $value) {
			$stmtSummary->bindValue($key, $value);
		}
		$stmtSummary->execute();

		if (is_array($sumid) || $stmtSummary->rowCount() > 1) {
			// 複数件だったら
			while ($record = $stmtSummary->fetch()) {
				$temp = new Summary($record);

				// ログデータを取得
				$stmtLog->bindValue(':sumid',	$temp->sumid);
				$stmtLog->execute();

				while ($record2 = $stmtLog->fetch()) {
					$temp->addLog(new Cabrillo($db, $record2));
				}

				$result[] = $temp;
			}

		} else {
			// １件だけだったら
			$record = $stmtSummary->fetch();
			$result = new Summary($record);

			// ログデータを取得
			$stmtLog->bindValue(':sumid',	$result->sumid);
			$stmtLog->execute();

			while ($record2 = $stmtLog->fetch()) {
				$result->addLog(new Cabrillo($db, $record2));
			}
		}

		return $result;
	}

	public static function search(PDO $db, $callsign) {

		$stmtLog = $db->prepare('SELECT * FROM logdata WHERE sumid = :sumid ORDER BY freq, datetime');

		$SQL = <<<EOF
SELECT
	sumid,
	category,
	callsign,
	name,
	address,
	email,
	comments,
	multioplist,
	filename,
	status,
	regclubnumber,
	password,
	uploadtime
FROM
	summary
WHERE
	callsign = :callsign
LIMIT 1
EOF;

		$result = null;
		$stmtSummary = $db->prepare($SQL);
		$stmtSummary->bindValue(':callsign', $callsign);
		$stmtSummary->execute();

		if ($stmtSummary->rowCount() > 0) {
			$result = new Summary($stmtSummary->fetch());

			// ログデータを取得
			$stmtLog->bindValue(':sumid',	$result->sumid);
			$stmtLog->execute();

			while ($record = $stmtLog->fetch()) {
				$result->addLog(new Cabrillo($db, $record));
			}
		}

		return $result;
	}

	/**
	 * サマリーテーブルを作る
	 * @param PDO $db ＤＢ接続
	 */
	public static function create(PDO $db) {

		$SQL = array();
		$SQL[] = <<<EOF
CREATE TABLE summary (
	sumid			CHAR(20)	NOT NULL,
	category		VARCHAR(15)	NOT NULL,
	callsign		VARCHAR(15)	NOT NULL,
	name			TEXT,
	address			TEXT,
	email			VARCHAR(50),
	comments		TEXT,
	multioplist		TEXT,
	regclubnumber	VARCHAR(10),
	password		VARCHAR(255),
	filename		CHAR(24),
	status			SMALLINT,
	uploadtime		TIMESTAMPTZ)
EOF;
		$SQL[] = 'ALTER TABLE summary ADD PRIMARY KEY (sumid)';
		$SQL[] = "COMMENT ON TABLE summary                IS 'スコアデータ'";
		$SQL[] = "COMMENT ON COLUMN summary.sumid         IS 'サマリーＩＤ'";
		$SQL[] = "COMMENT ON COLUMN summary.category      IS 'カテゴリーコード'";
		$SQL[] = "COMMENT ON COLUMN summary.callsign      IS 'コールサイン'";
		$SQL[] = "COMMENT ON COLUMN summary.name          IS '氏名'";
		$SQL[] = "COMMENT ON COLUMN summary.address       IS '住所'";
		$SQL[] = "COMMENT ON COLUMN summary.email         IS 'Ｅメール'";
		$SQL[] = "COMMENT ON COLUMN summary.comments      IS 'コメント'";
		$SQL[] = "COMMENT ON COLUMN summary.multioplist   IS '運用者'";
		$SQL[] = "COMMENT ON COLUMN summary.regclubnumber IS '登録クラブ番号'";
		$SQL[] = "COMMENT ON COLUMN summary.password      IS 'パスワード'";
		$SQL[] = "COMMENT ON COLUMN summary.filename      IS 'ファイル名'";
		$SQL[] = "COMMENT ON COLUMN summary.status        IS 'ステータス'";
		$SQL[] = "COMMENT ON COLUMN summary.uploadtime    IS 'アップロード日時'";

		foreach ($SQL as $temp) {
			$db->query($temp);
		}
	}
}
