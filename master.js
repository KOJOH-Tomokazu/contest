/**
 * マスターメンテナンス　JavaScriptファイル
 * @author JJ4KME
 */
const METHOD_TYPE			= 'POST';
const DATA_TYPE				= 'json';
const URL_AJAX				= 'master.php';
const regDateTimeChar		= /^[0-9\/\- :]$/;
const regIntegerChar		= /^\d$/;
const regPrefixChar			= /^[A-Sa-s0-9]$/;
const regDateTimeFormat		= /^(\d{4})([\/\-])(\d{1,2})([\/\-])(\d{1,2})\s+(\d{1,2}):(\d{1,2})$/;
const regCallsignChar		= /^[A-Za-z0-9]$/;
let categories				= {};
let bands					= {};
let user_div_names;
let branches;

/**
 * 読み込み完了後の処理
 * @param e イベント
 */
$(window).on('load', function(e) {

	// 戻るボタンが押された
	$('button#logout').click(function(e) {
		location.href = 'admin.html';
	});

	// ユーザー情報ダイアログを作る
	$('div#administrator').dialog({
		autoOpen:	false,
		resizable:	false,
		width:		380,
		height:		300,
		buttons:	[{
			text:	'ＯＫ',
			click:	function(e) {
				admin_registerUser();}}, {
			text:	'キャンセル',
			click:	function(e) {
				$(this).dialog('close');}}]});

	if (checkLoggedIn()) {
		// ログインされていたら初期処理
		get_user();
		getBands();
		category_list();

	} else {
		// ログインされていなかったら管理者ポータル
		location.href = 'admin.html';
	}
});

function get_user() {

	$.ajax({
		type:		METHOD_TYPE,
		url:		URL_AJAX,
		dataType:	DATA_TYPE,
		data:		{
			CALL_AJAX:	'get_user'},
		beforeSend:	function (jqXHR) {

		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.RESULTCD == 0) {
			// 正常に取得できていたら
			$('a#navbarUserMenu').html(data.user.user_id);
			$('input#user_id').val(data.user.user_id);
			$('span#contest_name').html(data.schema.description);
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {
		$('div#login').modal('show');

	}).always(function () {

	});
}

/* ========================================================================== */
/* 全体                                                                       */
/* ========================================================================== */
/**
 * 周波数帯の一覧を得る
 */
function getBands() {

	$.ajax({
		type:			METHOD_TYPE,
		url:			URL_AJAX,
		dataType:		DATA_TYPE,
		data:			{
			CALL_AJAX:	'getBands'},
		beforeSend:		function (jqXHR) {
			$('div#wait').show();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.RESULTCD == 0) {
			// 正常に取得出来ていたら
			bands = {};
			for (let i = 0; i < data.BANDS.length; i++) {
				bands[data.BANDS[i].value] = data.BANDS[i].label;
			}
			$('div#tabs > ul > li:first > a').click();

		} else {
			showAlertDialog('コンテスト管理用', data.MESSAGE);
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {
		$('div#wait').hide();
	});
}

function onKeyPress_Integer(event) {

	return regIntegerChar.test(event.key);
}

function onBlur_4V0(event) {

	event.target.value = ('0000' + event.target.value).slice(-4);
}

/**
 * 日時欄でキーが押された
 * @param event イベント
 * @returns 入力ＯＫならtrue、ＮＧならfalse
 */
function onKeyPress_DateTime(event) {

	return regDateTimeChar.test(event.key);
}

/**
 * 日時欄からフォーカスが外れた
 * @param event イベント
 */
function onBlur_DateTime(event) {

	$(event.target).removeClass('error').prop('title', '');

	if (event.target.value != '') {
		let temp = event.target.value.match(regDateTimeFormat);
		if (temp == null || temp.length != 8) {
			$(event.target).addClass('error').prop('title', '日時のフォーマットが不正です');

		} else if ((new Date(temp[1], temp[3] - 1, temp[5])).getMonth() != temp[3] - 1) {
			$(event.target).addClass('error').prop('title', '日付の値が不正です');

		} else if (temp[6] < 0 || temp[6] > 23 || temp[7] < 0 || temp[7] > 59) {
			$(event.target).addClass('error').prop('title', '時刻の値が不正です');

		} else {
			event.target.value = temp[1] + temp[2] + ('00' + temp[3]).slice(-2) + temp[4] + ('00' + temp[5]).slice(-2) + ' ' + ('00' + temp[6]).slice(-2) + ':' + ('00' + temp[7]).slice(-2);
		}
	}
}

function onKeyPress_prefix(event) {

	return regPrefixChar.test(event.key);
}

function onBlur_toUpperCase(event) {

	event.target.value = event.target.value.toUpperCase();
}

function onKeyPress_callsign(event) {

	return regCallsignChar.test(event.key);
}

/* ========================================================================== */
/* 参加カテゴリと周波数帯                                                     */
/* ========================================================================== */
/**
 * 参加カテゴリの一覧を得る
 */
function category_list() {

	$.ajax({
		type:			METHOD_TYPE,
		url:			URL_AJAX,
		dataType:		DATA_TYPE,
		data:			{
			CALL_AJAX:	'getCategories'},
		beforeSend:		function (jqXHR) {
			$('div#wait').show();
			$('table#categories > tbody').empty();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.RESULTCD == 0) {
			for (let code in data.categories) {
				category_add(data.categories[code]);
			}
			category_renumber();

		} else {
			showAlertDialog('コンテスト管理用', data.MESSAGE);
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {
		$('div#wait').hide();
	});
}

/**
 * カテゴリーを追加する
 * @param data データ
 */
function category_add(data) {

	if (data === undefined) {
		// 行追加だったら仮の値
		data = {
				code:	'',
				name:	'',
				bands:	[]};

	} else {
		let temp = [];
		for (let band of data.bands) {
			temp.push(band.freq);
		}
		data.bands = temp;
	}

	let bandCell = $('<td />').addClass('bands');
	for (let key in bands) {
		key = parseInt(key);
		$(bandCell).append($('<label />').append([
			$('<input />').addClass('form-check-input').attr({type: 'checkbox', id: 'band_' + key}).prop('checked', (data.bands.indexOf(key) != -1)).val(key),
			bands[key]]));
	}

	let index = $('table#categories > tbody > tr').length;
	$('table#categories > tbody').append(
		$('<tr />').attr({id: 'category_' + index}).append([
			$('<td />').addClass('order align-middle'),
			$('<td />').addClass('code').append(
				$('<input />').addClass('form-control').attr({type: 'text', maxlength: '5', id: 'code'}).val(data.code)),
			$('<td />').addClass('name').append(
				$('<input />').addClass('form-control').attr({type: 'text',                 id: 'name'}).val(data.name)),
			bandCell,
			$('<td />').addClass('buttons align-middle').append([
				$('<button />').addClass('btn btn-sm btn-primary'     ).attr({id: 'moveUp',   onclick: 'category_moveUp(  "category_' + index + '");', title: '表示順を一つ上げる'}).html('↑'),
				$('<button />').addClass('btn btn-sm btn-primary me-1').attr({id: 'moveDown', onclick: 'category_moveDown("category_' + index + '");', title: '表示順を一つ下げる'}).html('↓'),
				$('<button />').addClass('btn btn-sm btn-primary'     ).attr({id: 'delete',   onclick: 'category_delete(  "category_' + index + '");', title: 'このカテゴリを削除する'}).html('削除')])]));
	category_renumber();
}

/**
 * カテゴリーの表示順を一つ上げる
 * @param row_id 行ＩＤ
 */
function category_moveUp(row_id) {

	let order = parseInt($('table#categories > tbody > tr#' + row_id).find('td:first').html());
	if (order == 1) {
		showAlertDialog('コンテスト管理用', '移動できません');
		return;
	}

	let org = $('table#categories > tbody > tr#' + row_id).remove();
	$($('table#categories > tbody > tr')[order - 2]).before($(org));
	category_renumber();
}

/**
 * カテゴリーの表示順を一つ下げる
 * @param row_id 行ＩＤ
 */
function category_moveDown(row_id) {

	let order = parseInt($('table#categories > tbody > tr#' + row_id).find('td:first').html());
	if (order == $('table#categories > tbody > tr').length) {
		showAlertDialog('コンテスト管理用', '移動できません');
		return;
	}

	let org = $('table#categories > tbody > tr#' + row_id).remove();
	$($('table#categories > tbody > tr')[order - 1]).after($(org));
	category_renumber();
}

/**
 * カテゴリーを削除する
 * @param row_id 行ＩＤ
 */
function category_delete(row_id) {

	let buttons = [
		$('<button />').addClass('btn btn-sm btn-primary').attr({onclick: '$("table#categories > tbody > tr#' + row_id + '").remove();category_renumber();hideAlertDialog();'}).html('はい'),
		$('<button />').addClass('btn btn-sm btn-primary').attr({onclick:                                                                                'hideAlertDialog();'}).html('いいえ')];

	showAlertDialog('コンテスト管理用', '削除しますか？', buttons);
}

/**
 * カテゴリーの一覧を登録する
 */
function category_regist() {

	let data = {
			CALL_AJAX:	'registCategories',
			codes:		[],
			names:		[]};

	let index = 0;
	for (let row of $('table#categories > tbody > tr')) {
		data.codes.push($(row).find('input#code').val());
		data.names.push($(row).find('input#name').val());
		data['bands_' + index] = [];
		for (let band of $(row).find('input[id^=band_]')) {
			if($(band).prop('checked')) {
				data['bands_' + index].push($(band).val());
			}
		}
		index++;
	}

	$.ajax({
		type:			METHOD_TYPE,
		url:			URL_AJAX,
		dataType:		DATA_TYPE,
		data:			data,
		beforeSend:		function (jqXHR) {
			$('div#wait').show();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.RESULTCD == 0) {
			category_list();

		} else {
			showAlertDialog('コンテスト管理用', data.MESSAGE);
			$('div#wait').hide();
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {
		$('div#wait').hide();

	}).always(function () {

	});
}

/**
 * カテゴリーの表示順を振りなおす
 */
function category_renumber() {

	let order = 1;
	for (let row of $('table#categories > tbody > tr')) {
		$(row).children('td:first').html(order++);
	}
}

/* ========================================================================== */
/* 同時提出可否マスター                                                       */
/* ========================================================================== */
function duplicate_list() {

	$.ajax({
		type:			METHOD_TYPE,
		url:			URL_AJAX,
		dataType:		DATA_TYPE,
		data:			{
			CALL_AJAX:	'getDuplicates'},
		beforeSend:		function (jqXHR) {
			$('div#wait').show();
			$('table#duplicates > tbody').empty();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.RESULTCD == 0) {
			// 正常に取得出来ていたら
			categories = {};
			for (let disp_order in data.categories) {
				categories[disp_order] = {
						code:	data.categories[disp_order].code,
						name:	data.categories[disp_order].name}
			}

			// 表を作る
			let keys = Object.keys(categories);
			let tr = $('<tr />').append(
					$('<th />').attr({colspan: '2'}));
			for (let i = keys.length - 1; i >= 0; i--) {
				$(tr).append($('<th />').addClass('mode2').append($('<div />').html(categories[keys[i]].code)));
			}
			$('table#duplicates > tbody').append(tr);

			for (let i = 0; i < keys.length; i++) {
				tr = $('<tr />').append([
					$('<th />').addClass('mode1').html(categories[keys[i]].code),
					$('<th />').addClass('mode1').html(categories[keys[i]].name)]);
				for (let j = 0; j < keys.length - i; j++) {
					let id = categories[keys[i]].code.replace('.', 'r') + '_' + categories[keys[keys.length - j - 1]].code.replace('.', 'r');
					$(tr).append($('<td />').attr({id: id, onclick: 'duplicate_switch(event);'}).addClass('enabled'));
					$('table#duplicates > tbody').append($('<input />').attr({type: 'hidden', id: id, name: id}).val(-1));
				}
				$('table#duplicates > tbody').append(tr);
			}

			for (let i = 0; i < data.duplicates.length; i++) {
				let id = data.duplicates[i].code1 + '_' + data.duplicates[i].code2;
				if (data.duplicates[i].enabled) {
					$('input#' + id).val(1);
					$('table#duplicates > tbody > tr > td#' + id).addClass('ok').html('○');

				} else {
					$('input#' + id).val(0);
					$('table#duplicates > tbody > tr > td#' + id).addClass('ng').html('×');
				}
			}

		} else {
			showAlertDialog('コンテスト管理用', data.MESSAGE);
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {
		$('div#wait').hide();
	});
}

function duplicate_switch(event) {

	let target = $(event.target)[0];
	let id = $(target).prop('id');

	if ($('input#' + id).val() == -1) {
		// 可否が未設定
		$('input#' + id).val(0);
		$(target).addClass('ng').html('×')

	} else {
		// 可否が設定済
		$('input#' + id).val(1 - $('input#' + id).val());
		if ($('input#' + id).val() == 1) {
			$(target).addClass('ok').removeClass('ng').html('○');

		} else {
			$(target).addClass('ng').removeClass('ok').html('×');
		}
	}
}

function duplicate_regist() {

	let data = {
			CALL_AJAX:	'registDuplicates',
			keys:		[],
			values:		[]};

	for (let value of $('table#duplicates > tbody > input')) {
		data.keys.push(  $(value).prop('name'));
		data.values.push($(value).val());
	}

	$.ajax({
		type:			METHOD_TYPE,
		url:			URL_AJAX,
		dataType:		DATA_TYPE,
		data:			data,
		beforeSend:		function (jqXHR) {
			$('div#wait').show();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.RESULTCD == 0) {
			duplicate_list();

		} else {
			showAlertDialog('コンテスト管理用', data.MESSAGE);
			$('div#wait').hide();
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {
		$('div#wait').hide();

	}).always(function () {

	});
}

/* ========================================================================== */
/* マルチプライヤー                                                           */
/* ========================================================================== */
/**
 * マルチプライヤーの一覧を取得
 */
function multi_list() {

	$.ajax({
		type:			METHOD_TYPE,
		url:			URL_AJAX,
		dataType:		DATA_TYPE,
		data:			{
			CALL_AJAX:	'getMultipliers'},
		beforeSend:		function (jqXHR) {
			$('div#wait').show();
			$('table#multipliers > tbody').empty();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.RESULTCD == 0) {
			// 正常に取得出来ていたら
			categories = {};
			for (let disp_order in data.categories) {
				categories[disp_order] = {
						code:	data.categories[disp_order].code,
						name:	data.categories[disp_order].name}
			}

			for (let multi of data.multipliers) {
				multi_add(multi);
			}

		} else {
			showAlertDialog('コンテスト管理用', data.MESSAGE);
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {
		$('div#wait').hide();
	});
}

/**
 * マルチプライヤーの行を追加
 * @param data 行データ
 */
function multi_add(data) {

	if (data === undefined) {
		// 新規追加だったら仮の値
		data = {
				code:		'',
				name:		'',
				point:		'',
				categories:	[]};
	}

	let catCell = $('<td />').addClass('categories');
	for (let disp_order in categories) {
		let category = categories[disp_order];
		let tempCode = category.code.replace('.', 'r');
		$(catCell).append(
			$('<label />').append([
				$('<input />').addClass('form-check-input').attr({type: 'checkbox', id: 'cat_' + tempCode}).prop('checked', (data.categories.indexOf(tempCode) == -1 ? false : true)).val(category.code),
				category.code]));
	}

	let index = $('table#multipliers > tbody > tr').length;
	$('table#multipliers > tbody').append(
		$('<tr />').attr({id: 'multiplier_' + index}).append([
			$('<td />').addClass('code').append(
				$('<input />').addClass('form-control').attr({type: 'text', id: 'code',  maxlength: '6'}).val(data.code)),
			$('<td />').addClass('name').append(
				$('<input />').addClass('form-control').attr({type: 'text', id: 'name'                 }).val(data.name)),
			$('<td />').addClass('point').append(
				$('<input />').addClass('form-control').attr({type: 'text', id: 'point', maxlength: '1', onkeypress: 'return onKeyPress_Integer(event);'}).val(data.point)),
			catCell,
			$('<td />').addClass('buttons align-middle').append(
				$('<button />').addClass('btn btn-sm btn-primary').attr({onclick: 'multi_delete("multiplier_' + index + '");'}).html('削除'))]));
}

/**
 * マルチプライヤーの行を削除
 * @param row_id 行ＩＤ
 */
function multi_delete(row_id) {

	let buttons = [
		$('<button />').addClass('btn btn-sm btn-primary').attr({onclick: '$("table#multipliers > tbody > tr#' + row_id + '").remove();hideAlertDialog();'}).html('はい'),
		$('<button />').addClass('btn btn-sm btn-primary').attr({onclick:                                                             'hideAlertDialog();'}).html('いいえ')];

	showAlertDialog('コンテスト管理用', '削除しますか？', buttons);
}

/**
 * マルチプライヤーの一覧を登録
 * @returns
 */
function multi_regist() {

	let data = {
			CALL_AJAX:	'registMultipliers',
			codes:		[],
			names:		[],
			points:		[]};

	let index = 0;
	for (let row of $('table#multipliers > tbody > tr')) {
		data.codes.push( $(row).find('input#code' ).val());
		data.names.push( $(row).find('input#name' ).val());
		data.points.push($(row).find('input#point').val());
		data['cats_' + index] = [];
		for (let band of $(row).find('input[id^=cat_]')) {
			if($(band).prop('checked')) {
				data['cats_' + index].push($(band).val());
			}
		}
		index++;
	}

	$.ajax({
		type:			METHOD_TYPE,
		url:			URL_AJAX,
		dataType:		DATA_TYPE,
		data:			data,
		beforeSend:		function (jqXHR) {
			$('div#wait').show();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.RESULTCD == 0) {
			multi_list();

		} else {
			showAlertDialog('コンテスト管理用', data.MESSAGE);
			$('div#wait').hide();
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {
		$('div#wait').hide();

	}).always(function () {

	});
}

/* ========================================================================== */
/* 周波数帯ごとの交信時間帯                                                   */
/* ========================================================================== */
/**
 * 時間帯の一覧を取得
 */
function period_list() {

	$.ajax({
		type:			METHOD_TYPE,
		url:			URL_AJAX,
		dataType:		DATA_TYPE,
		data:			{
			CALL_AJAX:	'getPeriods'},
		beforeSend:		function (jqXHR) {
			$('div#wait').show();
			$('table#periods > tbody').empty();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.RESULTCD == 0) {
			// 正常に取得出来ていたら
			for (let freq in bands) {
				$('table#periods > tbody').append(
					$('<tr />').append([
						$('<td />').addClass('band align-middle').html(bands[freq] + '帯'),
						$('<td />').addClass('datetime').append(
							$('<input />').addClass('form-control').attr({type: 'text', id: 'startTime_' + freq, onkeypress: 'return onKeyPress_DateTime(event);', onblur: 'onBlur_DateTime(event);period_check();'}).val('')),
						$('<td />').addClass('datetime').append(
							$('<input />').addClass('form-control').attr({type: 'text', id: 'endTime_'   + freq, onkeypress: 'return onKeyPress_DateTime(event);', onblur: 'onBlur_DateTime(event);period_check();'}).val(''))]));
			}

			for (let i = 0; i < data.periods.length; i++) {
				let period = data.periods[i];
				$('table#periods > tbody').find('input#startTime_' + period.freq).val(period.starttime);
				$('table#periods > tbody').find('input#endTime_'   + period.freq).val(period.endtime);
			}

		} else {
			showAlertDialog('コンテスト管理用', data.MESSAGE);
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {
		$('div#wait').hide();
	});
}

/**
 * 時間帯を登録する
 */
function period_regist() {

	let data = {
			CALL_AJAX:	'registPeriods',
			freqs:		[],
			starttimes:	{},
			endtimes:	{}};

	for (let freq in bands) {
		if ($('table#periods > tbody > tr input#startTime_' + freq).val() != '' || $('table#periods > tbody > tr input#endTime_' + freq).val() != '') {
			data.freqs.push(freq);
			data.starttimes[freq]	= $('table#periods > tbody > tr input#startTime_' + freq).val();
			data.endtimes[freq]		= $('table#periods > tbody > tr input#endTime_'   + freq).val();
		}
	}

	$.ajax({
		type:			METHOD_TYPE,
		url:			URL_AJAX,
		dataType:		DATA_TYPE,
		data:			data,
		beforeSend:		function (jqXHR) {
			$('div#wait').show();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.RESULTCD == 0) {
			period_list();

		} else {
			showAlertDialog('コンテスト管理用', data.MESSAGE);
			$('div#wait').hide();
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {
		$('div#wait').hide();

	}).always(function () {

	});
}

/**
 * 日時をチェックする
 */
function period_check() {

	for (let freq in bands) {
		let start	= $('table#periods > tbody').find('input#startTime_' + freq)[0];
		let end		= $('table#periods > tbody').find('input#endTime_'   + freq)[0];

		if ($(start).hasClass('error') || $(end).hasClass('error')) {
			return;
		}
		if ($(start).val() != '' && $(end).val() == '' ||
			$(start).val() == '' && $(end).val() != '') {
			$(start).addClass('error').prop('title', '開始・終了日時はセットで指定してください');
			$(end).addClass('error').prop('title', '開始・終了日時はセットで指定してください');

		} else {
			$(start).removeClass('error').prop('title', '');
			$(end).removeClass('error').prop('title', '');
		}
	}
}

/* ========================================================================== */
/* モード区分                                                                 */
/* ========================================================================== */
/**
 * モード区分の一覧を取得
 */
function modecat_list() {

	$.ajax({
		type:			METHOD_TYPE,
		url:			URL_AJAX,
		dataType:		DATA_TYPE,
		data:			{
			CALL_AJAX:	'getModecats'},
		beforeSend:		function (jqXHR) {
			$('div#wait').show();
			$('table#modecat > tbody').empty();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.RESULTCD == 0) {
			// 正常に取得出来ていたら
			for (let i = 0; i < data.modecats.length; i++) {
				modecat_add(data.modecats[i]);
			}

		} else {
			showAlertDialog('コンテスト管理用', data.MESSAGE);
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {
		$('div#wait').hide();
	});
}

/**
 * モード区分の行を追加
 * @param data 行データ
 */
function modecat_add(data) {

	if (data === undefined) {
		// 新規追加だったら仮の値
		data = {
				mode:		'',
				category:	'',
				disp:		null};
	}

	let index = $('table#modecat > tbody > tr').length;
	$('table#modecat > tbody').append(
		$('<tr />').attr({id: 'modecat_' + index}).append([
			$('<td />').addClass('mode').append(
				$('<input />').addClass('form-control').attr({type: 'text', maxlength: '10', id: 'mode'}).val(data.mode)),
			$('<td />').addClass('category').append(
				$('<select />').addClass('form-select').attr({id: 'category'}).append([
					$('<option />').prop('selected', (data.category == 'G')).val('G').html('電信'),
					$('<option />').prop('selected', (data.category == 'P')).val('P').html('電話'),
					$('<option />').prop('selected', (data.category == 'D')).val('D').html('デジタル')])),
			$('<td />').addClass('disp').append($('<div />').addClass('input-group').append([
				$('<div />').addClass('input-group-text').append(
					$('<input />').addClass('form-check-input').attr({type: 'checkbox', onclick: 'modecat_enabled(this, "disp_name_' + index + '");'}).prop('checked', (data.disp !== null))),
				$('<input />').addClass('form-control').attr({type: 'text', name: 'disp_name', id: 'disp_name_' + index, maxlength: '10'}).prop('disabled', (data.disp === null)).val(data.disp)])),
			$('<td />').addClass('buttons align-middle').append(
				$('<button />').addClass('btn btn-sm btn-primary').attr({onclick: 'modecat_delete("modecat_' + index + '");'}).html('削除'))]));
}

/**
 * モード区分の行を削除
 * @param row_id 行ＩＤ
 */
function modecat_delete(row_id) {

	let buttons = [
		$('<button />').addClass('btn btn-sm btn-primary').attr({onclick: '$("table#modecat > tbody > tr#' + row_id + '").remove();hideAlertDialog();'}).html('はい'),
		$('<button />').addClass('btn btn-sm btn-primary').attr({onclick:                                                         'hideAlertDialog();'}).html('いいえ')];

	showAlertDialog('コンテスト管理用', '削除しますか？', buttons);
}

/**
 * モード区分の表示有無を設定
 * @param source イベント対象
 * @param dest_id 対象ＩＤ
 */
function modecat_enabled(source, dest_id) {

	$('table#modecat > tbody > tr > td input#' + dest_id).prop('disabled', !$(source).prop('checked'));
}

/**
 * モード区分の一覧を登録
 */
function modecat_regist() {

	let data = {
			CALL_AJAX:	'registModecats',
			modes:		[],
			categories:	[],
			disp_names:	[]};

	for (let row of $('table#modecat > tbody > tr')) {
		data.modes.push($(row).find('input#mode').val());
		data.categories.push($(row).find('select#category').val());
		if ($(row).find('input[type=checkbox]').prop('checked')) {
			data.disp_names.push($(row).find('input[name=disp_name]').val());

		} else {
			data.disp_names.push('');
		}
	}

	$.ajax({
		type:			METHOD_TYPE,
		url:			URL_AJAX,
		dataType:		DATA_TYPE,
		data:			data,
		beforeSend:		function (jqXHR) {
			$('div#wait').show();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.RESULTCD == 0) {
			modecat_list();

		} else {
			showAlertDialog('コンテスト管理用', data.MESSAGE);
			$('div#wait').hide();
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {
		$('div#wait').hide();

	}).always(function () {

	});
}

/* ========================================================================== */
/* 登録クラブ一覧                                                             */
/* ========================================================================== */
/**
 * 登録クラブの一覧を取得
 */
function club_list() {

	$.ajax({
		type:			METHOD_TYPE,
		url:			URL_AJAX,
		dataType:		DATA_TYPE,
		data:			{
			CALL_AJAX:	'getClubs'},
		beforeSend:		function (jqXHR) {
			$('div#wait').show();
			$('table#clubs > tbody').empty();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.RESULTCD == 0) {
			// 正常に取得出来ていたら
			// 支部の一覧を作る
			branches = $('<select />').addClass('form-select').attr({id: 'pref'});
			for (let branch of data.branches) {
				$(branches).append(
					$('<option />').val(branch.code).html(branch.name));
			}
			// 登録クラブの一覧を作る
			for (let i = 0; i < data.clubs.length; i++) {
				club_add(data.clubs[i]);
			}

		} else {
			showAlertDialog('コンテスト管理用', data.MESSAGE);
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {
		$('div#wait').hide();
	});
}

/**
 * 登録クラブの行を追加
 * @param data 行データ
 */
function club_add(data) {

	if (data === undefined) {
		// 新規追加だったら仮の値
		data = {
				pref:	'',
				genre:	'',
				number:	'',
				name:	''};

	} else {
		let temp = data.number.split('-');
		data.pref	= temp[0];
		data.genre	= temp[1];
		data.number	= temp[2];
	}

	let index = $('table#clubs > tbody > tr').length;
	let row = $('<tr />').attr({id: 'clubs_' + index}).append([
		$('<td />').addClass('number').append($('<div />').addClass('input-group').append([
			$(branches).clone(),
			$('<select />').addClass('form-select').attr({id: 'genre'}).append([
				$('<option />').val('1').html('地域'),
				$('<option />').val('2').html('学校'),
				$('<option />').val('3').html('職域'),
				$('<option />').val('4').html('専門')]),
			$('<input />').addClass('form-control text-end').attr({type: 'text', id: 'number', maxlength: '4', onkeypress: 'return onKeyPress_Integer(event);',  onblur: 'onBlur_4V0(event);'}).val(data.number)])),
		$('<td />').addClass('name').append(
			$('<input />').addClass('form-control').attr({type: 'text', id: 'name'}).val(data.name)),
		$('<td />').addClass('buttons align-middle').append(
			$('<button />').addClass('btn btn-sm btn-primary').attr({onclick: 'club_delete("clubs_' + index + '");'}).html('削除'))]);

	if (data.pref == '') {
		// 新規追加だったら支部は未選択
		$(row).find('select#pref').prop('selectedIndex', -1);

	} else {
		// 支部を選択状態にする
		$(row).find('select#pref').val(data.pref);
	}

	if (data.genre == '') {
		// 新規追加だったら区分は未選択
		$(row).find('select#genre').prop('selectedIndex', -1);

	} else {
		// 区分を選択状態にする
		$(row).find('select#genre').val(data.genre);
	}

	$('table#clubs > tbody').append(row);
}

/**
 * 登録クラブの行を削除
 * @param row_id 行ＩＤ
 */
function club_delete(row_id) {

	let buttons = [
		$('<button />').addClass('btn btn-sm btn-primary').attr({onclick: '$("table#clubs > tbody > tr#' + row_id + '").remove();hideAlertDialog();'}).html('はい'),
		$('<button />').addClass('btn btn-sm btn-primary').attr({onclick:                                                       'hideAlertDialog();'}).html('いいえ')];

	showAlertDialog('コンテスト管理用', '削除しますか？', buttons);
}

/**
 * 登録クラブの一覧を登録
 */
function club_regist() {

	let data = {
			CALL_AJAX:	'registClubs',
			numbers:	[],
			names:		[]};

	for (let row of $('table#clubs > tbody > tr')) {
		data.numbers.push(
				$(row).find('select#pref').val() + '-' +
				$(row).find('select#genre').val() + '-' +
				$(row).find('input#number').val());
		data.names.push($(row).find('input#name').val());
	}

	$.ajax({
		type:			METHOD_TYPE,
		url:			URL_AJAX,
		dataType:		DATA_TYPE,
		data:			data,
		beforeSend:		function (jqXHR) {
			$('div#wait').show();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.RESULTCD == 0) {
			club_list();

		} else {
			showAlertDialog('コンテスト管理用', data.MESSAGE);
			$('div#wait').hide();
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {
		$('div#wait').hide();

	}).always(function () {

	});
}

/* ========================================================================== */
/* プリフィクス表示順                                                         */
/* ========================================================================== */
function prefix_list() {

	$.ajax({
		type:			METHOD_TYPE,
		url:			URL_AJAX,
		dataType:		DATA_TYPE,
		data:			{
			CALL_AJAX:	'getPrefixOrders'},
		beforeSend:		function (jqXHR) {
			$('div#wait').show();
			$('table#prefixOrder > tbody').empty();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.RESULTCD == 0) {
			// 正常に取得出来ていたら
			for (let i = 0; i < data.prefixOrders.length; i++) {
				prefix_add(data.prefixOrders[i]);
			}

		} else {
			showAlertDialog('コンテスト管理用', data.MESSAGE);
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {
		$('div#wait').hide();
	});
}

function prefix_add(data) {

	if (data === undefined) {
		// 新規追加だったら仮の値
		data = {
				order:	'',
				prefix:	''};
	}

	let index = $('table#prefixOrder > tbody > tr').length;
	$('table#prefixOrder > tbody').append(
		$('<tr />').attr({id: 'prefix_order_' + index}).append([
			$('<td />').addClass('order align-middle').html(data.order),
			$('<td />').addClass('prefix').append(
				$('<input />').addClass('form-control').attr({type: 'text', id: 'prefix', maxlength: '3', onkeypress: 'return onKeyPress_prefix(event);', onblur: 'onBlur_toUpperCase(event);'}).val(data.prefix)),
			$('<td />').addClass('buttons align-middle').append([
				$('<button />').addClass('btn btn-sm btn-primary'     ).attr({id: 'moveUp',   onclick: 'prefix_moveUp(  "prefix_order_' + index + '");', title: '表示順を一つ上げる'}).html('↑'),
				$('<button />').addClass('btn btn-sm btn-primary me-1').attr({id: 'moveDown', onclick: 'prefix_moveDown("prefix_order_' + index + '");', title: '表示順を一つ下げる'}).html('↓'),
				$('<button />').addClass('btn btn-sm btn-primary'     ).attr({id: 'delete',   onclick: 'prefix_delete(  "prefix_order_' + index + '");', title: 'このプリフィクスを削除する'}).html('削除')])]));
	prefix_renumber();
}

/**
 * プリフィクスの表示順を一つ上げる
 * @param row_id 行ＩＤ
 */
function prefix_moveUp(row_id) {

	let order = parseInt($('table#prefixOrder > tbody > tr#' + row_id).find('td:first').html());
	if (order == 1) {
		showAlertDialog('コンテスト管理用', '移動できません');
		return;
	}

	let org = $('table#prefixOrder > tbody > tr#' + row_id).remove();
	$($('table#prefixOrder > tbody > tr')[order - 2]).before($(org));
	prefix_renumber();
}

/**
 * プリフィクスの表示順を一つ下げる
 * @param row_id 行ＩＤ
 */
function prefix_moveDown(row_id) {

	let order = parseInt($('table#prefixOrder > tbody > tr#' + row_id).find('td:first').html());
	if (order == $('table#categories > tbody > tr').length) {
		showAlertDialog('コンテスト管理用', '移動できません');
		return;
	}

	let org = $('table#prefixOrder > tbody > tr#' + row_id).remove();
	$($('table#prefixOrder > tbody > tr')[order - 1]).after($(org));
	prefix_renumber();
}

/**
 * プリフィクスを削除する
 * @param row_id 行ＩＤ
 */
function prefix_delete(row_id) {

	let buttons = [
		$('<button />').addClass('btn btn-sm btn-primary').attr({onclick: '$("table#prefixOrder > tbody > tr#' + row_id + '").remove();prefix_renumber();hideAlertDialog();'}).html('はい'),
		$('<button />').addClass('btn btn-sm btn-primary').attr({onclick:                                                                               'hideAlertDialog();'}).html('いいえ')];

	showAlertDialog('コンテスト管理用', '削除しますか？', buttons);
}

/**
 * プリフィクスの一覧を登録する
 */
function prefix_regist() {

	let data = {
			CALL_AJAX:	'registPrefixOrders',
			prefixes:	[]};

	for (let row of $('table#prefixOrder > tbody > tr')) {
		data.prefixes.push($(row).find('input#prefix').val());
	}

	$.ajax({
		type:			METHOD_TYPE,
		url:			URL_AJAX,
		dataType:		DATA_TYPE,
		data:			data,
		beforeSend:		function (jqXHR) {
			$('div#wait').show();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.RESULTCD == 0) {
			prefix_list();

		} else {
			showAlertDialog('コンテスト管理用', data.MESSAGE);
			$('div#wait').hide();
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {
		$('div#wait').hide();

	}).always(function () {

	});
}

function prefix_renumber() {

	let order = 1;
	for (let row of $('table#prefixOrder > tbody > tr')) {
		$(row).children('td:first').html(order++);
	}
}

