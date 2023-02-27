<!-- 課題
１）成績確定前は順位を表示しない
-->
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1" />
<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
<meta name="theme-color" content="#215591">
<link rel="stylesheet" type="text/css" href="common.css" />
<link rel="stylesheet" type="text/css" href="ranking.css" />
<?php
$global = json_decode(file_get_contents("{$_SERVER['QUERY_STRING']}.json"), true);
?>
<title><?= $global['title'] ?> - 順位表</title>
</head>
<body>
<?php
include_once 'common.php';
include_once 'libs/MyPDO.php';
include_once 'classes/Summary.php';
include_once 'classes/Score.php';
include_once 'classes/Band.php';

if (empty($_SERVER['QUERY_STRING'])) {
	$_SERVER['QUERY_STRING'] = SCHEMA_CURRENT;
}

$resultAll = null;
$resultClub = null;
try {
	$db = new MyPDO('ja4test');
	$db->query("SET search_path TO '{$_SERVER['QUERY_STRING']}'");

	$stmScore = $db->prepare('SELECT SUM.sumid, 0 AS freq, SUM(SC.numqso) AS numqso, SUM(SC.point) AS point, SUM(SC.multi) AS multi FROM summary SUM LEFT JOIN m_bands MB ON MB.category = SUM.category INNER JOIN scores SC ON SC.sumid = SUM.sumid AND SC.freq = MB.freq WHERE SUM.sumid = :sumid GROUP BY SUM.sumid');

	$SQL = <<<EOF
SELECT
	MC.disporder,
	MC.code AS category,
	MC.name,
	SUM.sumid,
	SUM.callsign,
--	SC.numqso,
	SC.point,
	SC.multi,
	SC.score,
	LAST.lasttime
FROM
	m_categories MC
	LEFT JOIN summary SUM
		ON	SUM.category	= MC.code
	LEFT JOIN scores SC
		ON	SC.sumid		= SUM.sumid
		AND	SC.freq			= 0
	LEFT JOIN (
		SELECT
			sumid,
			MAX(datetime)	AS lasttime
		FROM
			logdata
		WHERE
			error		IS NULL
		OR	error		<> 'CL'
		GROUP BY
			sumid
	) LAST
		ON LAST.sumid	= SUM.sumid
ORDER BY
	MC.disporder,
	SC.score DESC,
	LAST.lasttime
EOF;
	$stmt = $db->query($SQL);

	while ($record = $stmt->fetch()) {
		if (!isset($resultAll[$record['disporder']])) {
			$resultAll[$record['disporder']] = array(
				'code'		=> $record['category'],
				'name'		=> $record['name'],
				'members'	=> array());
		}

		if ($record['callsign'] !== NULL) {
			$score = getScore($db, $record['sumid']);

			$resultAll[$record['disporder']]['members'][] = array(
				'callsign'	=> $record['callsign'],
#				'numqso'	=> $record['numqso'],
				'numqso'	=> $score->numqso,
				'point'		=> $record['point'],
				'multi'		=> $record['multi'],
				'score'		=> $record['score'],
				'lasttime'	=> (new DateTime($record['lasttime']))->format('m/d H:i'));
		}
	}

	$stmt->closeCursor();

	$SQL = <<<EOF
SELECT
	MC.number,
	MC.name,
	SUM(SC.score) AS score
FROM
	common.m_clubs MC
	INNER JOIN summary SUM
		ON	SUM.regclubnumber	= MC.number
		AND	SUM.regclubnumber	IS NOT NULL
	INNER JOIN scores SC
		ON	SC.sumid	= SUM.sumid
		AND	SC.freq		= 0
	LEFT JOIN m_categories CAT
		ON	CAT.code	= sum.category
GROUP BY
	MC.number,
	MC.name
ORDER BY
	score DESC
EOF;
	$stmt = $db->query($SQL);

	$rank = 1;
	while ($record = $stmt->fetch()) {
		$resultClub[$record['number']] = array(
			'rank'		=> $rank++,
			'name'		=> $record['name'],
			'score'		=> $record['score']);
	}

	$stmt->closeCursor();

} catch (PDOException $pe) {
	error_log($pe->getMessage());
	$resultAll = false;
	$resultClub = false;

} finally {
	$db = null;
}
?>
<div id="header">
	<p><?= $global['title'] ?> 順位表</p>
</div>
<?php foreach ($global['official_comment'] as $comment) { ?>
<p><?= $comment ?></p>
<?php } ?>
<p>全体の順位表は以下の通りです。黄色の順位は入賞局です</p>
<p>得点が同じ場合、最終交信日時の早い局が上位となります</p><br />
<?php foreach ($resultAll as $disp_order => $category) { ?>
<table id="all">
	<thead>
		<tr>
			<th class="category_name" colspan="7"><?= $category['name'] ?></th>
		</tr>
		<tr>
			<th class="rank"    >順位</th>
			<th class="callsign">コールサイン</th>
			<th class="numqso"  ><?= (preg_match('/SWL$/', $category['code']) ? '受信数' : 'QSO数') ?></th>
			<th class="point"   >点数</th>
			<th class="multi"   >マルチ</th>
			<th class="score"   >得点</th>
			<th class="lasttime">最終<?= (preg_match('/SWL$/', $category['code']) ? '受信' : '交信') ?>日時</th>
		</tr>
	</thead>
	<tbody>
<?php if (empty($category['members'])) { ?>
		<tr><td colspan="7">参加なし</td></tr>
<?php } else { ?>
<?php
	$prise = 3;
	if ($category['code'] == 'CHL') {
		$prise = 0;

	} else if (count($category['members']) <= 10) {
		$prise = ceil(count($category['members']) / 5);
	}
?>
<?php $oldScore	= NULL; ?>
<?php $oldTime	= NULL; ?>
<?php foreach ($category['members'] as $i => $member) { ?>
<?php
if ($oldScore === NULL || $oldScore != $member['score'] || $oldTime != $member['lasttime']) {
	$rank = $i + 1;
}
?>
		<tr>
			<td class="rank <?= ($rank <= $prise ? 'prise' : '') ?>"><?= ($category['code'] == 'CHL' ? NULL : $rank) ?></td>
			<td class="callsign"><?= $member['callsign'] ?></td>
			<td class="numqso"  ><?= ($category['code'] == 'CHL'                          ? '' : number_format($member['numqso'])) ?></td>
			<td class="point"   ><?= ($category['code'] == 'CHL'                          ? '' : number_format($member['point']))  ?></td>
			<td class="multi"   ><?= ($category['code'] == 'CHL'                          ? '' : number_format($member['multi']))  ?></td>
			<td class="score"   ><?= ($category['code'] == 'CHL'                          ? '' : number_format($member['score']))  ?></td>
			<td class="lasttime"><?= ($category['code'] == 'CHL' || $member['score'] == 0 ? '' : $member['lasttime'])              ?></td>
		</tr>
<?php $oldScore	= $member['score'];    ?>
<?php $oldTime	= $member['lasttime']; ?>
<?php } ?>
<?php } ?>
	</tbody>
</table>
<?php } ?>
<table id="club">
	<thead>
		<tr>
			<th id="category_name" colspan="4">登録クラブ対抗</th>
		</tr>
		<tr>
			<th id="rank"  >順位</th>
			<th id="number">登録番号</th>
			<th id="name"  >クラブ名</th>
			<th id="score" >得点</th>
		</tr>
	</thead>
	<tbody>
<?php
	$prise = 3;
	if (count($resultClub) <= 10) {
		$prise = ceil(count($resultClub) / 5);
	}
?>
<?php foreach ($resultClub as $reg_number => $club) { ?>
		<tr>
			<td class="rank <?= ($club['rank'] <= $prise ? 'prise' : '') ?>"><?= $club['rank'] ?></td>
			<td class="number"><?= $reg_number ?></td>
			<td class="name"  ><?= $club['name'] ?></td>
			<td class="score" ><?= number_format($club['score']) ?></td>
		</tr>
<?php } ?>
	</tbody>
</table>
<div id="footer"><div>
	<h3>中国地方本部 コンテスト委員会</h3>
</div></div>
</body>
</html>
<?php
function getScore(PDO $db, $sum_id) {

	global $stmScore;

	$stmScore->bindValue(':sumid', $sum_id);
	$stmScore->execute();
	$score = $stmScore->fetch();

	$result = new Score();
	$result->numqso	= ($score === FALSE ? 0 : $score['numqso']);

	return $result;
}
