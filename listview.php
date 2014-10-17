<?php
require 'funcs.php';

$message = "";

// セッション開始
session_start();

if(isset( $_REQUEST["rowsPerPage"]))
{
	$_rowsPerPage = $_REQUEST["rowsPerPage"];
}
else
{
	$_rowsPerPage = 10;
}

$_location = 1;
if(isset($_POST["location"]))
{
	$_location = $_POST["location"];
}

// ログイン状態のチェック
if (!isset($_SESSION["USERID"])) {
	header("Location: index.php");
	exit;
}

//戻る
if(isset($_POST["return"])){
	header("Location: main.php");
}
//次に移動
else if(isset($_POST["next"])){
	$_POST["location"] += $_rowsPerPage;
}
//最後に移動
else if(isset($_POST["last"])){
	$mysqli = OpenDb();
	$query = "SELECT COUNT(*) FROM ltelement WHERE TableID=" . $_SESSION["TABLEID"];
	$result = ExecQuery($mysqli, $query);
	$row = $result->fetch_assoc();
	$count = $row["COUNT(*)"];
	$mysqli->close();
	$quotient = (int)($count / $_rowsPerPage);
	$_POST["location"] = $quotient * $_rowsPerPage + 1;
}
//カーソル位置に移動
else if(isset($_POST["move"])){
	//なにもしない
}
//前に移動
else if(isset($_POST["before"])){
	$_POST["location"] -= $_rowsPerPage;
}
//最初に移動
else if(isset($_POST["first"])){
	$_POST["location"] = 1;
}

//コントロールバーを表示
function MakeControl()
{
	$retStr = "";
	$location = 1;
	if(isset($_POST["location"]))
	{
		$location = $_POST["location"];
	}
	$step = 10;
	if(isset( $_REQUEST["rowsPerPage"]))
	{
		$step = $_REQUEST["rowsPerPage"];
	}

	$mysqli = OpenDb();
	$query = "SELECT COUNT(*) FROM ltelement WHERE TableID=" . $_SESSION["TABLEID"];
	$result = ExecQuery($mysqli, $query);
	$row = $result->fetch_assoc();
	$count = $row["COUNT(*)"];
	if($location > 1 )
	{
		$retStr .= "<input type=\"submit\" id=\"first\" name=\"first\" value=\"＜＜最初へ\">";
		$retStr .= " <input type=\"submit\" id=\"before\" name=\"before\" value=\"＜前へ\">";
	}
	$retStr .= " 位置：<select name=\"location\">";

	for($i = 1; $i <= $count; $i += $step)
	{
		$retStr .= "<option value=\"$i\"";
		if(abs($i - $location) < $step)
		{
			$retStr .= " selected";
		}
		$retStr .= ">$i- </option>";
	}
	$retStr .= "</select><input type=\"submit\" id=\"move\" name=\"move\" value=\"移動\">";

	if($location + $step <= $count)
	{
		$retStr .= " <input type=\"submit\" id=\"next\" name=\"next\" value=\"次へ＞\">";
		$retStr .= " <input type=\"submit\" id=\"last\" name=\"last\" value=\"最後へ＞＞\">";
	}

	$mysqli->close();

	return $retStr;
}

function MakeListHTML()
{
	$location = 1;
	if(isset($_POST["location"]))
	{
		$location = $_POST["location"];
	}
	$step = 10;
	if(isset( $_REQUEST["rowsPerPage"]))
	{
		$step = $_REQUEST["rowsPerPage"];
	}

	$retStr = "<table border=1>";
	$retStr .= "<TD>番号</TD><TD>単語</TD><TD>例文</TD><TD>解答</TD>";
	$mysqli = OpenDb();
	$offset = $location-1;
	$query = "SELECT * FROM ltelement WHERE TableID=" . $_SESSION["TABLEID"] . " LIMIT $step OFFSET $offset";
	$result = ExecQuery($mysqli, $query);

	while ($row = $result->fetch_assoc())
	{
		$docQ = new SimpleXMLElement($row["Question"]);
		$docA = new SimpleXMLElement($row["Answer"]);
		$retStr .= "<TR><TD>$location</TD><TD>$docQ->WORD</TD><TD>$docQ->SENTENCE</TD><TD>$docA->ANSWER</TD></TR>";
		$location++;
	}

	$mysqli->close();

	$retStr .= "</table>";
	return $retStr;
}

?>


<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>一覧表示</title>
</head>

<body>
  <form id="editForm" name="editForm" action="" method="POST">
  <fieldset>
  <legend>一覧表示</legend>
  <div><?php echo $message ?></div>
  <p>問題集：<?=htmlspecialchars(GetTableName()); ?></p>
  <BR>
  <?php echo MakeControl() ?>
  表示行数：<?php echo "<input type=\"text\" id=\"rowsPerPage\" name=\"rowsPerPage\" size=\"5\" value=\"$_rowsPerPage\">";?>
  <input type="submit" id="changeRows" name="changeRows" value="行数変更">
  <BR>
  <input type="submit" id="return" name="return" value="戻る">
  </fieldset>
  </form>
    <div><?php echo MakeListHTML() ?></div>
</body>

</html>