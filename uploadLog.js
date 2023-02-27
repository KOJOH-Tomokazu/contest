/**
 * ログアップローダー　JavaScriptファイル
 * @author JJ4KME
 */
const METHOD_TYPE			= 'POST';
const DATA_TYPE				= 'json';
const URL_AJAX				= 'uploadLog.php';
const regClubnumberChar		= /^[a-hA-H0-9\-]$/;
const regClubnumberFormat	= /^(\d{2}[a-hA-H]?)\-([1-4])\-(\d{1,4})$/;
const regDateChar			= /^[\d\/]$/;
const regDateFormat1		= /^((\d{4})(\/))?(\d{1,2})\/(\d{1,2})$/;
const regDateFormat2		= /^(\d{4})?(\d{2})(\d{2})$/;
const regTimeChar			= /^[\d:]$/;
const regTimeFormat1		= /^(\d{1,2}):(\d{1,2})$/;
const regTimeFormat2		= /^(\d{2})(\d{2})$/;
const regCallsignChar		= /^[a-zA-Z0-9\/]$/;
const regCallsign			= /^[a-zA-Z0-9]+(\/[a-zA-Z0-9]+)?$/;
const regSWLChar			= /^[a-zA-Z0-9\/\-]$/;
const regSWL				= /^JA[0-9]\-\d+(\/.+)?$/;
const regRstChar			= /^[1-9]$/;
const regRstFormat			= /^[1-5][1-9][1-9]?$/;
const regMultiChar			= /^[\da-zA-Z\-\/]$/;
const regMultiFormat		= /^([\da-zA-Z\/]{2,6}|\-)$/;
let title;
let schema;
let category				= null;
let clubs;

/**
 * 読み込み完了後の処理
 * @param e イベント
 */
$(window).on('load', function(e) {

	let temp = location.search.substr(1).split('&');
	schema	= temp[0];
	if (temp.length > 1) {
		category = temp[1];
	}

	// ＪＡＲＬ形式でファイルをアップロード
	$('button#jarl_file').click(function(e) {
		$('form#import input#schema').val(schema);
		$('form#import td#fileType').html('ＪＡＲＬ形式');
		$('form#import input#fileType').val('jarl');
		$('form#import input#method').val('file');
		$('form#import tr#file').show();
		$('form#import tr#text').hide();
		$('form#import input#source').val('');
		$('form#import input#timezone_jst').prop('checked', true);

		$('div#import').modal('show');
	});

	// ＪＡＲＬ形式でテキスト貼り付け
	$('button#jarl_text').click(function(e) {
		$('form#import input#schema').val(schema);
		$('form#import td#fileType').html('ＪＡＲＬ形式');
		$('form#import input#fileType').val('jarl');
		$('form#import input#method').val('text');
		$('form#import tr#file').hide();
		$('form#import tr#text').show();
		$('form#import textarea#source').val('');
		$('form#import input#timezone_jst').prop('checked', true);

		$('div#import').modal('show');
	});

	// Cabrillo形式でファイルをアップロード
	$('button#cabrillo_file').click(function(e) {
		$('form#import input#schema').val(schema);
		$('form#import td#fileType').html('Cabrillo形式');
		$('form#import input#fileType').val('cabrillo');
		$('form#import input#method').val('file');
		$('form#import tr#file').show();
		$('form#import tr#text').hide();
		$('form#import input#source').val('');
		$('form#import input#timezone_utc').prop('checked', true);

		$('div#import').modal('show');
	});

	// Cabrillo形式でテキスト貼り付け
	$('button#cabrillo_text').click(function(e) {
		$('form#import input#schema').val(schema);
		$('form#import td#fileType').html('Cabrillo形式');
		$('form#import input#fileType').val('cabrillo');
		$('form#import input#method').val('text');
		$('form#import tr#file').hide();
		$('form#import tr#text').show();
		$('form#import textarea#source').val('');
		$('form#import input#timezone_utc').prop('checked', true);

		$('div#import').modal('show');
	});

	// 取込開始
	$('button#start_import').click(function(e) {
		if ($('form#import input#method').val() == 'file' && $('form#import input#source').val() == '') {
			// ファイルが未選択
			$('form#import input#source').focus();
			showAlertDialog('オールＪＡ４コンテスト', 'アップロードするファイルを選択してください');
			return;
		}

		if ($('form#import input#method').val() == 'text' && $('form#import textarea#source').val() == '') {
			// テキストが未入力
			$('form#import textarea#source').focus();
			showAlertDialog('オールＪＡ４コンテスト', 'サマリーとログを貼り付けてください');
			return;
		}

		importLog(new FormData($('form#import')[0]));
		$('div#import').modal('hide');
	});

	// 取込キャンセル
	$('button#cancel_import').click(function(e) {

		$('div#import').modal('hide');
	});

	// 提出ボタンが押された
	$('button#register').click(function(e) {
		if ($('select#category').prop('selectedIndex') == -1) {
			// カテゴリーが未選択
			$('select#category').focus();
			showAlertDialog('オールＪＡ４コンテスト', '参加部門を選択してください');
			return;
		}

		if ($('input#owner').val() == '') {
			// コールサインが未入力
			$('input#owner').focus();
			showAlertDialog('オールＪＡ４コンテスト', '運用したコールサインを入力してください');
			return;

		} else if (!$('input#owner').val().match(regCallsign) && !$('input#owner').val().match(regSWL)) {
			// 形式が合っていなかったら
			$('input#owner').focus();
			showAlertDialog('オールＪＡ４コンテスト', 'コールサインのフォーマットが不正です');
			return;
		}

		if ($('input#name').val() == '') {
			// 氏名が未入力
			$('input#name').focus();
			showAlertDialog('オールＪＡ４コンテスト', '氏名(社団の代表者名)を入力してください');
			return;
		}

		if ($('form#register input#password1').val() != $('form#register input#password2').val()) {
			// パスワードが違う
			showAlertDialog('オールＪＡ４コンテスト', 'パスワードが異なります');
			return;
		}

		if (countLog() == 0 && $('td#logType').html() != 'その他') {
			// ログが無い
			showAlertDialog('オールＪＡ４コンテスト', 'ログデータが入力されていません');
			return;
		}

		if ($('input#replaceLog').is(':visible') && !$('input#replaceLog').prop('checked')) {
			// ログ置換チェックが入っていない
			showAlertDialog('オールＪＡ４コンテスト', '既に提出済みのサマリーが削除されます。確認のためチェックを入れてください');
			return;
		}

		$('form#register input#schema').val(schema);

		registLog(new FormData($('form#register')[0]));
	});

	// ツールチップを付ける
//	$(document).tooltip({
//		track:		true,
//		items:		'input',
//		content:	function() {
//			if ($(this).hasClass('error')) {
//				return	$(this).prop('title');
//
//			} else if ($(this).prop('id') == 'workdate') {
//				return	$('div#helpDate').html();
//
//			} else if ($(this).prop('id') == 'worktime') {
//				return	$('div#helpTime').html();
//
//			} else if ($(this).prop('id') == 'owner' || $(this).prop('id') == 'callsign') {
//				return $('div#helpCallsign').html();
//			}
//		}
//	});

	new bootstrap.Modal('div#import');

	initialize();
});

$(window).on('unload', function(e) {

	$('div#import').modal('dispose');
});

/**
 * 初期処理
 */
function initialize() {

	$.getJSON(schema + '.json', function (data, textStatus, jqXHR) {
		title = data.title;
		document.title = title + ' ログアップロード';
		$('span#title').html(title);
	});

	$.ajax({
		type:		METHOD_TYPE,
		url:		URL_AJAX,
		dataType:	DATA_TYPE,
		data:		{
			SCHEMA:		schema,
			CALL_AJAX:	'initialize'},
		beforeSend:	function (jqXHR) {
			$('div#wait').show();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.RESULTCD == 0) {
			// 正常に取得できていたら
			// ステータスに合わせてメッセージを表示
			if (data.status == 0) {
				$('p#message').append([
					title + 'はまだ開催されていません。お試しでログのアップロードは可能ですが、コンテスト開始時にデータは抹消されます',
					$('<br />'),
					'ログ提出システムに関してご意見等あれば jj4kme@gmail.com まで連絡頂ければ幸いです']);

			} else if (data.status == 1) {
				$('p#message').html(tltle + 'は現在開催中です。お早めにログの提出をお願いします');

			} else if (data.status == 2) {
				$('p#message').html(title + 'は終了しました。お早めにログの提出をお願いします');

			} else if (data.status == 3) {
				$('p#message').html('ただいまの期間は電子データでのチェックログのみ受け付けています');

			} else if (data.status == 4) {
				$('body').empty();
				$('body').append(
					$('<p />').attr({id: 'message'}).html(title + 'はログの提出を締め切りました。結果発表までしばらくお待ちください'));
			}

			// 周波数帯のリストをセット
			$('select#freq').empty();
			for (let value in data.BANDS) {
				$('select#freq').append($('<option />').val(value).html(data.BANDS[value]));
			}
			$('select#freq').prop('selectedIndex', -1);

			// モードのリストをセット
			$('select#mode').empty();
			for (let value in data.MODES) {
				$('select#mode').append($('<option />').val(data.MODES[value]).html(value));
			}
			$('select#mode').prop('selectedIndex', -1);

			// 参加部門のリストをセット
			$('select#category').empty();
			for (let i = 0; i < data.CATEGORIES.length; i++) {
				$('select#category').append($('<option />').val(data.CATEGORIES[i].code).html(data.CATEGORIES[i].name));
			}
			if (category === null) {
				$('select#category').prop('selectedIndex', -1);
			} else {
				$('select#category').val(category);
			}

			// 登録クラブのリストを保存
			clubs = {};
			for (let i = 0; i < data.CLUBS.length; i++) {
				clubs[data.CLUBS[i].number] = data.CLUBS[i].name;
			}
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {
		$('div#wait').hide();
	});
}

/**
 * アラートダイアログを表示する
 * @param title タイトル
 * @param message メッセージ
 * @param buttons ボタン定義
 */
function showAlertDialog(title, message, buttons) {

	// ダイアログのメッセージを設定
	$('div#dialog').html(message);
	// ダイアログを作成
	$('div#dialog').dialog({
		modal	: true,
		title	: title,
		buttons : (buttons == null ? defaultButtons : buttons)});
}

/**
 * ログを追加できるか調べる
 * @param target 対象行
 * @returns 追加ＯＫならtrue、ＮＧならfalse
 */
function canRegist(target) {

	let result = true;
	let source = $(target).find('input');
	for (let i = 0; i < source.length; i++) {
		result = result & !$(source[i]).hasClass('error');
	}

	source = $(target).find('select');
	for (let i = 0; i < source.length; i++) {
		result = result & !$(source[i]).hasClass('error');
	}

	$(target).find('button').prop('disabled', !result);
	return result;
}

function onChange_Category(target) {

	let value = target.value;

	if (value.match(/SWL$/) != null && location.pathname == '/uploadLog.html') {
		location.href = 'uploadSWL.html?' + schema + '&' + value;
	}
	if (value.match(/SWL$/) == null && location.pathname == '/uploadSWL.html') {
		location.href = 'uploadLog.html?' + schema + '&' + value;
	}
}

/**
 * 登録クラブ番号でキーが押された
 * @param e イベント
 * @returns 入力ＯＫならtrue、ＮＧならfalse
 */
function onKeyPress_Regclubnumber(e) {

	return	regClubnumberChar.test(String.fromCharCode(e.which));
}

/**
 * 登録クラブ番号からフォーカスが外れた
 * @param target 対象要素
 */
function onBlur_Regclubnumber(target) {

	let value = target.value;
	$(target).removeClass('error').prop('title', '');

	if (value == '') {
		$('td#regclubname').empty();
		return;

	} else if (value.match(regClubnumberFormat)) {
		let temp = value.match(regClubnumberFormat);
		$(target).val(temp[1].toUpperCase() + '-' + temp[2] + '-' + ('000' + parseInt(temp[3])).slice(-4));
		if (clubs.hasOwnProperty(target.value)) {
			$('td#regclubname').html(clubs[target.value]);

		} else {
			$('td#regclubname').empty();
		}

	} else {
		$(target).addClass('error').prop('title', '登録クラブ番号のフォーマットが不正です');
	}
}

/**
 * 日付でキーが押された
 * @param e イベント
 * @returns 入力ＯＫならtrue、ＮＧならfalse
 */
function onKeyPress_Date(e) {

	return	regDateChar.test(String.fromCharCode(e.which));
}

/**
 * 日付からフォーカスが外れた
 * @param target 対象要素
 */
function onBlur_Date(target) {

	let value = target.value;
	$(target).removeClass('error').prop('title', '');

	if (value == '') {
		// 日付が指定されていなかったら
		$(target).addClass('error').prop('title', '日付を入力してください');

	} else if (value.match(regDateFormat1)) {
		// '/'付きフォーマットだったら
		let temp = value.match(regDateFormat1);
		if (temp[2] === undefined) {
			// 年が指定されていなかったら付加
			temp[2] = (new Date()).getFullYear();
		}

		temp[4] = parseInt(temp[4]);
		temp[5] = parseInt(temp[5]);

		if (isNaN(Date.parse(temp[2] + '/' + temp[4] + '/' + temp[5])) || (new Date(temp[2] + '/' + temp[4] + '/' + temp[5])).getMonth() + 1 != temp[4]) {
			// 日付の値が間違っていたら
			$(target).addClass('error').prop('title', '日付が不正です');

		} else {
			// 正しかったら再フォーマット
			$(target).val(temp[2] + '/' + ('0' + temp[4]).slice(-2) + '/' + ('0' + temp[5]).slice(-2));
		}

	} else if (value.match(regDateFormat2)) {
		// '/'無しフォーマットだったら
		let temp = value.match(regDateFormat2);
		if (temp[1] === undefined) {
			// 年が指定されていなかったら付加
			temp[1] = (new Date()).getFullYear();
		}

		temp[2] = parseInt(temp[2]);
		temp[3] = parseInt(temp[3]);

		if (isNaN(Date.parse(temp[1] + '/' + temp[2] + '/' + temp[3])) || (new Date(temp[1] + '/' + temp[2] + '/' + temp[3])).getMonth() + 1 != temp[2]) {
			// 日付の値が間違っていたら
			$(target).addClass('error').prop('title', '日付が不正です');

		} else {
			// 正しかったら再フォーマット
			$(target).val(temp[1] + '/' + ('0' + temp[2]).slice(-2) + '/' + ('0' + temp[3]).slice(-2));
		}

	} else {
		$(target).addClass('error').prop('title', '日付のフォーマットが不正です');
	}

	canRegist($(target).parent().parent());
}

/**
 * 時刻でキーが押された
 * @param e イベント
 * @returns 入力ＯＫならtrue、ＮＧならfalse
 */
function onKeyPress_Time(e) {

	return	regTimeChar.test(String.fromCharCode(e.which));
}

/**
 * 時刻からフォーカスが外れた
 * @param target 対象要素
 */
function onBlur_Time(target) {

	let value = target.value;
	$(target).removeClass('error').prop('title', '');

	if (value == '') {
		// 時刻が指定されていなかったら
		$(target).addClass('error').prop('title', '時刻を入力してください');

	} else if (value.match(regTimeFormat1)) {
		// ':'付きフォーマットだったら
		let temp = value.match(regTimeFormat1);
		temp[1] = parseInt(temp[1]);
		temp[2] = parseInt(temp[2]);
		if (temp[1] < 0 || temp[1] > 23 || temp[2] < 0 || temp[2] > 59) {
			// 時刻の値が間違っていたら
			$(target).addClass('error').prop('title', '時刻が不正です');

		} else {
			// 正しかったら再フォーマット
			$(target).val(('0' + temp[1]).slice(-2) + ':' + ('0' + temp[2]).slice(-2));
		}

	} else if (value.match(regTimeFormat2)) {
		// '/'無しフォーマットだったら
		let temp = value.match(regTimeFormat2);
		temp[1] = parseInt(temp[1]);
		temp[2] = parseInt(temp[2]);
		if (temp[1] < 0 || temp[1] > 23 || temp[2] < 0 || temp[2] > 59) {
			// 日付の値が間違っていたら
			$(target).addClass('error').prop('title', '日付が不正です');

		} else {
			// 正しかったら再フォーマット
			$(target).val(('0' + temp[1]).slice(-2) + ':' + ('0' + temp[2]).slice(-2));
		}

	} else {
		$(target).addClass('error').prop('title', '時刻のフォーマットが不正です');
	}

	canRegist($(target).parent().parent());
}

/**
 * コールサインでキーが押された
 * @param e イベント
 * @returns 入力ＯＫならtrue、ＮＧならfalse
 */
function onKeyPress_Callsign(e) {

	return	regCallsignChar.test(String.fromCharCode(e.which)) || regSWLChar.test(String.fromCharCode(e.which));
}

/**
 * コールサインからフォーカスが外れた
 * @param target 対象要素
 */
function onBlur_Callsign(target) {

	let value = target.value;
	$(target).removeClass('error').prop('title', '');

	if (value == '') {
		// コールサインが入力されていなかったら
		$(target).addClass('error').prop('title', 'コールサインを入力してください');

	} else if (!value.match(regCallsign) && !value.match(regSWL)) {
		// 形式が合っていなかったら
		$(target).addClass('error').prop('title', 'コールサインのフォーマットが不正です');

	} else {
		$(target).val(value.toUpperCase());
	}

	canRegist($(target).parent().parent());
}

/**
 * 周波数からフォーカスが外れた
 * @param target 対象要素
 */
function onBlur_Freq(target) {

	$(target).removeClass('error').prop('title', '');

	let index = $(target).prop('selectedIndex');
	if (index == undefined) {
		$(target).addClass('error').prop('title', '周波数帯を選択してください');
		return;
	}

	let value = $(target).children()[index].value;
	if (value == '') {
		$(target).addClass('error').prop('title', '周波数帯を選択してください');
		return;
	}
}

/**
 * 電波の型式からフォーカスが外れた
 * @param target 対象要素
 */
function onBlur_Mode(target) {

	$(target).removeClass('error').prop('title', '');

	let index = $(target).prop('selectedIndex');
	if (index == undefined) {
		$(target).addClass('error').prop('title', '電波の型式を選択してください');
		return;
	}

	let value = $(target).children()[index].value;
	if (value == '') {
		$(target).addClass('error').prop('title', '電波の型式を選択してください');
		return;
	}
}

/**
 * リポートでキーが押された
 * @param e イベント
 * @returns 入力ＯＫならtrue、ＮＧならfalse
 */
function onKeyPress_Rst(e) {

	return	regRstChar.test(String.fromCharCode(e.which));
}

/**
 * リポートからフォーカスが外れた
 * @param target 対象要素
 */
function onBlur_Rst(target) {

	let value = target.value;
	$(target).removeClass('error').prop('title', '');

	if (value == '') {
		// リポートが入力されていなかったら
		$(target).addClass('error').prop('title', 'リポートを入力してください');
	}

	canRegist($(target).parent().parent());
}

/**
 * マルチプライヤーでキーが押された
 * @param e イベント
 * @returns 入力ＯＫならtrue、ＮＧならfalse
 */
function onKeyPress_Multi(e) {

	return	regMultiChar.test(String.fromCharCode(e.which));
}

/**
 * マルチプライヤーからフォーカスが外れた
 * @param target 対象要素
 */
function onBlur_Multi(target) {

	let value = target.value;
	$(target).removeClass('error').prop('title', '');

	if (value == '') {
		// リポートが入力されていなかったら
		$(target).addClass('error').prop('title', 'マルチプライヤーを入力してください');
	}

	canRegist($(target).parent().parent());
}

/**
 * ログを追加する
 * @param e イベント
 */
function addRow(e) {

	let target = $(e.target).parent().parent();

	if (!canRegist(target)) {
		return;
	}

	let freqIndex = $(target).find('select#freq').prop('selectedIndex');
	let modeIndex = $(target).find('select#mode').prop('selectedIndex');

	// １件のログデータを作る
	let newLine = getLogRow({
			workdate:	$(target).find('input#workdate').val(),
			worktime:	$(target).find('input#worktime').val(),
			callsign:	$(target).find('input#callsign').val().toUpperCase(),
			freq:		$(target).find('select#freq > option')[freqIndex].value,
			band:		$(target).find('select#freq > option')[freqIndex].innerHTML,
			modecat:	$(target).find('select#mode > option')[modeIndex].value,
			mode:		$(target).find('select#mode > option')[modeIndex].innerHTML,
			sentrst:	$(target).find('input#sentrst').val(),
			sentmulti:	$(target).find('input#sentmulti').val(),
			recvrst:	$(target).find('input#recvrst').val(),
			recvmulti:	$(target).find('input#recvmulti').val()});
	// 削除ボタンを付ける
	$(newLine).append($('<td />').addClass('button').append($('<button />').addClass('btn btn-sm btn-primary').attr({onclick: 'deleteRow(event);'}).html('←削除')));

	$(e.target).parent().parent().before(newLine);

	$(target).find('input#worktime').val('');
	$(target).find('input#callsign').val('');
//	$(target).find('input#sentrst').val('');
//	$(target).find('input#sentmulti').val('');
	$(target).find('input#recvrst').val('');
	$(target).find('input#recvmulti').val('');
	$(target).find('button').prop('disabled', true);

	$('td#logCount').html(countLog());
}

/**
 * ログを削除する
 * @param e イベント
 */
function deleteRow(e) {

	$(e.target).parent().parent().remove();
	$('td#logCount').html(countLog());
}

/**
 * ログ行を作る
 * @param logData ログデータ
 * @returns １行分のデータ
 */
function getLogRow(logData) {

	let row = $('<tr />');
	$(row).append($('<input />').attr({type: 'hidden',	name: 'workdate[]'}		).val(logData.workdate));
	$(row).append($('<input />').attr({type: 'hidden',	name: 'worktime[]'}		).val(logData.worktime));
	$(row).append($('<input />').attr({type: 'hidden',	name: 'callsign[]'}		).val(logData.callsign));
	$(row).append($('<input />').attr({type: 'hidden',	name: 'freq[]'}			).val(logData.freq));
	$(row).append($('<input />').attr({type: 'hidden',	name: 'mode[]'}			).val(logData.mode));
	$(row).append($('<input />').attr({type: 'hidden',	name: 'modecat[]'}		).val(logData.modecat));
	$(row).append($('<input />').attr({type: 'hidden',	name: 'recvrst[]'}		).val(logData.recvrst));
	$(row).append($('<input />').attr({type: 'hidden',	name: 'recvmulti[]'}	).val(logData.recvmulti));
	$(row).append($('<input />').attr({type: 'hidden',	name: 'sentrst[]'}		).val(logData.sentrst));
	$(row).append($('<input />').attr({type: 'hidden',	name: 'sentmulti[]'}	).val(logData.sentmulti));
	$(row).append($('<td />').addClass('datetime').html(logData.workdate + ' ' + logData.worktime));
	$(row).append($('<td />').addClass('callsign').html(logData.callsign));
	$(row).append($('<td />').addClass('freq').html(logData.band + ' MHz'));
	$(row).append($('<td />').addClass('mode').html(logData.mode));
	$(row).append($('<td />').addClass('sentNumber').append(
			$('<span />').attr({id: 'sentrst'}).html(logData.sentrst)).append(
			$('<span />').attr({id: 'sentmulti'}).html(logData.sentmulti)));
	$(row).append($('<td />').addClass('recvNumber').append(
			$('<span />').attr({id: 'recvrst'}).html(logData.recvrst)).append(
			$('<span />').attr({id: 'recvmulti'}).html(logData.recvmulti)));

	return row;
}

/**
 * ログ件数を数える
 */
function countLog() {

	return $('table#log > tbody > tr').length - $('table#log > tbody > tr#input').length - $('table#log > tbody > tr#message').length;
}

/**
 * 部門の同時提出可否を調べる
 */
function checkCategoryDup() {

	$.ajax({
		type:		METHOD_TYPE,
		url:		URL_AJAX,
		dataType:	DATA_TYPE,
		data:		{
			SCHEMA:		schema,
			CALL_AJAX:	'checkCategoryDup',
			callsign:	$('input#owner').val(),
			category:	$('select#category').val()},
		beforeSend:	function (jqXHR) {
			$('select#category').removeClass('error').prop('title', '');
			$('tr#replaceLog').hide();
			$('input#replaceLog').prop('checked', true);
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.RESULTCD == 1) {
			// 既に提出済みのカテゴリーだったら
			$('tr#replaceLog').show();
			$('input#replaceLog').prop('checked', false);

		} else if (data.RESULTCD == 2) {
			// 同時提出できないカテゴリーだったら
			$('select#category').addClass('error').prop('title', '同時に提出できない組み合わせです');
			$('input#replaceLog').prop('checked', false);

		} else if (data.RESULTCD == 3) {
			// 既に２個あったら
			$('select#category').addClass('error').prop('title', '既に２部門提出されています');
			$('input#replaceLog').prop('checked', false);
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {

	});
}

/**
 * ログデータを取り込む
 * @param formData フォームデータ
 */
function importLog(formData) {

	$.ajax({
		type:			METHOD_TYPE,
		url:			URL_AJAX,
		dataType:		'html',
        cache:			false,
        contentType:	false,
        processData:	false,
		data:			formData,
		beforeSend:		function (jqXHR) {
			$('div#wait').show();
		}
	}).done(function (data, textStatus, jqXHR) {
		let result = JSON.parse(data);
		if (result.RESULTCD == 0 || result.RESULTCD == 1) {
			$('input#timezone').val(result.summary.timezone);
			$('input#filename').val(result.summary.filename);
			$('input#status').val(result.summary.status);

			$('select#category').val(result.summary.category).change();
			$('input#owner').val(result.summary.owner);
			$('input#name').val(result.summary.name);
			$('input#address').val(result.summary.address);
			$('input#email').val(result.summary.email);
			$('input#comments').val(result.summary.comments);
			$('input#multioplist').val(result.summary.multioplist);
			$('input#regclubnumber').val(result.summary.regclubnumber).blur();
			checkCategoryDup();
			$('td#logCount').html(result.logCount);
			$('td#logType').html(result.logType);

			if (result.summary.timezone == '+09') {
				$('span#timezone').html('JST');

			} else if (result.summary.timezone == '+00') {
				$('span#timezone').html('UTC');
			}

			if (result.RESULTCD == 0) {
				// ログデータが解析できたら
				$('table#log > tbody > tr[id!=input]').remove();

				for (let i = 0; i < result.logData.length; i++) {
					// １件のログデータを作る
					let newLine = getLogRow(result.logData[i]);
					// 空白を付ける
					$(newLine).append($('<td />').addClass('button').html('&nbsp;'));

					$('table#log > tbody > tr#input').before(newLine);
				}

				$('tr#input').hide();
				$('tr#message').hide();
				$('table#log').show();

			} else {
				// ログデータが解析できなかったら
				$('table#log').hide();
			}

			showAlertDialog('オールＪＡ４コンテスト', '<ol><li>内容が正しいか確認して、画面最下部の「提出」ボタンを押してください</li><li>間違っている場合は修正または再度取り込みを行ってください</li><li>画面の内容をクリアするには、このページを再読み込みしてください</li></ol>');
			$('div#wait').hide();
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {

	});
}

/**
 * ログを提出する
 * @param formData フォームデータ
 */
function registLog(formData) {

	formData.SCHEMA = schema;

	$.ajax({
		type:			METHOD_TYPE,
		url:			URL_AJAX,
		dataType:		'html',
        cache:			false,
        contentType:	false,
        processData:	false,
		data:			formData,
		beforeSend:		function (jqXHR) {
			$('div#wait').show();
		}
	}).done(function (data, textStatus, jqXHR) {
		let result = JSON.parse(data);
		if (result.RESULTCD == 0) {
			showAlertDialog('オールＪＡ４コンテスト', 'ログの提出が完了しました', [
				{text: "ＯＫ", click: function(e) {
					e.stopPropagation();
					$(this).dialog('close');
					location.href = 'listLog.html?' + schema;}}]);
		}
		$('div#wait').hide();

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {

	});
}
