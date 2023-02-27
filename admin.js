/**
 * コンテスト管理画面　JavaScriptファイル
 @author JJ4KME
 */
const METHOD_TYPE	= 'POST';
const DATA_TYPE		= 'json';
const URL_AJAX		= 'admin.php';
let schema;

/**
 * 読み込み完了時の処理
 * @param e イベント
 */
$(window).on('load', function(e) {

	schema = location.search.slice(1);

	// ユーザーＩＤ欄でキーが押された
	$('div#login input#user').keydown(function(e) {
		if (e.key == 'Enter') {
			// ENTERだったらログインが押されたことにする
			$('button#login').click();
		}
	});

	// パスワード欄でキーが押された
	$('div#login input#pass').keydown(function(e) {
		if (e.key == 'Enter') {
			// ENTERだったらログインが押されたことにする
			$('button#login').click();
		}
	});

	// ログインボタンが押された
	$('div#login button#login').click(function(e) {
		$('div#login input#user').removeClass('is-invalid');
		$('div#login input#pass').removeClass('is-invalid');

		let message = '';
		if ($('div#login input#user').val() == '') {
			$('div#login input#user').addClass('is-invalid');
			message = message + 'ユーザーＩＤを入力してください。';
		}

		if ($('div#login input#pass').val() == '') {
			$('div#login input#pass').addClass('is-invalid');
			message = message + 'パスワードを入力してください。';
		}

		if (message == '') {
			login($('div#login input#user').val(), $('div#login input#pass').val());

		} else {
			$('div#message').html(message).show();
		}
	});

	// ログアウトボタンが押された
	$('button#logout').click(function(e) {
		logout();
	});

	// 戻るボタンが押された
	$('div#login button#back').click(function(e) {
		location.href = 'index.html';
	});

	new bootstrap.Modal('div#login');
	new bootstrap.Modal('div#password');

	if (checkLoggedIn()) {
		// ログインされていたら
		get_deadline();
		get_user();
		$('div#wait').hide();

	} else {
		initialize();
		$('div#login').modal('show');
	}
});

/**
 * ページ変更時の処理
 * @param e イベント
 */
$(window).on('unload', function(e) {

	$('div#login').modal('dispose');
	$('div#password').modal('dispose');
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
			$('select#schema').empty();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.success) {
			// 複数件だったら
			for (let schema of data.schemas) {
				$('select#schema').append(
					$('<option />').val(schema.schema_name).html(schema.description));
			}
			$('select#schema').prop('selectedIndex', -1);
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {
		$('div#wait').hide();
	});
}

/**
 * ログイン
 * @param user ユーザーＩＤ
 * @param pass パスワード
 */
function login(user, pass) {

	$.ajax({
		type:		METHOD_TYPE,
		url:		URL_AJAX,
		dataType:	DATA_TYPE,
		data:		{
			CALL_AJAX:		'login',
			schema:			$('select#schema').val(),
			PHP_AUTH_USER:	user,
			PHP_AUTH_PASS:	pass},
		beforeSend:	function (jqXHR) {
			$('div#message').html('').hide();
		}
	}).done(function (data, textStatus, jqXHR) {
		// ログインに成功していたら
		get_deadline();
		get_user();
		$('div#login').modal('hide');

	}).fail(function (jqXHR, textStatus, errorThrown) {
		if (jqXHR.status == 401) {
			$('div#message').html(jqXHR.responseJSON.message).show();
		}

	}).always(function () {

	});
}

/**
 * ログアウト
 */
function logout() {

	$.ajax({
		type:		METHOD_TYPE,
		url:		URL_AJAX,
		dataType:	DATA_TYPE,
		data:		{
			CALL_AJAX:		'logout'},
		beforeSend:	function (jqXHR) {

		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.success) {
			location.reload();
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {

	});
}

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
		if (data.success) {
			// 正常に取得できていたら
			$('a#navbarUserMenu').html(data.user.user_id);
			$('input#user_id').val(data.user.user_id);
			$('span#contest_name').html(data.schema.description);
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {
		initialize();
		$('div#login').modal('show');

	}).always(function () {

	});
}

function change_password() {

	$('div#password').modal('show');
}

function change_password_execute() {

	$.ajax({
		type:		METHOD_TYPE,
		url:		URL_AJAX,
		dataType:	DATA_TYPE,
		data:		{
			CALL_AJAX:		'change_password',
			callsign:		$('td#callsign').html(),
			old_password:	$('input#old_password').val(),
			new_password:	$('input#new_password1').val()},
		beforeSend: function (jqXHR) {console.log(jqXHR);
			if ($('input#new_password1').val() != $('input#new_password2').val()) {
				showAlertDialog('コンテスト管理用', '確認用パスワードが違います');
				return false;
			}
			return true;
		}
	}).done(function (data, textStatus, jqXHR) {
		showAlertDialog('コンテスト管理用', 'パスワードを変更しました<br />一旦ログアウトします', [
			$('<button />').addClass('btn btn-sm btn-primary').attr({onclick: 'location.href="admin.html"'}).html('ＯＫ')]);

	}).fail(function (jqXHR, textStatus, errorThrown) {
		if (jqXHR.status == 401) {
			showAlertDialog('コンテスト管理用', '旧パスワードが違います');
		}

	}).always(function () {

	});
}

function get_deadline() {

	$.ajax({
		type:		METHOD_TYPE,
		url:		URL_AJAX,
		dataType:	DATA_TYPE,
		data:		{
			CALL_AJAX:	'get_deadline'},
		beforeSend:	function (jqXHR) {
			$('div#wait').show();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.success) {
			// 正常に取得できていたら
			$('input#deadline1').val(data.deadlines.deadline1);
			$('input#deadline2').val(data.deadlines.deadline2);

		} else {
			showAlertDialog('コンテスト管理用', data.message);
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {
		$('div#wait').hide();
	});
}

function set_deadline() {

	$.ajax({
		type:		METHOD_TYPE,
		url:		URL_AJAX,
		dataType:	DATA_TYPE,
		data:		{
			CALL_AJAX:	'set_deadline',
			deadline1:	$('input#deadline1').val(),
			deadline2:	$('input#deadline2').val()},
		beforeSend: function (jqXHR) {
			$('div#wait').show();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.success) {
			showAlertDialog('コンテスト管理用', '提出期限を設定しました');

		} else {
			showAlertDialog('コンテスト管理用', data.message);
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {
		$('div#wait').hide();
	});
}

