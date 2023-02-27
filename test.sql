-- 一覧
-- UPDATE common.m_schemas SET used = NULL;
-- INSERT INTO common.m_schemas VALUES ('ja4test_04', '第４回オールＪＡ４コンテスト', TRUE);
INSERT INTO common.m_schemas VALUES('test_01', '第１回テスト', FALSE);

-- スキーマ作成・移動
CREATE SCHEMA test_01;
COMMENT ON SCHEMA ja4test_01 IS '第１回テスト';
SET search_path TO test_01;

-- ログデータ
CREATE TABLE logdata (
	sumid		CHAR(20)	NOT NULL,
	logid		INTEGER		NOT NULL,
	owner		VARCHAR(15)	NOT NULL,
	callsign	VARCHAR(15)	NOT NULL,
	datetime	TIMESTAMPTZ	NOT NULL,
	freq		INTEGER		NOT NULL,
	mode		VARCHAR(6)	NOT NULL,
	recvrst		VARCHAR(3)	NOT NULL,
	recvmulti	VARCHAR(10)	NOT NULL,
	sentrst		VARCHAR(3)	NOT NULL,
	sentmulti	VARCHAR(10)	NOT NULL,
	modecat		CHAR(1)		NOT NULL,
	input_id	VARCHAR(6),
	verify_id	VARCHAR(6),
	point		SMALLINT,
	error		CHAR(2));
ALTER TABLE logdata ADD PRIMARY KEY (sumid, logid);
COMMENT ON TABLE logdata            IS 'ログデータ';
COMMENT ON COLUMN logdata.sumid     IS 'サマリーＩＤ';
COMMENT ON COLUMN logdata.logid     IS 'ログＩＤ';
COMMENT ON COLUMN logdata.owner     IS '自局コールサイン';
COMMENT ON COLUMN logdata.callsign  IS '相手局コールサイン';
COMMENT ON COLUMN logdata.datetime  IS '交信日時';
COMMENT ON COLUMN logdata.freq      IS '周波数';
COMMENT ON COLUMN logdata.mode      IS '電波の型式';
COMMENT ON COLUMN logdata.recvrst   IS '受信リポート';
COMMENT ON COLUMN logdata.recvmulti IS '受信マルチ';
COMMENT ON COLUMN logdata.sentrst   IS '送信リポート';
COMMENT ON COLUMN logdata.sentmulti IS '送信マルチ';
COMMENT ON COLUMN logdata.modecat   IS 'モード区分';
COMMENT ON COLUMN logdata.input_id  IS '入力者';
COMMENT ON COLUMN logdata.verify_id IS '確認者';
COMMENT ON COLUMN logdata.point     IS '点数';
COMMENT ON COLUMN logdata.error     IS 'エラー理由';

-- 周波数帯
CREATE TABLE m_bands (
	category	VARCHAR(5)		NOT NULL,
	freq		INTEGER			NOT NULL);
COMMENT ON TABLE m_bands           IS '周波数帯マスター';
COMMENT ON COLUMN m_bands.category IS 'カテゴリーコード';
COMMENT ON COLUMN m_bands.freq     IS '周波数帯';
INSERT INTO m_bands SELECT category, freq FROM ja4test_03.m_bands;

-- カテゴリー
CREATE TABLE m_categories (
	code		VARCHAR(5)	NOT NULL,
	disporder	SMALLINT	NOT NULL,
	bandcat		CHAR(1)		NOT NULL,
	name		text);
CREATE TABLE m_categories_dup (
	code1		VARCHAR(5)	NOT NULL,
	code2		VARCHAR(5)	NOT NULL,
	enabled		BOOLEAN		NOT NULL);
ALTER TABLE m_categories ADD PRIMARY KEY (code);
COMMENT ON TABLE m_categories              IS '参加部門マスター';
COMMENT ON COLUMN m_categories.code        IS '部門コード';
COMMENT ON COLUMN m_categories.disporder   IS '表示順';
COMMENT ON COLUMN m_categories.bandcat     IS 'バンド区分';
COMMENT ON COLUMN m_categories.name        IS '名称';
COMMENT ON TABLE m_categories_dup          IS '同時提出可否マスター';
COMMENT ON COLUMN m_categories_dup.code1   IS '部門１';
COMMENT ON COLUMN m_categories_dup.code2   IS '部門２';
COMMENT ON COLUMN m_categories_dup.enabled IS '可否フラグ';
INSERT INTO m_categories     SELECT code, disporder, bandcat, name FROM ja4test_03.m_categories;
INSERT INTO m_categories_dup SELECT code1, code2, enabled          FROM ja4test_03.m_categories_dup;

-- モード分類
CREATE TABLE m_modecat (
	mode      VARCHAR(10) NOT NULL,
	category  CHAR(1),
	disp_name VARCHAR(10));
ALTER TABLE m_modecat ADD PRIMARY KEY (mode);
COMMENT ON TABLE m_modecat            IS 'モード分類';
COMMENT ON COLUMN m_modecat.mode      IS '電波の型式';
COMMENT ON COLUMN m_modecat.category  IS '分類';
COMMENT ON COLUMN m_modecat.disp_name IS '表示名';
INSERT INTO m_modecat SELECT mode, category, disp_name FROM ja4test_03.m_modecat;

-- マルチプライヤー
CREATE TABLE m_multipliers (
	category VARCHAR(5) NOT NULL,
	code     VARCHAR(6) NOT NULL,
	name     TEXT       NOT NULL,
	point    SMALLINT);
COMMENT ON TABLE m_multipliers           IS 'マルチプライヤーマスター';
COMMENT ON COLUMN m_multipliers.category IS '部門コード';
COMMENT ON COLUMN m_multipliers.code     IS 'マルチプライヤー';
COMMENT ON COLUMN m_multipliers.name     IS '名称';
COMMENT ON COLUMN m_multipliers.point    IS '得点';
INSERT INTO m_multipliers SELECT category, code, name, point FROM ja4test_03.m_multipliers;

-- 時間帯マスター
CREATE TABLE m_periods (
	freq		INTEGER		NOT NULL,
	starttime	TIMESTAMPTZ	NOT NULL,
	endtime		TIMESTAMPTZ	NOT NULL);
ALTER TABLE m_periods ADD PRIMARY KEY (freq);
COMMENT ON TABLE m_periods            IS '時間帯マスター';
COMMENT ON COLUMN m_periods.freq      IS '周波数';
COMMENT ON COLUMN m_periods.starttime IS '開始日時';
COMMENT ON COLUMN m_periods.endtime   IS '終了日時';

-- 全体ステータス
CREATE TABLE m_status (
	status SMALLINT NOT NULL);
COMMENT ON TABLE m_status IS '全体ステータス';
INSERT INTO m_status VALUES (0);

-- スコア
CREATE TABLE scores (
	sumid	CHAR(20)	NOT NULL,
	freq	INTEGER		NOT NULL,
	numqso	INTEGER,
	point	INTEGER,
	multi	INTEGER,
	score	INTEGER);
ALTER TABLE scores ADD PRIMARY KEY (sumid, freq);
COMMENT ON TABLE scores         IS 'スコアデータ';
COMMENT ON COLUMN scores.sumid  IS 'サマリーＩＤ';
COMMENT ON COLUMN scores.freq   IS '周波数';
COMMENT ON COLUMN scores.numqso IS '交信数';
COMMENT ON COLUMN scores.point  IS '点数';
COMMENT ON COLUMN scores.multi  IS 'マルチプライヤー';
COMMENT ON COLUMN scores.score  IS '得点';

-- サマリー
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
	uploadtime		TIMESTAMPTZ);
ALTER TABLE summary ADD PRIMARY KEY (sumid);
COMMENT ON TABLE summary                IS 'スコアデータ';
COMMENT ON COLUMN summary.sumid         IS 'サマリーＩＤ';
COMMENT ON COLUMN summary.category      IS 'カテゴリーコード';
COMMENT ON COLUMN summary.callsign      IS 'コールサイン';
COMMENT ON COLUMN summary.name          IS '氏名';
COMMENT ON COLUMN summary.address       IS '住所';
COMMENT ON COLUMN summary.email         IS 'Ｅメール';
COMMENT ON COLUMN summary.comments      IS 'コメント';
COMMENT ON COLUMN summary.multioplist   IS '運用者';
COMMENT ON COLUMN summary.regclubnumber IS '登録クラブ番号';
COMMENT ON COLUMN summary.password      IS 'パスワード';
COMMENT ON COLUMN summary.filename      IS 'ファイル名';
COMMENT ON COLUMN summary.status        IS 'ステータス';
COMMENT ON COLUMN summary.uploadtime    IS 'アップロード日時';

