/**
 * 共通Javascriptファイル
 * @author JJ4KME
 */
let defaultButtons = [{text: "ＯＫ", click: function(e) {e.stopPropagation();$(this).dialog('close');}}];
let queryParams = parseQuery();

$(window).on('load', function(e) {

	createDialog();
});

/**
 * ダイアログを作る
 */
function createDialog() {

	$('div#dialog').remove();
	$('body').append(
		$('<div />').attr({id: 'dialog', tabindex: '-1', role: 'dialog', 'aria-hidden': true, 'data-bs-backdrop': 'static', 'data-bs-keyboard': true}).addClass('modal').append(
			$('<div />').attr({role: 'document'}).addClass('modal-dialog modal-dialog-centered').append(
				$('<div />').addClass('modal-content').append([
					$('<div />').addClass('modal-header').append(
						$('<span />').addClass('modal-title')),
					$('<div />').addClass('modal-body'),
					$('<div />').addClass('modal-footer')]))));

	new bootstrap.Modal('div#dialog');
}

/**
 * アラートダイアログを表示する
 * @param title タイトル
 * @param message メッセージ
 * @param buttons ボタン定義
 */
function showAlertDialog(title, message, buttons) {

	if (buttons === undefined) {
		buttons = [
			$('<button />').addClass('btn btn-sm btn-primary').attr({onclick: 'hideAlertDialog();'}).html('ＯＫ')];
	}

	// ダイアログのタイトルを設定
	$('div#dialog span.modal-title').html(title);
	// ダイアログの本文を設定
	$('div#dialog div.modal-body').html(message);
	// ダイアログのボタンを設定
	$('div#dialog div.modal-footer').empty();
	$('div#dialog div.modal-footer').append(buttons);

	$('div#dialog').modal('show');
}

function hideAlertDialog() {

	$('div#dialog').modal('hide');
}

/**
 * ログイン中か調べる
 * @returns ログイン中だったらtrue、ログインしていなかったらfalse
 */
function checkLoggedIn() {

	let cookies = Cookie.get();
	if (cookies === null) {
		return false;

	} else if (!cookies.hasOwnProperty('schema') || !cookies.hasOwnProperty('user_id')) {
		return false;
	}

	return true;
}

/**
 * クエリー文字列を分解する
 * @returns パースされたクエリー文字列
 */
function parseQuery() {

	let result = {};
	if (location.search != '') {
		let source = location.search.substring(1);
		let temp = [];
		if (source.indexOf('&') == -1) {
			temp[0] = source;

		} else {
			temp = source.split('&');
		}

		for (let i = 0; i < temp.length; i++) {
			if (temp[i].indexOf('=') == -1) {
				result[temp[i]] = null;

			} else {
				temp2 = temp[i].split('=');
				result[temp2[0]] = temp2[1];
			}
		}
	}

	return result;
}

let Cookie = {
	get:	function() {
		if (document.cookie == '') {
			return null;

		} else {
			let result = {};
			let source = document.cookie.split(';');
			for (let i = 0; i < source.length; i++) {
				let temp = source[i].split('=');
				result[temp[0].trim()] = temp[1];
			}
			return result;
		}
	},
	set:	function(data) {
		for (let key in data) {
			document.cookie = encodeURIComponent(key) + '=' + encodeURIComponent(data[key]);
		}
	}
};
