<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1" />
<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
<meta name="theme-color" content="#215591">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
<?php
$global = json_decode(file_get_contents("{$_SERVER['QUERY_STRING']}.json"), true);
?>
<title><?= $global['title'] ?> - 順位表</title>
</head>
<body class="font-monospace">
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

	$SQL = <<<EOF
SELECT
	MC.disporder,
	MC.code,
	MC.name AS category,
	SUM.callsign,
	SUM.name,
	SUM.address,
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
WHERE
	MC.code	<> 'CHL'
ORDER BY
	MC.disporder,
	SC.score DESC,
	LAST.lasttime
EOF;
	$stmt = $db->query($SQL);

	while ($record = $stmt->fetch()) {
		if (!isset($resultAll[$record['disporder']])) {
			$resultAll[$record['disporder']] = array(
				'code'		=> $record['code'],
				'name'		=> $record['category'],
				'members'	=> array());
			$i = 1;
		}

		$oldScore	= NULL;
		$oldTime	= NULL;
		if ($record['callsign'] !== NULL) {
			if ($oldScore === NULL || $oldScore != $record['score'] || $oldTime != $record['lasttime']) {
				$rank = $i;
			}
			$resultAll[$record['disporder']]['members'][] = array(
				'rank'		=> $rank,
				'callsign'	=> $record['callsign'],
				'name'		=> $record['name'],
				'address'	=> $record['address']);

			$i++;
		}
	}

	$list = array();
	foreach ($resultAll as $result) {
		$prise = 3;
		if (count($result['members']) <= 10) {
			$prise = ceil(count($result['members']) / 5);
		}
		foreach ($result['members'] as $member) {
			if ($member['rank'] > $prise) {
				continue;
			}

			if (!isset($list[$member['callsign']])) {
				if (preg_match('/^\d{3}\-\d{4}/', $member['address'])) {
					$zip_code	= substr($member['address'], 0, 8);
					$address	= substr($member['address'], 9);
				} else {
					$zip_code	= '';
					$address	= $member['address'];
				}
				$list[$member['callsign']] = array(
						'callsign'	=> mb_convert_kana($member['callsign'], 'A'),
						'name'		=> $member['name'],
						'zip_code'	=> $zip_code,
						'address'	=> $address,
						'prises'	=> array());
			}

			$category_name = $result['name'];
			$category_name = str_replace('【',  '', $category_name);
			$category_name = str_replace('】', ' ', $category_name);
			$category_name = str_replace('／', ' ', $category_name);
			$category_name = mb_convert_kana($category_name, 'ak');

			$list[$member['callsign']]['prises'][] = array(
					'category'	=> $category_name,
					'rank'		=> '第'. mb_convert_kana($member['rank'], 'N'). '位');
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

	$prise = 3;
	if (count($resultClub) <= 10) {
		$prise = ceil(count($resultClub) / 5);
	}
	foreach ($resultClub as $number => $result) {
		if ($result['rank'] > $prise) {
			break;
		}

		$list[$number] = array(
				'callsign'	=> $number,
				'name'		=> $result['name'],
				'zip_code'	=> '',
				'address'	=> '',
				'prises'	=> array(array(
						'category'	=> '登録ｸﾗﾌﾞ対抗',
						'rank'		=> '第'. mb_convert_kana($result['rank'], 'N'). '位')));
	}

} catch (PDOException $pe) {
	error_log($pe->getMessage());
	$resultAll = false;
	$resultClub = false;

} finally {
	$db = null;
}
?>
<table class="table table-bordered">
	<thead><tr>
		<th>コールサイン</th>
		<th>名前</th>
		<th>郵便番号</th>
		<th>住所</th>
		<th>カテゴリー１</th>
		<th>順位１</th>
		<th>カテゴリー２</th>
		<th>順位２</th>
	</tr></thead>
	<tbody>
<?php foreach ($list as $callsign => $result) { ?>
		<tr>
			<td><?= $result['callsign'] ?></td>
			<td><?= $result['name'] ?></td>
			<td><?= $result['zip_code'] ?></td>
			<td><?= $result['address'] ?></td>
			<td><?= $result['prises'][0]['category'] ?></td>
			<td><?= $result['prises'][0]['rank'] ?></td>
			<td><?= (isset($result['prises'][1]) ? $result['prises'][1]['category'] : '') ?></td>
			<td><?= (isset($result['prises'][1]) ? $result['prises'][1]['rank']     : '') ?></td>
		</tr>
<?php } ?>
	</tbody>
</table>
</body>
</html>
