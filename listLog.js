/**
 * ログ提出状況　JavaScriptファイル
 * @author JJ4KME
 */
const METHOD_TYPE	= 'POST';
const DATA_TYPE		= 'json';
const URL_AJAX		= 'listLog.php';
let schema;
let timer;

/**
 * 読み込み完了後の処理
 * @param e イベント
 */
$(window).on('load', function(e) {

	schema	= location.search.slice(1);

	if (checkLoggedIn()) {
		// 管理者でログインされていたら
		$('table#list > thead > tr').prepend(
			$('<th />').attr({id: 'check'}));

	} else {
		// 一般人だったら
		Cookie.set({schema: location.search.slice(1)});
	}

	$('div#password input#password').keydown(function(e) {
		if (e.key == 'Enter') {
			// ENTERだったらＯｋが押されたことにする
			$('div#password button#btnOK').click();
		}
	});

	// 提出状況のソート機能
	$('table#list thead th div.sortable').append([
		$('<span />').addClass('asc').html('▲'),
		$('<span />').addClass('desc').html('▼')]);
	$('table#list thead th div.sortable').click(function(e) {
		let target = $(e.target).parent();

		if ($(target).hasClass('sort_none')) {
			// ソート無しだったら
			$('table#list thead th div.sortable').removeClass('sort_asc').removeClass('sort_desc').addClass('sort_none');
			$(target).removeClass('sort_none').addClass('sort_asc');

		} else if ($(target).hasClass('sort_asc')) {
			// 昇順だったら
			$(target).removeClass('sort_asc').addClass('sort_desc');

		} else if ($(target).hasClass('sort_desc')) {
			// 降順だったら
			$(target).removeClass('sort_desc').addClass('sort_none');
		}

		listLogs();
	});

	new bootstrap.Modal('div#password');

	initialize();
});

$(window).on('unload', function(e) {
	clearTimeout(timer);
});

/**
 * 初期処理
 */
function initialize() {

	$.ajax({
		type:		METHOD_TYPE,
		url:		URL_AJAX,
		dataType:	DATA_TYPE,
		data:		{
			CALL_AJAX:	'initialize'},
		beforeSend:	function (jqXHR) {
			$('div#wait').show();
			$('span#contest_name').empty();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.success) {
			// 正常に取得できていたら
			$('span#contest_name').html(data.schemas[0].description);

			listLogs();
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {

	});
}

/**
 * ログ提出状況を取得
 */
function listLogs() {

	let sort_keys = [];
	for (let elem of $('table#list thead th div.sort_asc')) {
		sort_keys.push($(elem).data('key') + ' ASC');
	}
	for (let elem of $('table#list thead th div.sort_desc')) {
		sort_keys.push($(elem).data('key') + ' DESC');
	}
	if (sort_keys.length == 0) {
		sort_keys = undefined;
	}

	$.ajax({
		type:		METHOD_TYPE,
		url:		URL_AJAX,
		dataType:	DATA_TYPE,
		data:		{
			SCHEMA:		schema,
			CALL_AJAX:	'listLogs',
			sort_keys:	sort_keys},
		beforeSend:	function (jqXHR) {
			$('div#wait').show();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.success) {
			$('table#list > tbody').empty();
			$('table#list > tfoot').empty();

			for (i = 0; i < data.LOGS.length; i++) {
				let logData = data.LOGS[i];
				let row = $('<tr />').addClass('align-middle').append([
					$('<td />').addClass('callsign').html(logData.callsign),
					$('<td />').addClass('category').html(logData.category),
					$('<td />').addClass('uploadtime').html(logData.uploadtime),
					$('<td />').addClass('status').attr({id: 'status_' + logData.sumid}).html(logData.status_name)]);
				if (checkLoggedIn()) {
					// 管理者でログインされていたら
					$(row).prepend(
						$('<td />').addClass('check').append(
							$('<input />').addClass('form-check-input').attr({type: 'checkbox', name: 'collate_target'}).val(logData.sumid)));
					$(row).append(
						$('<td />').addClass('buttons').append([
							$('<button />').addClass('btn btn-sm btn-primary border border-primary me-1').attr({onclick: "showDetail('"  + logData.sumid + "');"}).html('審査'),
							$('<button />').addClass('btn btn-sm btn-primary border border-primary     ').attr({onclick: "setPassword('" + logData.sumid + "');"}).html('パスワードを付ける')]));

				} else if (logData.status == 9 && !logData.empty_password) {
					// 一般人で成績が確定してパスワードが入っていたら
					$(row).append(
						$('<td />').addClass('buttons').append([
							$('<button />').addClass('btn btn-sm btn-primary border border-primary').attr({onclick: "showDetailUser('" + logData.sumid + "');"}).html('成績表示')]));

				} else {
					$(row).append(
						$('<td />').addClass('buttons').append([
							$('<button />').addClass('btn btn-sm btn-light border border-light').attr({title: "成績閲覧用パスワードが設定されていません"}).prop('disabled', true).html('&nbsp;')]));
				}
				$('table#list > tbody').append(row);
			}

			if (checkLoggedIn()) {
				// 管理者でログインされていたら
				$('table#list > tfoot').append(
					$('<tr />').append(
						$('<td />').attr({colspan: 6}).append([
							'↑↑選択されたサマリーを',
							$('<button />').addClass('btn btn-sm btn-primary border border-primary me-1').attr({onclick: 'clearCollate();'}).html('照合クリア'),
							$('<button />').addClass('btn btn-sm btn-primary border border-primary me-1').attr({onclick: 'startCollate();'}).html('照合開始'),
							$('<button />').addClass('btn btn-sm btn-primary border border-primary     ').attr({onclick: 'selectAll();'   }).html('全て選択')])));
			}

			if (!checkLoggedIn()) {
				getComment();
			}
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {
		$('div#wait').hide();
	});
}

function getComment() {

	$.ajax({
		type:		METHOD_TYPE,
		url:		URL_AJAX,
		dataType:	DATA_TYPE,
		data:		{
			SCHEMA:		schema,
			CALL_AJAX:	'getComment'},
		beforeSend:	function(jqXHR) {
			clearTimeout(timer);
			$('div#comment > div#comment').empty();
			$('div#comment > div#callsign').empty();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.success) {
			if (data.COMMENT !== null) {
				$('div#comment > div#body').html(data.COMMENT.comments);
				$('div#comment > div#callsign').html(data.COMMENT.callsign);
			}

			timer = setTimeout(function() {
				getComment();}, 5000);
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {

	});
}

function showDetailUser(sumid) {

	$('div#password div.modal-footer').empty();
	$('div#password div.modal-footer').append([
		$('<button />').addClass('btn btn-sm btn-primary').attr({id: "btnOK",     onclick: "showDetailVerify('" + sumid + "');$('div#password').modal('hide');"}).html('ＯＫ'),
		$('<button />').addClass('btn btn-sm btn-primary').attr({id: "btnCancel", onclick: "                                  $('div#password').modal('hide');"}).html('閉じる')]);

	$('div#password').modal('show');
	$('div#password input#password').val('').focus();
}

function showDetailVerify(sumid) {

	$.ajax({
		type:		METHOD_TYPE,
		url:		URL_AJAX,
		dataType:	DATA_TYPE,
		data:		{
			SCHEMA:		schema,
			CALL_AJAX:	'verifyPassword',
			sumid:		sumid,
			password:	$('div#password input#password').val()},
		beforeSend:	function (jqXHR) {
			$('div#wait').show();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.success) {
			// パスワードが合っていたら
			showDetail(data.sumid);

		} else {
			if (data.code == -1) {
				// パスワードが間違っていたら
				showAlertDialog('オールＪＡ４コンテスト', 'パスワードが違います');
				$('div#wait').hide();

			} else {
				showAlertDialog('オールＪＡ４コンテスト', data.message);
				$('div#wait').hide();
			}
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {
		$('div#wait').hide();
	});
}

function showDetail(sumid) {

	window.open('showResult.html?' + schema + '&' + sumid, 'detail_' + sumid);
}

/**
 * 照合結果をクリアする
 */
function clearCollate() {

	let data = {
			SCHEMA:		schema,
			CALL_AJAX:	'clearCollate',
			sumids:		[]};

	let source = $('table#list > tbody input[name=collate_target]:checked');
	for (i = 0; i < source.length; i++) {
		data.sumids.push($(source[i]).val());
	}

	if (data.sumids.length == 0) {
		showAlertDialog('オールＪＡ４コンテスト', '照合結果をクリアしたいサマリーを選択してください');
		return;
	}

	$.ajax({
		type:		METHOD_TYPE,
		url:		URL_AJAX,
		dataType:	DATA_TYPE,
		data:		data,
		beforeSend:	function (jqXHR) {
			$('div#wait').show();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.success) {
			// 正常に終了していたら
			listLogs();

		} else {
			showAlertDialog('オールＪＡ４コンテスト', data.message);
			$('div#wait').hide();
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {
		$('div#wait').hide();
	});
}

/**
 * ログの照合を開始する
 */
function startCollate() {

	let data = {
			SCHEMA:		schema,
			CALL_AJAX:	'startCollate',
			sumids:		[]};

	let source = $('table#list > tbody input[name=collate_target]:checked');
	for (i = 0; i < source.length; i++) {
		data.sumids.push($(source[i]).val());
	}

	if (data.sumids.length == 0) {
		showAlertDialog('オールＪＡ４コンテスト', 'ログを照合したいサマリーを選択してください');
		return;
	}

	$.ajax({
		type:		METHOD_TYPE,
		url:		URL_AJAX,
		dataType:	DATA_TYPE,
		data:		data,
		beforeSend:	function (jqXHR) {
			$('div#wait').show();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.success) {
			// 正常に終了していたら
			listLogs();

		} else {
			showAlertDialog('オールＪＡ４コンテスト', data.message);
			$('div#wait').hide();
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {
		$('div#wait').hide();

	}).always(function () {

	});
}

function selectAll() {

	$('table#list > tbody input[name=collate_target]').prop('checked', true);
}

function setPassword(sumid) {

	$('div#password div.modal-footer').empty();
	$('div#password div.modal-footer').append([
		$('<button />').addClass('btn btn-sm btn-primary').attr({id: "btnOK",     onclick: "setPassword_exec('" + sumid + "');$('div#password').modal('hide');"}).html('ＯＫ'),
		$('<button />').addClass('btn btn-sm btn-primary').attr({id: "btnCancel", onclick: "                                  $('div#password').modal('hide');"}).html('閉じる')]);

	$('div#password').modal('show');
	$('div#password input#password').val('').focus();
}

function setPassword_exec(sumid) {

	$.ajax({
		type:		METHOD_TYPE,
		url:		URL_AJAX,
		dataType:	DATA_TYPE,
		data:		{
			SCHEMA:		schema,
			CALL_AJAX:	'setPassword',
			sumid:		sumid,
			password:	$('div#password input#password').val()},
		beforeSend:	function (jqXHR) {
			$('div#wait').show();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.success) {
			// 正常に終了していたら
			showAlertDialog('オールＪＡ４コンテスト', '成績閲覧用パスワードを設定しました');

		} else {
			showAlertDialog('オールＪＡ４コンテスト', data.message);
			$('div#wait').hide();
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {
		$('div#wait').hide();
	});
}
