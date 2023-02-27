<?php
include_once 'common.php';
include_once 'classes/Cabrillo.php';
include_once 'classes/Category.php';
include_once 'classes/CtestWin.php';
include_once 'classes/GlobalStatus.php';
include_once 'classes/HLTest7.php';
include_once 'classes/HLTest8.php';
include_once 'classes/Others.php';
include_once 'classes/LogData.php';
include_once 'classes/RegClub.php';
include_once 'classes/RTCL.php';
include_once 'classes/ZLog.php';
include_once 'classes/ZLogAll.php';
include_once 'libs/MyPDO.php';
include_once 'libs/PHPMailer/src/Exception.php';
include_once 'libs/PHPMailer/src/PHPMailer.php';
include_once 'libs/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

define('SMTP_HOST',	'smtp.ocn.ne.jp');
define('SMTP_PORT',	465);
define('IMAP_HOST',	'imap.ocn.ne.jp');
define('IMAP_PORT',	993);
define('SUBJECT',	'【自動返信】オールＪＡ４コンテストにご参加ありがとうございます');
define('USERNAME',	'ＪＡＲＬ中国地方本部コンテスト委員会－ログ提出用');
define('USERADDR',	'ja4test@circus.ocn.ne.jp');
define('PASSWORD',	'KSayaka0728');
define('KEYWORD',	'[JA4TEST04]');
define('SCHEMA',	'JA4TEST_03');

try {
	$db = new MyPDO('ja4test');
	$db->beginTransaction();

	if (($imap = imap_open('{'. IMAP_HOST. ':'. IMAP_PORT. '/imap/ssl}', USERADDR, PASSWORD)) === FALSE) {
		echo '接続に失敗しました';

	} else {
		if (($mails = imap_search($imap, 'UNSEEN SUBJECT "'. KEYWORD. '"')) !== FALSE) {
			$n = 0;
			foreach ($mails as $msg_no) {
				$header = parse_header(imap_header($imap, $msg_no));
				if (substr($header['subject'], 0, strlen(KEYWORD)) == KEYWORD) {
					$TempFile = TEMP_DIR. '/'. createUploadTime(date('U')). '.txt';
					file_put_contents($TempFile, get_body($imap, $msg_no));

					$logType = check_log_type($TempFile);

					echo "{$header['subject']} ⇒ {$logType}\n";

					$summary = create_summary($db, $TempFile);

					$recipient = (isset($header['reply_to']) ? $header['reply_to'] : $header['from']);
					if (send_mail($summary->callsign, $summary->name, $recipient, $header['MailDate'], $logType, count($summary->logData))) {
						echo "メール返信 ⇒ {$recipient} ⇒ OK\n";
						$n++;
					} else {
						echo "メール返信 ⇒ {$recipient} ⇒ NG\n";
						imap_setflag_full($imap, $msg_no, "\\RECENT");
					}
				}

				if ($n >= 2) {
					break;
				}
			}
		}

		imap_close($imap, CL_EXPUNGE);
	}
	$db->commit();

} catch (PDOException $pe) {
	echo $pe->getCode(). "\n";
	echo $pe->getMessage(). "\n";
	$db->rollBack();

} finally {
	$db = NULL;
}

exit(0);

function parse_header($header) {

	$result = array();
	if (isset($header->subject)) {
		$result['subject'] = '';
		$mhead = imap_mime_header_decode($header->subject);
		foreach ($mhead as $key => $value) {
			if ($value->charset == 'default') {
				$result['subject'] .= $value->text;
			} else {
				$result['subject'] .= mb_convert_encoding($value->text, 'UTF-8', $value->charset);
			}
		}
	}

	if (isset($header->from)) {
		$result['from'] = "{$header->from[0]->mailbox}@{$header->from[0]->host}";
	}

	if (isset($header->reply_to)) {
		$result['reply_to'] = "{$header->reply_to[0]->mailbox}@{$header->reply_to[0]->host}";
	}

	if (isset($header->MailDate)) {
		$result['MailDate'] = new DateTime($header->MailDate);
	}

	return $result;
}

function get_body($imap, $msg_no) {

	$body = imap_fetchbody($imap, $msg_no, 1, FT_INTERNAL);
	$s = imap_fetchstructure($imap, $msg_no);

	if (isset($s->parts)) {
		$charset = $s->parts[0]->parameters[0]->value;
		$encoding = $s->parts[0]->encoding;

	} else {
		$charset = $s->parameters[0]->value;
		$encoding = $s->encoding;
	}

	switch ($encoding) {
		case 1://8bit
			$body = imap_qprint(imap_8bit($body));
			break;
		case 3://BASE64
			$body = imap_base64($body);
			break;
		case 4://Quoted-Printable
			$body = imap_qprint($body);
			break;
		case 0:
		case 2:
		case 5:
		default:
	}

	return mb_convert_encoding($body, 'UTF-8', $charset);
}

function check_log_type($path) {

	$result = 'その他';

	$fp = fopen($path, 'r');
	while($line = fgets($fp)) {
		if (preg_match('/^START\-OF\-LOG/', $line)) {
			$result = 'Cabrillo';
			break;
		}
	}
	fclose($fp);

	if ($result == 'その他') {
		$result = LogData::checkLogType($path);
	}

	return $result;
}

function create_summary(PDO $db, $path) {

	$log_type = check_log_type($path);

	if ($log_type == 'Cabrillo') {
		$summary = Cabrillo::readFile($db, $path);

	} else if ($log_type == 'CTESTWIN') {
		$summary = CtestWin::readFile($db, $path);

	} else if ($log_type == 'ZLOG') {
		$summary = ZLog::readFile($db, $path);

	} else if ($log_type == 'ZLOGALL') {
		$summary = ZLogAll::readFile($db, $path);

	} else if ($log_type == 'HLTEST7') {
		$summary = HLTest7::readFile($db, $path);

	} else if ($log_type == 'HLTEST8') {
		$summary = HLTest8::readFile($db, $path);

	} else if ($log_type == 'RTCL') {
		$summary = RTCL::readFile($db, $path);

	} else {
		$summary = Others::readFile($db, $path);
	}

	$callsign = (strpos($summary->callsign, '/') === FALSE ? $summary->callsign : substr($summary->callsign, 0, strpos($summary->callsign, '/')));

	$summary->sumid = createUploadTime(date('U'));
	$summary->input_id = $callsign;
	$summary->verify_id = $callsign;
	$summary->register($db);

	return $summary;
}

function send_mail($callsign, $name, $mail, DateTime $date, $log_type, $log_count) {

	$mailer = new PHPMailer(true);

	try {
		$mailer->CharSet = 'UTF-8';
		$mailer->SMTPDebug = SMTP::DEBUG_OFF;
		$mailer->isSMTP();
		$mailer->Host = SMTP_HOST;
		$mailer->Port = SMTP_PORT;
		$mailer->SMTPAuth = TRUE;
		$mailer->Username = USERADDR;
		$mailer->Password = PASSWORD;
		$mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;

		$mailer->setFrom(USERADDR, mb_encode_mimeheader(USERNAME));
		$mailer->addReplyTo(USERADDR, mb_encode_mimeheader(USERNAME));
		$mailer->addAddress($mail, mb_encode_mimeheader($name));

		$mailer->isHTML(false);
		$mailer->Subject = mb_encode_mimeheader(SUBJECT);
		$mailer->Body = "\n自動返信テスト";
		$mailer->Body = <<<EOF

{$callsign} {$name} さん、こんにちは
ＪＡＲＬ中国地方本部コンテスト委員会です

この度はオールＪＡ４コンテストにご参加ありがとうございます
提出いただきましたサマリーとログは確かに受領いたしましたのでお知らせします

提出(メール送信)日時：{$date->format('Y/m/d H:i:s')}
ログフォーマット：{$log_type}
ログ件数：{$log_count}

※ログフォーマットが「Others」となっている場合、ログ部分の自動解析ができず、ログ件数が「０」となります

結果発表までしばらくお待ちください
EOF;

		$mailer->send();

		return TRUE;

	} catch (Exception $ex) {
		return FALSE;

	} finally {
		$mailer = NULL;
	}
}
