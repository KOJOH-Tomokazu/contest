/**
 * ログデータの手動登録　JavaScriptファイル
 * @author JJ4KME
 */
const METHOD_TYPE			= 'POST';
const DATA_TYPE				= 'json';
const URL_AJAX				= 'registerLog.php';
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
const regRstChar			= /^[\d\-]$/;
const regRstFormat			= /^(\d{2,3}|\-)$/;
const regMultiChar			= /^[\da-zA-Z\-\/]$/;
const regMultiFormat		= /^([\da-zA-Z\/]{2,6}|\-)$/;
let summaries;
let schema;
let sumid;
let clubs;

/**
 * 読み込み完了後の処理
 * @param e イベント
 */
$(window).on('load', function(e) {

	schema	= location.search.slice(1);

	// 戻るボタンが押された
	$('button#logout').click(function(e) {
		location.href = 'admin.php?SCHEMA=' + schema;
	});

	$('button#deleteSummary').click(function(e) {
		showAlertDialog('コンテストログ', '表示中のサマリーを削除しますか？', [
			$('<button />').addClass('btn btn-sm btn-danger' ).attr({onclick: 'deleteSummary();$("div#dialog").modal("hide");'}).html('削除'),
			$('<button />').addClass('btn btn-sm btn-primary').attr({onclick: '                $("div#dialog").modal("hide");'}).html('キャンセル')]);
	});

	// ツールチップを付ける
//	$(document).tooltip({
//		track:		false,
//		items:		'input',
//		content:	function() {
//			if ($(this).prop('id') == 'workdate') {
//				return	$('div#helpDate').html();
//
//			} else if ($(this).prop('id') == 'worktime') {
//				return	$('div#helpTime').html();
//			}
//		}
//	});

	if (checkLoggedIn()) {
		// ログインされていたら初期処理
		initialize();
		getSummaries();
		$('div#wait').hide();

	} else {
		// ログインされていなかったら管理者ポータル
		location.href = 'admin.html';
	}
});

function initialize() {

	$.ajax({
		type:		METHOD_TYPE,
		url:		URL_AJAX,
		dataType:	DATA_TYPE,
		data:		{
			SCHEMA:		schema,
			CALL_AJAX:	'initialize'},
		beforeSend:	function (jqXHR) {
			$('div#wait').show();
			$('select#freq').empty();
			$('select#mode').empty();
			clubs = {};
		},
	}).done(function (data, textStatus, jqXHR) {
		if (data.RESULTCD == 0) {
			$('span#contest_name').html(data.schemas[0].description);
			// 周波数帯のリストを作る
			for (let value in data.BANDS) {
				$('select#freq').append($('<option />').val(value).html(data.BANDS[value]));
			}
			$('select#freq').prop('selectedIndex', -1);

			// モードのリストを作る
			for (let value in data.MODES) {
				$('select#mode').append(
					$('<option />').val(data.MODES[value]).html(value));
			}
			$('select#mode').prop('selectedIndex', -1);

			// 登録クラブのリストを保存
			for (let i = 0; i < data.CLUBS.length; i++) {
				clubs[data.CLUBS[i].number] = data.CLUBS[i].name;
			}

		} else {
			alert(data.MESSAGE);
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {
		$('div#wait').hide();
	});
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
		$('span#regclubname').empty();
		return;

	} else if (value.match(regClubnumberFormat)) {
		let temp = value.match(regClubnumberFormat);
		$(target).val(temp[1].toUpperCase() + '-' + temp[2] + '-' + temp[3].padStart(4, '0'));
		if (clubs.hasOwnProperty(target.value)) {
			$('input#regclubname').val(clubs[target.value]);

		} else {
			$('input#regclubname').val('');
		}

	} else {
		$(target).addClass('error').prop('title', '登録クラブ番号のフォーマットが不正です');
	}
}

/**
 * ログを追加できるか調べる
 * @param target 対象行
 * @returns 追加ＯＫならtrue、ＮＧならfalse
 */
function canRegist(target) {

//	let result = true;
//	let source = $(target).find('input');
//	for (let i = 0; i < source.length; i++) {
//		result = result & !$(source[i]).hasClass('error');
//	}
//
//	source = $(target).find('select');
//	for (let i = 0; i < source.length; i++) {
//		result = result & !$(source[i]).hasClass('error');
//	}
//
//	$(target).find('button').prop('disabled', !result);
//	return result;
	$(target).find('button').prop('disabled', ($(target).find('.error').length != 0 && $('select#sumid').prop('selectedIndex') >= 0));
	return $(target).find('.error').length == 0 || $('select#sumid').prop('selectedIndex') == -1;
}

/**
 * 日付でキーが押された
 * @param e イベント
 * @returns 入力ＯＫならtrue、ＮＧならfalse
 */
function onKeyPressDate(e) {

	return	regDateChar.test(e.key);
}

/**
 * 日付からフォーカスが外れた
 * @param e イベント
 */
function onBlurDate(e) {

	let value = e.target.value;
	$(e.target).removeClass('error').prop('title', '');

	if (value == '') {
		// 日付が指定されていなかったら
		$(e.target).addClass('error').prop('title', '日付を入力してください');

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
			$(e.target).addClass('error').prop('title', '日付が不正です');

		} else {
			// 正しかったら再フォーマット
			e.target.value = temp[2] + '/' + ('0' + temp[4]).slice(-2) + '/' + ('0' + temp[5]).slice(-2);
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
			$(e.target).addClass('error').prop('title', '日付が不正です');

		} else {
			// 正しかったら再フォーマット
			e.target.value = temp[1] + '/' + ('0' + temp[2]).slice(-2) + '/' + ('0' + temp[3]).slice(-2);
		}

	} else {
		$(e.target).addClass('error').prop('title', '日付のフォーマットが不正です');
	}

	canRegist($(e.target).parent().parent().parent());
}

/**
 * 時刻でキーが押された
 * @param e イベント
 * @returns 入力ＯＫならtrue、ＮＧならfalse
 */
function onKeyPressTime(e) {

	return	regTimeChar.test(e.key);
}

/**
 * 時刻からフォーカスが外れた
 * @param e イベント
 */
function onBlurTime(e) {

	let value = e.target.value;
	$(e.target).removeClass('error').prop('title', '');

	if (value == '') {
		// 時刻が指定されていなかったら
		$(e.target).addClass('error').prop('title', '時刻を入力してください');

	} else if (value.match(regTimeFormat1)) {
		// ':'付きフォーマットだったら
		let temp = value.match(regTimeFormat1);

		temp[1] = parseInt(temp[1]);
		temp[2] = parseInt(temp[2]);

		if (temp[1] < 0 || temp[1] > 23 || temp[2] < 0 || temp[2] > 59) {
			// 時刻の値が間違っていたら
			$(e.target).addClass('error').prop('title', '時刻が不正です');

		} else {
			// 正しかったら再フォーマット
			e.target.value = ('0' + temp[1]).slice(-2) + ':' + ('0' + temp[2]).slice(-2);
		}

	} else if (value.match(regTimeFormat2)) {
		// '/'無しフォーマットだったら
		let temp = value.match(regTimeFormat2);
		temp[1] = parseInt(temp[1]);
		temp[2] = parseInt(temp[2]);
		if (temp[1] < 0 || temp[1] > 23 || temp[2] < 0 || temp[2] > 59) {
			// 日付の値が間違っていたら
			$(e.target).addClass('error').prop('title', '日付が不正です');

		} else {
			// 正しかったら再フォーマット
			e.target.value = ('0' + temp[1]).slice(-2) + ':' + ('0' + temp[2]).slice(-2);
		}

	} else {
		$(e.target).addClass('error').prop('title', '時刻のフォーマットが不正です');
	}

	canRegist($(e.target).parent().parent().parent());
}

/**
 * コールサインでキーが押された
 * @param e イベント
 * @returns 入力ＯＫならtrue、ＮＧならfalse
 */
function onKeyPressCallsign(e) {

	return	regCallsignChar.test(String.fromCharCode(e.which)) || regSWLChar.test(String.fromCharCode(e.which));
}

/**
 * コールサインからフォーカスが外れた
 * @param e イベント
 */
function onBlurCallsign(e) {

	let value = e.target.value;
	$(e.target).removeClass('error').prop('title', '');

	if (value == '') {
		// コールサインが入力されていなかったら
		$(e.target).addClass('error').prop('title', 'コールサインを入力してください');

	} else if (!value.match(regCallsign) && !value.match(regSWL)) {
		// 形式が合っていなかったら
		$(e.target).addClass('error').prop('title', 'コールサインのフォーマットが不正です');

	} else {
		$(e.target).val(value.toUpperCase());
	}

	canRegist($(e.target).parent().parent());
}

/**
 * 周波数からフォーカスが外れた
 * @param e イベント
 */
function onBlurFreq(e) {

	$(e.target).removeClass('error').prop('title', '');

	let index = $(e.target).prop('selectedIndex');
	if (index == -1) {
		$(e.target).addClass('error').prop('title', '周波数帯を選択してください');
		return;
	}

	let value = $(e.target).children()[index].value;
	if (value == '') {
		$(e.target).addClass('error').prop('title', '周波数帯を選択してください');
		return;
	}
}

/**
 * 電波の型式からフォーカスが外れた
 * @param e イベント
 */
function onBlurMode(e) {

	$(e.target).removeClass('error').prop('title', '');

	let index = $(e.target).prop('selectedIndex');
	if (index == -1) {
		$(e.target).addClass('error').prop('title', '電波の型式を選択してください');
		return;
	}

	let value = $(e.target).children()[index].value;
	if (value == '') {
		$(e.target).addClass('error').prop('title', '電波の型式を選択してください');
		return;
	}
}

/**
 * リポートでキーが押された
 * @param e イベント
 * @returns 入力ＯＫならtrue、ＮＧならfalse
 */
function onKeyPressRst(e) {

	return	regRstChar.test(e.key);
}

/**
 * リポートからフォーカスが外れた
 * @param e イベント
 */
function onBlurRst(e) {

	let value = $(e.target).val();
	$(e.target).removeClass('error').prop('title', '');

	if (value == '') {
		// リポートが入力されていなかったら
		$(e.target).addClass('error').prop('title', 'リポートを入力してください');

	} else {
		$(e.target).val(value.toUpperCase());
	}

	canRegist($(e.target).parent().parent().parent());
}

/**
 * マルチプライヤーでキーが押された
 * @param e イベント
 * @returns 入力ＯＫならtrue、ＮＧならfalse
 */
function onKeyPressMulti(e) {

	return	regMultiChar.test(e.key);
}

/**
 * マルチプライヤーからフォーカスが外れた
 * @param e イベント
 */
function onBlurMulti(e) {

	let value = $(e.target).val();
	$(e.target).removeClass('error').prop('title', '');

	if (value == '') {
		// リポートが入力されていなかったら
		$(e.target).addClass('error').prop('title', 'リポートを入力してください');

	} else {
		$(e.target).val(value.toUpperCase());
	}

	canRegist($(e.target).parent().parent().parent());
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
	$(row).append($('<td />').addClass('datetime').html(logData.datetime));
	$(row).append($('<td />').addClass('callsign').html(logData.callsign));
	$(row).append($('<td />').addClass('freq').html(logData.band + ' MHz'));
	$(row).append($('<td />').addClass('mode').html(logData.mode));
	$(row).append($('<td />').addClass('sentNumber').append([
		$('<span />').attr({id: 'sentrst'}).html(logData.sentrst),
		$('<span />').attr({id: 'sentmulti'}).html(logData.sentmulti)]));
	$(row).append($('<td />').addClass('recvNumber').append([
		$('<span />').attr({id: 'recvrst'}).html(logData.recvrst),
		$('<span />').attr({id: 'recvmulti'}).html(logData.recvmulti)]));

	return row;
}

/**
 * サマリーのリストを取得
 */
function getSummaries() {

	$.ajax({
		type:		METHOD_TYPE,
		url:		URL_AJAX,
		dataType:	DATA_TYPE,
		data:		{
			SCHEMA:		schema,
			CALL_AJAX:	'getSummaries',
			filter:		$('select#filter').val()},
		beforeSend:	function (jqXHR) {
			$('div#wait').show();
			$('select#owner').empty();
			$('table#summary id#filename'        ).empty();
			$('table#summary input#name'         ).val('');
			$('table#summary input#address'      ).val('');
			$('table#summary input#email'        ).val('');
			$('table#summary input#multioplist'  ).val('');
			$('table#summary input#comments'     ).val('');
			$('table#summary input#regclubnumber').val('');
			$('table#summary span#regclubname'   ).empty();
			$('table#summary td#password'        ).empty();
			$('table#log > tbody').empty();
		},
	}).done(function (data, textStatus, jqXHR) {
		if (data.RESULTCD == 0) {
			summaries = data.SUMMARIES;

			for (let callsign in summaries) {
				$('select#owner').append($('<option />').val(callsign).html(callsign));
			}

			$('select#owner').prop('selectedIndex', -1);
			$('select#sumid').empty();
			$('button#deleteSummary').prop('disabled', true);
			$('button#updateSummary').prop('disabled', true);
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {
		$('div#wait').hide();
	});
}

/**
 * 参加部門のリストを取得
 * @param event イベント
 */
function onChangeOwner(event) {

	$('select#sumid').empty();
	$('table#summary td#filename'        ).empty();
	$('table#summary input#name'         ).val('');
	$('table#summary input#address'      ).val('');
	$('table#summary input#email'        ).val('');
	$('table#summary input#multioplist'  ).val('');
	$('table#summary input#comments'     ).val('');
	$('table#summary input#regclubnumber').val('');
	$('table#summary td#password'        ).empty();
			$('table#summary span#regclubname'   ).empty();
	$('table#log > tbody').empty();

	for (let sumid in summaries[event.target.value]) {
		$('select#sumid').append($('<option />').val(sumid).html(summaries[event.target.value][sumid]));
	}

	$('select#sumid').prop('selectedIndex', -1).focus();
	$('button#deleteSummary').prop('disabled', true);
	$('button#updateSummary').prop('disabled', true);
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
			$('div#wait').show();
			$('select#category').removeClass('error').prop('title', '');
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.RESULTCD == 1) {
			// 既に提出済みのカテゴリーだったら
			$('select#category').addClass('error').prop('title', '同じ参加部門のサマリーがあります');

		} else if (data.RESULTCD == 2) {
			// 同時提出できないカテゴリーだったら
			$('select#category').addClass('error').prop('title', '同時に提出できない組み合わせです');

		} else if (data.RESULTCD == 3) {
			// 既に２個あったら
			$('select#category').addClass('error').prop('title', '既に２部門提出されています');
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {
		$('div#wait').hide();
	});
}

/**
 * 既存のログデータを取得
 * @param sumid サマリーＩＤ
 */
function readLog(sumid) {

	$.ajax({
		type:		METHOD_TYPE,
		url:		URL_AJAX,
		dataType:	DATA_TYPE,
		data:		{
			SCHEMA:		schema,
			CALL_AJAX:	'getLogdata',
			sumid:		sumid},
		beforeSend:	function (jqXHR) {
			$('div#wait').show();
			$('table#summary td#filename'        ).empty();
			$('table#summary input#name'         ).val('');
			$('table#summary input#address'      ).val('');
			$('table#summary input#email'        ).val('');
			$('table#summary input#multioplist'  ).val('');
			$('table#summary input#comments'     ).val('');
			$('table#summary input#regclubnumber').val('');
			$('table#summary span#regclubname'   ).empty();
			$('table#log > tbody').empty();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.RESULTCD == 0) {
			// サマリー情報を表示
			$('table#summary input#name'         ).val(data.summary.name);
			$('table#summary input#address'      ).val(data.summary.address);
			$('table#summary input#email'        ).val(data.summary.email);
			$('table#summary input#multioplist'  ).val(data.summary.multioplist);
			$('table#summary input#comments'     ).val(data.summary.comments);
			$('table#summary input#regclubnumber').val(data.summary.regclubnumber).blur();
			$('table#summary td#password'        ).html((data.summary.password == null ? '設定なし' : '設定あり'));

			if (data.summary.filename == null) {
				// 参考ファイルが未登録だったら
				$('table#summary td#filename').append([
					$('<form />').attr({action: 'registLog.php'}).append([
						$('<input />').attr({type: 'hidden', name: 'sumid'}).val(data.summary.sumid),
						$('<input />').addClass('form-control').attr({type: 'file', name: 'source'})]),
					$('<button />').addClass('btn btn-sm btn-primary').attr({onclick: 'uploadFile();'}).html('アップロード')]);

			} else {
				// 参考ファイルが登録済みだったら
				$('table#summary td#filename').append([
					$('<button />').addClass('btn btn-sm btn-primary').attr({onclick: 'showFile("' + data.summary.sumid + '");'}).html('参照'),
					'&nbsp;',
					data.summary.filename]);
			}

			if (data.summary.logData.length == 0) {
				// ログが未登録だったら
				alert('ログが登録されていません');

			} else {
				let errors = data.errors;

				for (i = 0; i < data.summary.logData.length; i++) {
					let logData = data.summary.logData[i];
					if (logData.verify_id == null) {
						// 未確認ログだったら
						let modeSelect = $('tr#input select#mode').clone(true);
						let children = $(modeSelect).children();
						for (j = 0; j < children.length; j++) {
							let option = children[j];
							if ($(option).html() == logData.mode) {
								$(option).prop('selected', true);
//								return false;
							}
						}

						$('table#log > tbody').append(
							$('<tr />').append([
								$('<input />').attr({type: 'hidden', id: 'sumid'}).val(logData.sumid),
								$('<input />').attr({type: 'hidden', id: 'logid'}).val(logData.logid),
								$('<input />').attr({type: 'hidden', id: 'input_id'}).val(logData.input_id),
								$('<td />').addClass('datetime').append([
									$('<input />').attr({type: 'text', id: 'workdate',  maxlength: '10', onkeypress: 'return onKeyPressDate(event);',     onblur: 'onBlurDate(event);'}).val(logData.workdate),
									$('<input />').attr({type: 'text', id: 'worktime',  maxlength:  '5', onkeypress: 'return onKeyPressTime(event);',     onblur: 'onBlurTime(event);'}).val(logData.worktime)]),
								$('<td />').addClass('callsign').append(
									$('<input />').attr({type: 'text', id: 'callsign',  maxlength: '12', onkeypress: 'return onKeyPressCallsign(event);', onblur: 'onBlurCallsign(event);'}).val(logData.callsign)),
								$('<td />').addClass('freq').append([
									$('tr#input select#freq').clone(true).val(logData.freq),
									'&nbsp;MHz']),
								$('<td />').addClass('mode').append(modeSelect),
								$('<td />').addClass('sentNumber').append([
									$('<input />').attr({type: 'text', id: 'sentrst',   maxlength:  '3', onkeypress: 'return onKeyPressRst(event);',      onblur: 'onBlurRst(event);'}).val(logData.sentrst),
									$('<input />').attr({type: 'text', id: 'sentmulti', maxlength: '10', onkeypress: 'return onKeyPressMulti(event);',    onblur: 'onBlurMulti(event);'}).val(logData.sentmulti)]),
								$('<td />').addClass('recvNumber').append([
									$('<input />').attr({type: 'text', id: 'recvrst',   maxlength:  '3', onkeypress: 'return onKeyPressRst(event);',      onblur: 'onBlurRst(event);'}).val(logData.recvrst),
									$('<input />').attr({type: 'text', id: 'recvmulti', maxlength: '10', onkeypress: 'return onKeyPressMulti(event);',    onblur: 'onBlurMulti(event);'}).val(logData.recvmulti)]),
								$('<td />').addClass('input_id').html(logData.input_id),
								$('<td />').addClass('button').attr({colspan: 3}).append(
									$('<button />').attr({onclick: 'confirmLog(event, "update");' }).html('修正'),
									$('<button />').attr({onclick: 'confirmLog(event, "confirm");'}).html('確認'))]));

					} else {
						// 確認済みログだったら
						$('table#log > tbody').append(
							$('<tr />').append([
								$('<td />').addClass('datetime').append([
									$('<span />').addClass('workdate').html(logData.workdate),
									$('<span />').addClass('worktime').html(logData.worktime)]),
								$('<td />').addClass('callsign').html(logData.callsign),
								$('<td />').addClass('freq').html(data.bands[logData.freq] + ' MHz'),
								$('<td />').addClass('mode').html(logData.mode),
								$('<td />').addClass('sentNumber').append([
									$('<span />').addClass('sentrst').html(logData.sentrst),
									$('<span />').addClass('sentmulti').html(logData.sentmulti)]),
								$('<td />').addClass('recvNumber').append([
									$('<span />').addClass('recvrst').html(logData.recvrst),
									$('<span />').addClass('recvmulti').html(logData.recvmulti)]),
								$('<td />').addClass('input_id').html(logData.input_id),
								$('<td />').addClass('verify_id').html(logData.verify_id),
								$('<td />').addClass('point').html(logData.point),
								$('<td />').addClass('error').append(
									$('<span />').attr({title: errors[logData.error]}).html(logData.error))]));
					}
				}
			}

//			$('tr#input input#workdate').val('');
			$('tr#input input#worktime').val('');
			$('tr#input input#callsign').val('');
//			$('tr#input input#sentrst').val('');
//			$('tr#input input#sentmulti').val('');
//			$('tr#input input#recvrst').val('');
			$('tr#input input#recvmulti').val('');
			$('tr#input button').prop('disabled', true);
			$('button#deleteSummary').prop('disabled', false);
			$('button#updateSummary').prop('disabled', false);
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {
		$('div#wait').hide();
	});
}

/**
 * ログを確認済みにする
 * @param e イベント
 */
function confirmLog(e, action) {

	let target = $(e.target).parent().parent();

	let freqIndex = $(target).find('select#freq').prop('selectedIndex');
	let modeIndex = $(target).find('select#mode').prop('selectedIndex');

	$.ajax({
		type:		METHOD_TYPE,
		url:		URL_AJAX,
		dataType:	DATA_TYPE,
		data:		{
			SCHEMA:		schema,
			CALL_AJAX:	action,
			timezone:	'+09',
			owner:		$('select#owner').val(),
			sumid:		$(target).find('input#sumid').val(),
			logid:		$(target).find('input#logid').val(),
			workdate:	$(target).find('input#workdate').val(),
			worktime:	$(target).find('input#worktime').val(),
			callsign:	$(target).find('input#callsign').val(),
			freq:		$(target).find('select#freq > option')[freqIndex].value,
			band:		$(target).find('select#freq > option')[freqIndex].innerHTML,
			modecat:	$(target).find('select#mode > option')[modeIndex].value,
			mode:		$(target).find('select#mode > option')[modeIndex].innerHTML,
			sentrst:	$(target).find('input#sentrst').val(),
			sentmulti:	$(target).find('input#sentmulti').val(),
			recvrst:	$(target).find('input#recvrst').val(),
			recvmulti:	$(target).find('input#recvmulti').val(),
			input_id:	$(target).find('input#input_id').val()},
		beforeSend:	function (jqXHR) {
			$('div#wait').show();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.RESULTCD == 0) {
			if (action == 'confirm') {
				// ログの確認だったら
				let logData = data.LOGDATA[0];
				$(target).empty();
				$(target).append([
					$('<td />').addClass('datetime').append([
						$('<span />').attr({id: 'workdate'}).html(logData.workdate),
						$('<span />').attr({id: 'worktime'}).html(logData.worktime)]),
					$('<td />').addClass('callsign').html(logData.callsign),
					$('<td />').addClass('freq').html(logData.band + ' MHz'),
					$('<td />').addClass('mode').html(logData.mode),
					$('<td />').addClass('sentNumber').append([
						$('<span />').attr({id: 'sentrst'}).html(logData.sentrst),
						$('<span />').attr({id: 'sentmulti'}).html(logData.sentmulti)]),
					$('<td />').addClass('recvNumber').append([
						$('<span />').attr({id: 'recvrst'}).html(logData.recvrst),
						$('<span />').attr({id: 'recvmulti'}).html(logData.recvmulti)]),
					$('<td />').addClass('input_id').html(logData.input_id),
					$('<td />').addClass('verify_id').html(logData.verify_id),
					$('<td />').addClass('point').html(logData.point),
					$('<td />').addClass('error').html(logData.error)]);
			}

		} else {
			alert(data.MESSAGE);
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {
		$('div#wait').hide();
	});
}

/**
 * ログを追加する
 * @param e イベント
 */
function appendLog(e) {

	let target = $(e.target).parent().parent();

	if ($('select#sumid').prop('selectedIndex') == -1) {
		showAlertDialog('コンテストログ', '対象のサマリーを選択、または作成して下さい', [
			$('<button />').addClass('btn btn-sm btn-primary').attr({onclick: '$("div#dialog").modal("hide");'}).html('ＯＫ')]);

		return;

	} else if (!canRegist(target)) {
		return;
	}

	let freqIndex = $(target).find('select#freq').prop('selectedIndex');
	let modeIndex = $(target).find('select#mode').prop('selectedIndex');

	$.ajax({
		type:		METHOD_TYPE,
		url:		URL_AJAX,
		dataType:	DATA_TYPE,
		data:		{
			SCHEMA:		schema,
			CALL_AJAX:	'append',
			timezone:	'+09',
			owner:		$('select#owner').val(),
			sumid:		$('select#sumid').val(),
			workdate:	$(target).find('input#workdate').val(),
			worktime:	$(target).find('input#worktime').val(),
			callsign:	$(target).find('input#callsign').val(),
			freq:		$(target).find('select#freq > option')[freqIndex].value,
			band:		$(target).find('select#freq > option')[freqIndex].innerHTML,
			modecat:	$(target).find('select#mode > option')[modeIndex].value,
			mode:		$(target).find('select#mode > option')[modeIndex].innerHTML,
			sentrst:	$(target).find('input#sentrst').val(),
			sentmulti:	$(target).find('input#sentmulti').val(),
			recvrst:	$(target).find('input#recvrst').val(),
			recvmulti:	$(target).find('input#recvmulti').val()},
		beforeSend:	function (jqXHR) {

		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.RESULTCD == 0) {
			readLog(data.LOGDATA.sumid);
			$('tr#input input#worktime').focus();

		} else {
			alert(data.MESSAGE);
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {

	});
}

function uploadFile() {

	let formData = new FormData($('td#filename > form')[0]);

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
			$('table#summary td#filename').empty();
			$('table#summary td#filename').append([
				$('<button />').attr({onclick: 'showFile("' + result.SUMID + '");'}).html('参照'),
				'&nbsp;',
				result.FILENAME]);

		} else {
			alert(result.MESSAGE);
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {
		$('div#wait').hide();
	});
}

function showFile(sumid) {

	$('div#source iframe').attr({src: 'showFile.php?sumid=' + sumid});
	$('div#source').dialog({
		buttons:	[{
			text:	'閉じる',
			click:	function() {
//				$('div#source iframe').attr({src: 'abount:blank'});
				$(this).dialog( "close" );
			}}],
		width:		630,
		height:		'auto',
		position:	{
			my:		'right bottom',
			at:		'right bottom+50'}
	});
}

function showNewSummary() {

	$.ajax({
		type:		METHOD_TYPE,
		url:		URL_AJAX,
		dataType:	DATA_TYPE,
		data:		{
			SCHEMA:		schema,
			CALL_AJAX:	'showNewSummary'},
		beforeSend:	function (jqXHR) {
			$('div#wait').show();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.RESULTCD == 0) {
			// 正常に取得出来ていたら
			// コールサインをクリア
			$('div#newSummary input#owner').val('');
			// 氏名をクリア
			$('div#newSummary input#name').val('');
			// 住所をクリア
			$('div#newSummary input#address').val('');
			// メールアドレスをクリア
			$('div#newSummary input#email').val('');
			// 運用者リストをクリア
			$('div#newSummary input#multioplist').val('');
			// 登録クラブ番号・名称をクリア
			$('div#newSummary input#regclubnumber').val('');
			$('div#newSummary span#regclubname').empty();
			// カテゴリーの一覧を作る
			$('div#newSummary select#category').empty();
			for (let disp_order in data.CATEGORIES) {
				$('div#newSummary select#category').append(
					$('<option />').val(data.CATEGORIES[disp_order].code).html(data.CATEGORIES[disp_order].name));
			}
			$('div#newSummary select#category').prop('selectedIndex', -1);
			// 登録クラブの一覧を保存
			clubs = {};
			for (i = 0; i < data.CLUBS.length; i++) {
				let club = data.CLUBS[i];
				clubs[club.number] = club.name;
			}

			$('div#newSummary').dialog({
				width:		560,
				height:		320,
				buttons:	[{
					text:	'作成',
					click:	function() {
						createSummary();
						$(this).dialog('close');}}]
			});

		} else {
			alert(result.MESSAGE);
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {
		$('div#wait').hide();
	});
}

function createSummary() {

	$.ajax({
		type:		METHOD_TYPE,
		url:		URL_AJAX,
		dataType:	DATA_TYPE,
		data:		{
			SCHEMA:			schema,
			CALL_AJAX:		'createSummary',
			category:		$('div#newSummary select#category').val(),
			owner:			$('div#newSummary input#owner').val(),
			name:			$('div#newSummary input#name').val(),
			address:		$('div#newSummary input#address').val(),
			email:			$('div#newSummary input#email').val(),
			multioplist:	$('div#newSummary input#multioplist').val(),
			regclubnumber:	$('div#newSummary input#regclubnumber').val()},
		beforeSend:	function (jqXHR) {
			$('div#wait').show();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.RESULTCD == 0) {
			// 正常に作成出来ていたら再読み込み
			location.reload();

		} else {
			alert(result.MESSAGE);
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {

	});
}

function searchSummary() {

	$.ajax({
		type:		METHOD_TYPE,
		url:		URL_AJAX,
		dataType:	DATA_TYPE,
		data:		{
			SCHEMA:			schema,
			CALL_AJAX:		'searchSummary',
			callsign:		$('div#newSummary input#owner').val()},
		beforeSend:	function (jqXHR) {
			$('div#wait').show();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.RESULTCD == 0) {
			// 正常に作成出来ていたら値をセット
			if (data.SUMMARY !== null) {
				$('div#newSummary input#name').val(data.SUMMARY.name);
				$('div#newSummary input#address').val(data.SUMMARY.address);
				$('div#newSummary input#email').val(data.SUMMARY.email);
				$('div#newSummary input#multioplist').val(data.SUMMARY.multioplist);
				$('div#newSummary input#regclubnumber').val(data.SUMMARY.regclubnumber).blur();
			}

		} else {
			alert(result.MESSAGE);
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {

	});
}

function updateSummary() {

	$.ajax({
		type:		METHOD_TYPE,
		url:		URL_AJAX,
		dataType:	DATA_TYPE,
		data:		{
			SCHEMA:			schema,
			CALL_AJAX:		'updateSummary',
			sumid:			$('select#sumid').val(),
			name:			$('table#summary input#name'         ).val(),
			address:		$('table#summary input#address'      ).val(),
			email:			$('table#summary input#email'        ).val(),
			multioplist:	$('table#summary input#multioplist'  ).val(),
			comments:		$('table#summary input#comments'     ).val(),
			regclubnumber:	$('table#summary input#regclubnumber').val()},
		beforeSend:	function (jqXHR) {
			$('div#wait').show();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.RESULTCD == 0) {
			// 正常に更新できていたら
			readLog(data.SUMMARY.sumid);

		} else {
			alert(result.MESSAGE);
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {
		$('div#wait').hide();
	});
}

function deleteSummary() {

	$.ajax({
		type:		METHOD_TYPE,
		url:		URL_AJAX,
		dataType:	DATA_TYPE,
		data:		{
			SCHEMA:		schema,
			CALL_AJAX:	'deleteSummary',
			sumid:		$('select#sumid').val()},
		beforeSend:	function (jqXHR) {
			$('div#wait').show();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.RESULTCD == 0) {
			// 正常に作成出来ていたら再検索
			getSummaries();

		} else {
			alert(result.MESSAGE);
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {

	});
}
