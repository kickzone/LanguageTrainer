<?php

require 'funcs.php';

$code_html = "";
$code_script = "";

// セッション開始
session_start();

// ログイン状態のチェック
if (!isset($_SESSION["USERID"])) {
	header("Location: index.php");
	exit;
}
if(isset($_POST["return"])){
	header("Location: main.php");
}
//必要な情報を集めて、配列に投入
$info = array();
$mysqli = OpenDb();
$query = "SELECT * FROM lttable";
$result = ExecQuery($mysqli, $query);
while ($row = $result->fetch_assoc())
{
	$infoElement = array();
	$infoElement[] = $row['TableName'];
	array_push($infoElement, MakeLogArr($mysqli, $_SESSION["USERID"], $row['TableID']));
	$info[] = $infoElement;
}

$json_data = json_encode( $info );

$infoAll = array();
$infoElementAll = array();
$infoElementAll[] = "    合計";
$infoElementAll[] = MakeLogArr($mysqli, $_SESSION["USERID"], 0);
$infoAll[] = $infoElementAll;
$json_dataAll = json_encode( $infoAll );

function MakeLogArr($mysqli, $UserID, $TableID)
{
	$retArr = array();
	//まだ出題していない問題数
	$query = "SELECT ltelement.ElementID FROM ltelement WHERE ".
	($TableID != 0 ? ("ltelement.TableID=" . $TableID . " AND ") :  "") .
	"NOT EXISTS(SELECT 1 from ltlog WHERE ltlog.UserID=" . $UserID . " AND ltelement.TableID=ltlog.TableID AND ltelement.ElementID=ltlog.ElementID) ";
	$result = ExecQuery($mysqli, $query);
	$retArr[] = $result->num_rows;
	//Flagが1～5であるレコードの集計
	for($i=1; $i<=5; $i++)
	{
		$query = "SELECT ElementID FROM ltlog WHERE UserID=$UserID ".
		($TableID != 0 ? "AND TableID=$TableID" : "") .
		" AND Flag=$i";
		$result = ExecQuery($mysqli, $query);
		$retArr[] = $result->num_rows;
	}
	return $retArr;
}

function MakeGraphHTML($graphName, $graphNum)
{
	$GLOBALS['$code_html'] .= "<P>$graphName</P>\r\n<DIV id=\"graph$graphNum\"></DIV>\r\n";
}


?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<style type="text/css">
#graph_individual {
	width: 600px;
	height: 400px;
}
#graph_total {
	width: 600px;
	height: 320px;
}
#tab {
	width: 10px;
	height: 50px;
}
</style>
<script type="text/javascript" src="flotr2.min.js"></script>
<title>統計</title>
</head>

<body>
<p>ユーザー名：<?=htmlspecialchars($_SESSION["USERNAME"], ENT_QUOTES); ?></p>
<div id="graph_individual"></div>
<div id="tab"></div>
<div id="graph_total"></div>
<form id="editForm" name="editForm" action="" method="POST">
<input type="submit" id="return" name="return" value="戻る">
</form>

<script>
(function bars_stacked(container) {

    var
    	d1 = [],
        d2 = [],
        d3 = [],
        d4 = [],
        d5 = [],
        d6 = [],
        graph, i;

    //PHPからオブジェクトを受け取る
    var arr = JSON.parse('<?php echo $json_data ?>');

    //展開
	var bar = [];
	for(i=0; i<arr.length; i++)
	{
		bar.push([i, arr[i][0]]);
		d1.push([arr[i][1][0], i]);
		d2.push([arr[i][1][1], i]);
		d3.push([arr[i][1][2], i]);
		d4.push([arr[i][1][3], i]);
		d5.push([arr[i][1][4], i]);
		d6.push([arr[i][1][5], i]);

	}
    var flotrArr = [{
        data: d1,
        label: '未出題'
    }, {
        data: d2,
        label: '1回解いた'
    }, {
        data: d3,
        label: '1日復習完了'
    }, {
        data: d4,
        label: '1週間復習完了'
    }, {
        data: d5,
        label: '2週間復習完了'
    }, {
        data: d6,
        label: '1か月復習完了'
    }];


    graph = Flotr.draw(container, flotrArr,
    {
        legend: {
            backgroundColor: '#D2E8FF', // Light blue
            position: 'ne' //右上に表示
        },
        bars: {
            show: true,
            stacked: true,
            horizontal: true,
            barWidth: 0.6,
            lineWidth: 1,
            shadowSize: 0
        },
        grid: {
            verticalLines: true,
            horizontalLines: false
        },
		yaxis: {
			ticks: bar,
		},
    });
})(document.getElementById("graph_individual"));

(function bars2(container) {

    var
    	d1 = [],
        d2 = [],
        d3 = [],
        d4 = [],
        d5 = [],
        d6 = [],
        graph, i;

    //PHPからオブジェクトを受け取る
    var arr = JSON.parse('<?php echo $json_dataAll ?>');

    //展開
	var bar = [];
	bar.push([0, arr[0][0]]);
	d1.push([arr[0][1][0], 0]);
	d2.push([arr[0][1][1], 0]);
	d3.push([arr[0][1][2], 0]);
	d4.push([arr[0][1][3], 0]);
	d5.push([arr[0][1][4], 0]);
	d6.push([arr[0][1][5], 0]);

    var flotrArr = [{
        data: d1,
        label: '未出題'
    }, {
        data: d2,
        label: '1回解いた'
    }, {
        data: d3,
        label: '1日復習完了'
    }, {
        data: d4,
        label: '1週間復習完了'
    }, {
        data: d5,
        label: '2週間復習完了'
    }, {
        data: d6,
        label: '1か月復習完了'
    }];


    graph = Flotr.draw(container, flotrArr,
    {
        legend: {
            backgroundColor: '#D2E8FF', // Light blue
            position: 'ne' //右上に表示
        },
        bars: {
            show: true,
            stacked: true,
            horizontal: true,
            barWidth: 0.3,
            lineWidth: 2,
            shadowSize: 0
        },
        grid: {
            verticalLines: true,
            horizontalLines: false
        },
		yaxis: {
			ticks: bar,
		},
    });
})(document.getElementById("graph_total"));

</script>

</body>

</html>