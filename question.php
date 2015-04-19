<?php
require 'funcs.php';

date_default_timezone_set('Asia/Tokyo');

$message = "";
$htmlQuestion = "";
$htmlExample = "";
$htmlAnswer = "";
$msgExample = "例文を表示/非表示";

// セッション開始
session_start();

// ログイン状態のチェック
if (!isset($_SESSION["USERID"])) {
	header("Location: index.php");
	exit;
}
$UserID = $_SESSION["USERID"];
$TableID = $_SESSION["TABLEID"];
// 初回表示時：問題を表示する
if (!isset($_POST["registAndNext"]) && !isset($_POST["registAndBack"]) && !isset($_POST["registAndEdit"])) {

	$ElementID = SelectQuestion($UserID, $TableID);
	//1問も問題がなかったなど、エラー時
	if($ElementID == 0) return;

	$_SESSION["ElementID"] = $ElementID;

	$mysqli = OpenDb();
	$query = "SELECT * FROM ltelement WHERE TableID=" . $TableID . " AND ElementID=" . $ElementID;
	$result = ExecQuery($mysqli, $query);
	$row = $result->fetch_assoc();
	//表示
	View($row["Question"], $row["Answer"], $htmlQuestion, $htmlExample, $htmlAnswer);
	$mysqli->close();
}
//記録ボタンを押した
else{
	//セッションに保存しておいた値を使う
	$bInsert = $_SESSION["bInsert"];
	$bUpdateFlag = $_SESSION["bUpdateFlag"];
	$ElementID = $_SESSION["ElementID"];

	$memory = $_REQUEST["remember"];
	$mysqli = OpenDb();
	$query = "";
	if($bInsert){
		//ログ新規追加 FlagとCountは1にする
		$query = "INSERT INTO ltlog(UserID, TableID, ElementID, Memory, Flag, Count, Time) VALUES(" .
			$UserID . ", " . $TableID . ", " . $ElementID . ", " . $memory . ", 1, 1, '" . date("y/m/d") . "')" ;
	}
	else{
		$ltlogFlag = $_SESSION["ltlogFlag"];
		$ltlogCount = $_SESSION["ltlogCount"];

		//「覚えていた！」ならFlagをインクリメント
		//「なんとなく覚えていた」ならFlagはインクリメントしない
		//「忘れた。。」ならFlagはデクリメントする
		// 復習モードならFlagは0にする
		if($bUpdateFlag && $memory == 2) $ltlogFlag++;
		if($memory == 0){
			if($_SESSION["OLD"] == "1") $ltlogFlag = 1;
			else if($ltLogFlag > 1) $ltlogFlag--;
		}
		$ltlogCount++;
		$query = "UPDATE ltlog SET Memory=" . $memory . ", Flag=" . $ltlogFlag . ", Count=" . $ltlogCount . ", Time='" . date("y/m/d") .
			"' WHERE UserID=" . $UserID . " AND TableID=" . $TableID . " AND ElementID=" . $ElementID;
	}
	$result = ExecQuery($mysqli, $query);
	$mysqli->Close();
	if(isSet($_POST["registAndBack"])){
		//問題集選択へ戻る
		$_POST = array();
		header("Location: main.php");
	}else if(isSet($_POST["registAndEdit"])){
		//記録後編集
		$_POST = array();
		$_SESSION["EDITMODE"] = 2;
		$_SESSION["ElementID"] = $ElementID;
		header("Location: editmain.php");
	}
	else{
		//次の問題へ
		$_POST = array();
		header("Location: question.php");
	}
}

//問題選択関数 メイン
function SelectQuestion($UserID, $TableID){

	//LTLog.Flag=1 かつ 現在時刻 - LtLog.Time>=1日 の問題
	//LTLog.Flag=2 かつ 現在時刻 - LtLog.Time>=7日 の問題
	//LTLog.Flag=3 かつ 現在時刻 - LtLog.Time>=14日 の問題
	//LTLog.Flag=4 かつ 現在時刻 - LtLog.Time>=1か月 の問題
	//の順で優先して出題する。このとき、Flagはインクリメントする。
	//これらを使い切ったら、未出題の問題、すなわちltlogテーブルにレコードの無い問題を出題する。
	//ltlogに存在しない問題が無くなったら、ランダムに出題する。ランダムに出題した場合はFlagとTimeを更新しない。

	$mysqli = OpenDb();

	//2015/02/08 復習モード
	if($_SESSION["OLD"] == "1"){
		$condDate = date("y/m/d", strtotime("-60 day"));
		$query = "SELECT * FROM ltlog WHERE UserID=" . $UserID . " AND TableID=" . $TableID . " AND ltlog.Flag=5 AND Time<='"  . $condDate . "'";
		$result = ExecQuery($mysqli, $query);
		if($result->num_rows == 0)
		{
			$GLOBALS['message'] = "復習する問題がありません。";
			$mysqli->close();
			return 0;
		}
		$rndNum = rand(0, $result->num_rows-1);
		$result->data_seek($rndNum);
		$row = $result->fetch_assoc();
		$reviewElementNo = $row["ElementID"];
		$reviewElementNums = $result->num_rows;
		$GLOBALS['message'] = "1か月後の復習が終わってから60日以上経過した問題です。あと".$result->num_rows."問復習できます。";
		$_SESSION["ltlogCount"] = $row["Count"];
		$_SESSION["ltlogFlag"] = $row["Flag"];
		$_SESSION["bUpdateFlag"] = true;
		$_SESSION["bInsert"] = false;
		return $row["ElementID"];
	}

	//選択条件に当てはまるものからリターンする
	$reviewElementNo = 0;
	$reviewElementNums = 0;
	for($idx = 0; ; $idx++){
		$cond = LTLogCondition::GetCondition($idx);
		if($cond == null) break;

		$query = $cond->MakeSQL($UserID, $TableID);
		$result = ExecQuery($mysqli, $query);
		if($reviewElementNums > 0)
		{
			//すでにヒットしている場合、件数だけ足し算する
			$reviewElementNums += $result->num_rows;
		}
		else if($result->num_rows > 0)
		{
			//ヒットした
			$rndNum = rand(0, $result->num_rows-1);
			$result->data_seek($rndNum);
			$row = $result->fetch_assoc();
			$reviewElementNo = $row["ElementID"];
			$reviewElementNums = $result->num_rows;
			$GLOBALS['message'] = $cond->Comment;
			$_SESSION["ltlogCount"] = $row["Count"];
			$_SESSION["ltlogFlag"] = $row["Flag"];
			$_SESSION["bUpdateFlag"] = true;
			$_SESSION["bInsert"] = false;
		}
	}
	if($reviewElementNums > 0){
		$mysqli->close();
		$GLOBALS['message'] .= "復習の必要がある問題は残り" . $reviewElementNums . "問です。";
		return $reviewElementNo;
	}


	//復習条件に当てはまるレコードが無くなったので、
	//次に、新規出題分として、ltlogに存在しないレコードを検索する。
	$query = "SELECT ltelement.ElementID FROM ltelement WHERE ltelement.TableID=" . $TableID .
		" AND NOT EXISTS(SELECT 1 from ltlog WHERE ltlog.UserID=" . $UserID . " AND ltelement.TableID=ltlog.TableID AND ltelement.ElementID=ltlog.ElementID) ".
		"ORDER BY ltelement.ElementID";
	$result = ExecQuery($mysqli, $query);
	if($result->num_rows > 0)
	{
		//ヒットした
		$rndNum = rand(0, $result->num_rows-1);
		$result->data_seek($rndNum);
		$row = $result->fetch_assoc();
		$ret = $row["ElementID"];
		$GLOBALS['message'] = "新規出題です。未出題問題はあと" . ($result->num_rows-1) . "問あります。";
		$mysqli->close();
		$_SESSION["bUpdateFlag"] = true;
		$_SESSION["bInsert"]= true;
		return $ret;
	}

	//選択条件に当てはまらなかったので、ランダム出題する
	$GLOBALS['bUpdateFlag'] = false;
	$_SESSION["bInsert"] = false;
	$query = "SELECT MAX(ElementID) FROM ltelement WHERE TableID=" . $TableID;
	$result = ExecQuery($mysqli, $query);
	if($result->num_rows == 0)
	{
		$GLOBALS['message'] = "問題集に1問も問題がありません。";
		$mysqli->close();
		return 0;
	}
	$row = $result->fetch_assoc();
	$MaxNum = $row["MAX(ElementID)"];
	$rndNum = rand(1, $MaxNum);
	$query = "SELECT * FROM ltlog WHERE UserID=" . $UserID . " AND TableID=" . $TableID . " AND ElementID=" . $rndNum;
	$result = ExecQuery($mysqli, $query);
	$row = $result->fetch_assoc();
	$_SESSION["ltlogCount"] = $row["Count"];
	$_SESSION["ltlogFlag"] = $row["Flag"];
	$GLOBALS['message'] = "ランダム出題です。";
	$mysqli->close();
	return rand(1, $MaxNum);
}

function View($question, $answer)
{
	//TableTypeによって処理を分ける予定
	$docQ = new SimpleXMLElement($question);
	$docA = new SimpleXMLElement($answer);
	$GLOBALS['htmlQuestion'] = "<H1>" . $docQ->WORD . "</H1>";
	$GLOBALS['htmlExample'] = "<P>" . $docQ->SENTENCE . "</P>";
	$GLOBALS['htmlAnswer'] = "<P>" . $docA->ANSWER . "</P>";
	if($docQ->SENTENCE == "") $GLOBALS['msgExample'] = "例文はありません";
}

class LTLogCondition{
	public $Flag, $BeforeTime, $Comment;

	//function LTLogCondition($Flag, $BeforeTime, $Comment){
	function __construct($Flag, $BeforeTime, $Comment){
		$this->Flag = $Flag;
		$this->BeforeTime = $BeforeTime;
		$this->Comment = $Comment;
	}

	private static $conditions = array();
	private static function InitConditions()
	{
		if(count(self::$conditions) > 0) return;

		self::$conditions[] = new LTLogCondition(1, 1, "1日後の復習です。");
		self::$conditions[] = new LTLogCondition(2, 7, "1週間後の復習です。");
		self::$conditions[] = new LTLogCondition(3, 14, "2週間後の復習です。");
		self::$conditions[] = new LTLogCondition(4, 30, "1か月後の復習です。");
	}

	public static function GetCondition($idx){
		if(count(self::$conditions) == 0) self::InitConditions();
		if(count(self::$conditions) <= $idx) return Null;
		return self::$conditions[$idx];
	}

	function MakeSQL($UserID, $TableID)	{
		$condDate = date("y/m/d", strtotime("-" . $this->BeforeTime . " day"));
		$query = "SELECT * FROM ltlog WHERE UserID=" . $UserID . " AND TableID=" . $TableID . " AND ltlog.Flag=" . $this->Flag . " AND Time<='"  . $condDate . "' ORDER BY Time, ElementID";
		return $query;
	}
}
?>


<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<style>
#answer{
 display:none;
}
p {
  width: 33%
  min-width: 30em;
  max-width: 50em;
}
</style>
<script type="text/javascript">
var ctExample=0;

//例文の表示・非表示の切り替え
function dispExample(){
 ctExample++;
 if(ctExample%2==1)
  document.getElementById("example").style.display="block";
 else
  document.getElementById("example").style.display="none";
}

//解答を表示
function dispAnswer(){
  document.getElementById("answer").style.display="block";
}

</script>
<title>出題</title>
</head>

<body>
<p>ユーザー名：<?=htmlspecialchars($_SESSION["USERNAME"], ENT_QUOTES); ?></p>
<p>問題集：<?=htmlspecialchars(GetTableName()); ?></p>
<BR>
<BR>
<div><?php echo $message ?></div>
<div id="question"><?php echo $htmlQuestion ?></div>
<BR>
<a href="javascript:void(0);" onclick="javascript:dispExample();"><?php echo $msgExample ?></a>
<div id="example" style="display:none"><?php echo $htmlExample ?></div>
<BR>
<BR>
<a href="javascript:void(0);" onclick="javascript:dispAnswer();">解答を表示</a>
<div id="answer"  style="display:none">
	<?php echo $htmlAnswer ?>
	<BR>
	<form id="loginForm" name="loginForm" action="" method="POST">
	<fieldset>
		<input type="radio" id="wellRemenbered" name="remember" value="2" checked="checked">覚えていた！
		<input type="radio" id="slightRemenbered" name="remember" value="1">なんとなく覚えていた
		<input type="radio" id="forget" name="remember" value="0">忘れた。。
		<BR>
		<input type="submit" id="registAndNext" name="registAndNext" value="記録して次の問題へ">
		<input type="submit" id="registAndBack" name="registAndBack" value="記録して問題集選択画面に戻る">
		<input type="submit" id="registAndEdit" name="registAndEdit" value="記録してからこの問題を修正">
	</fieldset>
	</form>
</div>
<BR>
<BR>
<a href="main.php">解答せずに戻る</a>
</body>

</html>