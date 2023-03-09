/**
 * 成績表示　JavaScriptファイル
 */
const METHOD_TYPE	= 'POST';
const DATA_TYPE		= 'json';
const URL_AJAX		= 'showResult.php';
let schema;
let sumid;

/**
 * 読み込み完了時の処理
 * @param e イベント
 */
$(window).on('load', function(e) {

	let temp = location.search.substring(1).split('&');
	schema	= temp[0];
	sumid	= temp[1];

	if (checkLoggedIn()) {
		// 管理者でログインされていたら確定して閉じるボタンを表示
		$('button#fixScore').show();

	} else {
		// 一般人だったら閉じるボタンを表示
		$('button#close').show();
	}

	showDetail();
});

/**
 * 詳細表示
 */
function showDetail() {

	$.ajax({
		type:		METHOD_TYPE,
		url:		URL_AJAX,
		dataType:	DATA_TYPE,
		data:		{
			SCHEMA:		schema,
			CALL_AJAX:	'showDetail',
			sumid:		sumid},
		beforeSend:	function (jqXHR) {
			$('div#wait').show();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.success) {
			let multipliers = {};
			let category_name = '';
			for (let category of data.categories) {
				if (category.code == data.summary.category) {
					category_name = category.name;
				}
			}
			let errors = data.errors;

			if(data.ADMIN) {
				$('button#fixScore').show();
			} else {
				$('button#fixScore').hide();
			}

			// サマリーを作る
			$('table#summary td#owner').html(data.summary.callsign);
			$('table#summary td#category').empty();
			$('table#summary td#category').append(
				$('<span />').attr({title: category_name}).html(data.summary.category));
			if (data.scores.hasOwnProperty('0')) {
				$('table#summary td#numsqo').html(data.scores[0].numqso);
				$('table#summary td#point').html(data.scores[0].point);
				$('table#summary td#multi').html(data.scores[0].multi);
				$('table#summary td#total').html(data.scores[0].score);

			} else {
				$('table#summary td#numsqo').empty();
				$('table#summary td#point').empty();
				$('table#summary td#multi').empty();
				$('table#summary td#total').empty();
			}

			// エラー詳細を作る
			$('table#errors > tbody').empty();
			for (let code in errors) {
				let name = errors[code];
				$('table#errors > tbody').append([
					$('<tr />').append([
						$('<td />').addClass('code').html(code),
						$('<td />').addClass('desc').html(name),
						$('<td />').addClass('count').attr({id: 'count_' + code})])]);
			}

			// 得点表を作る
			$('table#scores > tbody').empty();
			for (let freq in data.bands) {
				let name = data.bands[freq];
				$('table#scores > tbody').append(
					$('<tr />').append([
						$('<td />').addClass('band'  ).html(name + ' MHz'),
						$('<td />').addClass('numsqo').attr({id: 'numsqo_' + freq}),
						$('<td />').addClass('point' ).attr({id: 'point_'  + freq}),
						$('<td />').addClass('multi' ).attr({id: 'multi_'  + freq}),
						$('<td />').addClass('total' ).attr({id: 'total_'  + freq})]));
			}

			// スコアを記入
			for (let freq in data.scores) {
				let score = data.scores[freq];
				$('table#scores td#numsqo_' + freq).html(score.numqso);
				$('table#scores td#point_'  + freq).html(score.point);
				$('table#scores td#multi_'  + freq).html(score.multi);
				$('table#scores td#total_'  + freq).html(score.score);
			}

			// 運用者リストを記入
			$('td#oplist').html(data.summary.multioplist);
			// コメントを記入
			$('td#comments').html(data.summary.comments);

			// ログを作る
			$('table#body > tbody').empty();
			for (let i = 0; i < data.summary.logData.length; i++) {
				let logData = data.summary.logData[i];

				if (logData.point == 0) {
					logData.multi = '';

				} else {
					if (multipliers[logData.freq] === undefined) {
						multipliers[logData.freq] = [];
					}
					if (multipliers[logData.freq].indexOf(logData.recvmulti) == -1 && logData.error != 'RR' && logData.error != 'MR') {
						logData.multi = logData.recvmulti;
						multipliers[logData.freq].push(logData.recvmulti);

					} else {
						logData.multi = '';
					}
				}

				$('table#body > tbody').append(
					$('<tr />').append([
						$('<td />').addClass('datetime').append(
							$('<time />').html(logData.datetime)),
						$('<td />').addClass('callsign').html(logData.callsign),
						$('<td />').addClass('freq').html(data.bands[logData.freq]),
						$('<td />').addClass('mode').html(logData.mode),
						$('<td />').addClass('number').append([
							$('<span />').addClass('rst').html(logData.sentrst),
							$('<span />').addClass('multi').html(logData.sentmulti)]),
						$('<td />').addClass('number').append([
							$('<span />').addClass('rst').html(logData.recvrst),
							$('<span />').addClass('multi').html(logData.recvmulti)]),
						$('<td />').addClass('multi').html(logData.multi),
						$('<td />').addClass('point').html(logData.point),
						$('<td />').addClass('error').append(
							$('<span />').attr({title: errors[logData.error]}).html(logData.error))]));

				// エラーだったらエラー詳細に加算
				if (logData.error != '') {
					$('table#errors td#count_' + logData.error).html(function(index, oldHtml) {
						return (oldHtml == '' ? 0 : parseInt(oldHtml)) + 1;
					});
				}
			}

		} else {
			showAlertDialog('オールＪＡ４コンテスト', data.message);
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {

	}).always(function () {
		$('div#wait').hide();
	});
}

/**
 * 成績確定
 */
function fixScore() {

	$.ajax({
		type:		METHOD_TYPE,
		url:		URL_AJAX,
		dataType:	DATA_TYPE,
		data:		{
			SCHEMA:		schema,
			CALL_AJAX:	'fixScore',
			sumid:		sumid},
		beforeSend:	function (jqXHR) {
			$('div#wait').show();
		}
	}).done(function (data, textStatus, jqXHR) {
		if (data.success) {
			// 正常に終了していたら
			window.close();

		} else {
			showAlertDialog('オールＪＡ４コンテスト', data.message);
			$('div#wait').hide();
		}

	}).fail(function (jqXHR, textStatus, errorThrown) {
		$('div#wait').hide();

	}).always(function () {

	});
}
