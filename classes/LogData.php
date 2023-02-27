<?php
/**
 * ログデータクラス
 * @author JJ4KME
 */
abstract class LogData {

	/** モード一覧 */
	protected $modeList;

	/** サマリーＩＤ */
	protected $sumid;
	/** ログＩＤ */
	protected $logid;
	/** 自局コールサイン */
	protected $owner;
	/** 相手局コールサイン */
	protected $callsign;
	/** 交信日時 */
	protected $datetime;
	/** 周波数 */
	protected $freq;
	/** 電波の型式 */
	protected $mode;
	/** 受信リポート */
	protected $recvrst;
	/** 受信マルチ */
	protected $recvmulti;
	/** 送信リポート */
	protected $sentrst;
	/** 送信マルチ */
	protected $sentmulti;
	/** モード区分 */
	protected $modecat;
	/** 入力者 */
	protected $input_id;
	/** 確認者 */
	protected $verify_id;
	/** 点数 */
	protected $point;
	/** 不照合理由 */
	protected $error;

	/**
	 * コンストラクター
	 */
	function __construct(PDO $db, array $source = null) {
		$this->modeList = $this->getModeList($db);

		if ($source !== null) {
			$this->sumid		= $source['sumid'];						// サマリーＩＤ
			$this->logid		= $source['logid'];						// ログＩＤ
			$this->owner		= $source['owner'];						// 自局コールサイン
			$this->callsign		= $source['callsign'];					// 相手局コールサイン
			$this->datetime		= new DateTime($source['datetime']);	// 交信日時
			$this->freq			= $source['freq'];						// 周波数
			$this->mode			= $source['mode'];						// 電波の型式
			$this->recvrst		= $source['recvrst'];					// 受信リポート
			$this->recvmulti	= $source['recvmulti'];					// 受信マルチ
			$this->sentrst		= $source['sentrst'];					// 送信リポート
			$this->sentmulti	= $source['sentmulti'];					// 送信マルチ
			$this->modecat		= $source['modecat'];					// モード区分
			$this->input_id		= $source['input_id'];					// 入力者
			$this->verify_id	= $source['verify_id'];					// 確認者
			$this->point		= $source['point'];						// 点数
			$this->error		= $source['error'];						// 不照合理由
		}
	}

	/**
	 * ゲッター
	 * @param unknown $name プロパティ名
	 * @return unknown 値
	 */
	function __get($name) {
		if ($name == 'duplicateKey') {
			return "{$this->freq}_{$this->callsign}_{$this->modecat}";

		} else {
			return $this->{$name};
		}
	}

	/**
	 * セッター
	 * @param unknown $name プロパティ名
	 * @param unknown $value 値
	 */
	function __set($name, $value) {
		$this->{$name} = $value;
	}

	public function toArray() {

		return array (
				'sumid'		=> $this->sumid,							// サマリーＩＤ
				'logid'		=> $this->logid,							// ログＩＤ
				'owner'		=> $this->owner,							// 自局コールサイン
				'callsign'	=> $this->callsign,							// 相手局コールサイン
				'workdate'	=> $this->datetime->format('Y/m/d'),		// 交信日付
				'worktime'	=> $this->datetime->format('H:i'),			// 交信時刻
				'datetime'	=> $this->datetime->format('Y-m-d H:i'),	// 交信日時
				'freq'		=> $this->freq,								// 周波数
				'mode'		=> $this->mode,								// 電波の型式
				'recvrst'	=> $this->recvrst,							// 受信リポート
				'recvmulti'	=> $this->recvmulti,						// 受信マルチ
				'sentrst'	=> $this->sentrst,							// 送信リポート
				'sentmulti'	=> $this->sentmulti,						// 送信マルチ
				'modecat'	=> $this->modecat,							// モード区分
				'input_id'	=> $this->input_id,							// 入力者
				'verify_id'	=> $this->verify_id,						// 確認者
				'point'		=> $this->point,							// 得点
				'error'		=> $this->error);							// 不照合理由
	}

	public function initialize() {
		$this->owner		= null;
		$this->callsign		= null;
		$this->datetime		= null;
		$this->freq			= null;
		$this->mode			= null;
		$this->recvrst		= null;
		$this->recvmulti	= null;
		$this->sentrst		= null;
		$this->sentmulti	= null;
		$this->modecat		= null;
		$this->input_id		= null;
		$this->verify_id	= null;
	}

	private function getModeList(PDO $db) {

		$result = array();
		$stmt = $db->prepare('SELECT mode, category FROM m_modecat');
		$stmt->execute();

		while ($record = $stmt->fetch()) {
			$result[$record['mode']] = $record['category'];
		}

		$stmt->closeCursor();

		return $result;
	}

	/**
	 * レコードを登録する
	 * @param PDO $db ＤＢ接続
	 */
	public function register(PDO $db) {

		$stmt = $db->prepare('INSERT INTO logdata (
 sumid,  logid,  owner,  callsign,  datetime,  freq,  mode,  recvrst,  recvmulti,  sentrst,  sentmulti,  modecat,  input_id,  verify_id) VALUES (
:sumid, :logid, :owner, :callsign, :datetime, :freq, :mode, :recvrst, :recvmulti, :sentrst, :sentmulti, :modecat, :input_id, :verify_id)');
		$stmt->bindValue(':sumid',		$this->sumid);
		$stmt->bindValue(':logid',		$this->logid);
		$stmt->bindValue(':owner',		$this->owner);
		$stmt->bindValue(':callsign',	$this->callsign);
		$stmt->bindValue(':datetime',	$this->datetime->format('Y-m-d H:i:sO'));
		$stmt->bindValue(':freq',		$this->freq);
		$stmt->bindValue(':mode',		$this->mode);
		$stmt->bindValue(':recvrst',	$this->recvrst);
		$stmt->bindValue(':recvmulti',	$this->recvmulti);
		$stmt->bindValue(':sentrst',	$this->sentrst);
		$stmt->bindValue(':sentmulti',	$this->sentmulti);
		$stmt->bindValue(':modecat',	$this->modecat);
		$stmt->bindValue(':input_id',	$this->input_id);
		$stmt->bindValue(':verify_id',	$this->verify_id);

		$stmt->execute();
	}

	/**
	 * レコードを更新する
	 * @param PDO $db ＤＢ接続
	 * @return number 更新された件数
	 */
	public function update(PDO $db) {

		$stmt = $db->prepare('UPDATE logdata SET
	owner		= :owner,
	callsign	= :callsign,
	datetime	= :datetime,
	freq		= :freq,
	mode		= :mode,
	recvrst		= :recvrst,
	recvmulti	= :recvmulti,
	sentrst		= :sentrst,
	sentmulti	= :sentmulti,
	modecat		= :modecat,
	point		= :point,
	error		= :error,
	input_id	= :input_id,
	verify_id	= :verify_id
WHERE sumid = :sumid AND logid = :logid');
		$stmt->bindValue(':sumid',		$this->sumid);
		$stmt->bindValue(':logid',		$this->logid);
		$stmt->bindValue(':owner',		$this->owner);
		$stmt->bindValue(':callsign',	$this->callsign);
		$stmt->bindValue(':datetime',	$this->datetime->format('Y-m-d H:i:sO'));
		$stmt->bindValue(':freq',		$this->freq);
		$stmt->bindValue(':mode',		$this->mode);
		$stmt->bindValue(':recvrst',	$this->recvrst);
		$stmt->bindValue(':recvmulti',	$this->recvmulti);
		$stmt->bindValue(':sentrst',	$this->sentrst);
		$stmt->bindValue(':sentmulti',	$this->sentmulti);
		$stmt->bindValue(':modecat',	$this->modecat);
		$stmt->bindValue(':point',		$this->point);
		$stmt->bindValue(':error',		$this->error);
		$stmt->bindValue(':input_id',	$this->input_id);
		$stmt->bindValue(':verify_id',	$this->verify_id);

		$stmt->execute();

		return $stmt->rowCount();
	}

	/**
	 * ログの形式をチェックする
	 * @param unknown $path 元ファイルのパス
	 */
	public static function checkLogType($path) {

		$result = 'Others';

		$fp = fopen($path, 'r');
		while (!feof($fp)) {
			$line = fgets($fp);
			$line = trim($line);
			$line = preg_replace('/\n$/', '', $line);
			$line = mb_convert_encoding($line, 'UTF-8', 'SJIS-win');

			if (preg_match('/^<LOGSHEET\s+TYPE=CTESTWIN>/', $line) || preg_match('/^<LOGSHEET\s+TYPE="logsheetform">/', $line)) {
				$result = 'CTESTWIN';
				break;

			} else if (preg_match('/^<LOGSHEET\s+TYPE=ZLOG>/', $line)) {
				$result = 'ZLOG';
				break;

			} else if (preg_match('/^<LOGSHEET\s+TYPE=ZLOG\.ALL>/', $line)) {
				$result = 'ZLOGALL';
				break;

			} else if (preg_match('/^<LOGSHEET\s+TYPE=(")?HLTST7.+(")?>/', $line)) {
				$result = 'HLTEST7';
				break;

			} else if (preg_match('/^<LOGSHEET\s+TYPE=(")?HLTST8.+(")?>/', $line)) {
				$result = 'HLTEST8';
				break;

			} else if (preg_match('/^<LOGSHEET\s+TYPE=RTCL>/', $line)) {
				$result = 'RTCL';
				break;
			}
		}
		fclose($fp);

		return $result;
	}

	public static function correctRst($source) {

		return preg_replace('/^([1-5][1-9]([1-9])?).*/', '${1}', $source);
	}

	public static function correctMulti($source) {

		return preg_replace('/^([\da-zA-z]+).*/', '${1}', $source);
	}

	protected static function readSummary($source) {

		$result = new Summary();

		if (preg_match('/<CATEGORYCODE>(.+)<\/CATEGORYCODE>/', $source, $temp)) {
			// カテゴリーコードだったら
			$result->category = $temp[1];
		}

		if (preg_match('/<CALLSIGN>(.+)<\/CALLSIGN>/', $source, $temp)) {
			// コールサインだったら
			$result->callsign = strtoupper(mb_convert_kana($temp[1], 'a'));
		}

		if (preg_match('/<NAME>(.+)<\/NAME>/', $source, $temp)) {
			// 氏名だったら
			$result->name = $temp[1];
		}

		if (preg_match('/<ADDRESS>(.+)<\/ADDRESS>/', $source, $temp)) {
			// 住所だったら
			$result->address = $temp[1];
		}

		if (preg_match('/<EMAIL>(.+)<\/EMAIL>/', $source, $temp)) {
			// メールアドレスだったら
			$result->email = $temp[1];
		}

		if (preg_match('/<MULTIOPLIST>(.+)<\/MULTIOPLIST>/', $source, $temp)) {
			// 運用者リストだったら
			$result->multioplist = $temp[1];
		}

		if (preg_match('/<COMMENTS>(.+)<\/COMMENTS>/', $source, $temp)) {
			// コメントだったら
			$result->comments = $temp[1];
		}

		if (preg_match('/<REGCLUBNUMBER>(.+)<\/REGCLUBNUMBER>/', $source, $temp)) {
			// 登録クラブ番号だったら
			$result->regclubnumber = $temp[1];
		}

		return $result;
	}

	/**
	 * 指定されたファイルを読み込む
	 * @param PDO $db ＤＢ接続
	 * @param unknown $path 元ファイルのパス
	 */
	abstract static function readFile(PDO $db, $path);

	/**
	 * ログデータテーブルを作る
	 * @param PDO $db ＤＢ接続
	 */
	public static function create(PDO $db) {

		$SQL = array();
		$SQL[] = <<<EOF
CREATE TABLE logdata (
	sumid		CHARACTER(20)			NOT NULL,
	logid		INTEGER					NOT NULL,
	owner		CHARACTER VARYING(15)	NOT NULL,
	callsign	CHARACTER VARYING(15)	NOT NULL,
	datetime	TIMESTAMPTZ				NOT NULL,
	freq		INTEGER					NOT NULL,
	mode		CHARACTER VARYING(6)	NOT NULL,
	recvrst		CHARACTER VARYING(3)	NOT NULL,
	recvmulti	CHARACTER VARYING(10)	NOT NULL,
	sentrst		CHARACTER VARYING(3)	NOT NULL,
	sentmulti	CHARACTER VARYING(10)	NOT NULL,
	modecat		CHARACTER(1)			NOT NULL,
	input_id	CHARACTER VARYING(6),
	verify_id	CHARACTER VARYING(6),
	point		SMALLINT,
	error		CHARACTER(2))
EOF;
		$SQL[] = 'ALTER TABLE logdata ADD PRIMARY KEY (sumid, logid)';
		$SQL[] = "COMMENT ON TABLE logdata IS 'ログデータ'";
		$SQL[] = "COMMENT ON COLUMN logdata.sumid		IS 'サマリーＩＤ'";
		$SQL[] = "COMMENT ON COLUMN logdata.logid		IS 'ログＩＤ'";
		$SQL[] = "COMMENT ON COLUMN logdata.owner		IS '自局コールサイン'";
		$SQL[] = "COMMENT ON COLUMN logdata.callsign	IS '相手局コールサイン'";
		$SQL[] = "COMMENT ON COLUMN logdata.datetime	IS '交信日時'";
		$SQL[] = "COMMENT ON COLUMN logdata.freq		IS '周波数'";
		$SQL[] = "COMMENT ON COLUMN logdata.mode		IS '電波の型式'";
		$SQL[] = "COMMENT ON COLUMN logdata.recvrst		IS '受信リポート'";
		$SQL[] = "COMMENT ON COLUMN logdata.recvmulti	IS '受信マルチ'";
		$SQL[] = "COMMENT ON COLUMN logdata.sentrst		IS '送信リポート'";
		$SQL[] = "COMMENT ON COLUMN logdata.sentmulti	IS '送信マルチ'";
		$SQL[] = "COMMENT ON COLUMN logdata.modecat		IS 'モード区分'";
		$SQL[] = "COMMENT ON COLUMN logdata.input_id	IS '入力者'";
		$SQL[] = "COMMENT ON COLUMN logdata.verify_id	IS '確認者'";
		$SQL[] = "COMMENT ON COLUMN logdata.point		IS '点数'";
		$SQL[] = "COMMENT ON COLUMN logdata.error		IS 'エラー理由'";

		foreach ($SQL as $temp) {
			$db->query($temp);
		}
	}
}
