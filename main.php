<?php
require 'funcs.php';

$message = "";

// セッション開始
session_start();

// ログイン状態のチェック
if (!isset($_SESSION["USERID"])) {
	header("Location: index.php");
	exit;
}
// 開始
if (isset($_POST["start"])) {
	$_SESSION["TABLEID"] = $_POST["table"];
	$_SESSION["OLD"] =$_REQUEST["old"];
	header("Location: question.php");
}
else if(isset($_POST["edit"])) {
	$_SESSION["TABLEID"] = $_POST["table"];
	header("Location: edit.php");
}
else if(isset($_POST["list"])) {
	$_SESSION["TABLEID"] = $_POST["table"];
	header("Location: listview.php");
}
else if(isset($_POST["newTable"])) {

	if(!empty($_POST["newTableName"])){
		$mysqli = OpenDb();
		//最大値を得る
		$query = "SELECT MAX(TableID) FROM lttable";
		$result = ExecQuery($mysqli, $query);
		$row = $result->fetch_assoc();
		$MaxTableID = $row['MAX(TableID)'];
		$NewTableID = $MaxTableID+1;
		$query = "INSERT INTO lttable(TableID, TableName, TableType) VALUES(" . $NewTableID . ", '" . $_POST["newTableName"] . "', 1)";
		$result = $mysqli->query($query);
		if (!$result) {
			print('クエリーが失敗しました。' . $mysqli->error);
			$mysqli->close();
			exit();
		}
		$message = $_POST["newTableName"] . "を新規作成しました。";
		$mysqli->Close();
	}
}
else if(isset($_POST["stat"]))
{
	header("Location: statistics.php");
}

//コンボボックスのHTMLを出力する
function MakeComboBox()
{

	$mysqli = OpenDb();
	$query = "SELECT * FROM lttable ORDER BY TableID";
	$result = ExecQuery($mysqli, $query);

	$retStr = "<select name=\"table\">";
	while ($row = $result->fetch_assoc()) {
		$query2 = "SELECT * FROM ltelement WHERE TableID=" . $row["TableID"];
		$result2 = ExecQuery($mysqli, $query2);

		$retStr .= "<option ";
		$retStr .= "value=\"" . $row["TableID"] . "\">" . htmlspecialchars($row["TableName"] . "(" . $result2->num_rows . ")", ENT_QUOTES) . "</option>";
	}
	$retStr .= "</select>";

	$mysqli->close();

	return $retStr;
}



?>


<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>問題集選択</title>
</head>

<body>
  <form id="tableForm" name="tableForm" action="" method="POST">
  <fieldset>
  <p>ユーザー名：<?=htmlspecialchars($_SESSION["USERNAME"], ENT_QUOTES); ?></p>
  <legend>問題集選択</legend>
  <div><?php echo $message ?></div>
  <label for="userid">問題集選択：</label><?php echo MakeComboBox() ?>
  <br>
  <input type="submit" id="start" name="start" value="開始">
  <input type="submit" id="edit" name="edit" value="編集">
  <input type="submit" id="list" name="list" value="一覧表示">
  <BR>
  <input type="checkbox" id="old" name="old" value="1">復習モード<BR>
  <label for="userid">新規作成する問題集：</label><input type="text" id="newTableName" name="newTableName" value="">
  <input type="submit" id="newTable" name="newTable" value="作成"><BR>
  <input type="submit" id="stat" name="stat" value="統計">
  </fieldset>
  </form>
</body>

</html>