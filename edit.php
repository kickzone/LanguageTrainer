<?php
require 'funcs.php';

$message = "";
$searchResult = -1;

// セッション開始
session_start();

// ログイン状態のチェック
if (!isset($_SESSION["USERID"])) {
	header("Location: index.php");
	exit;
}

// 開始
if(isset($_POST["return"])){
	header("Location: main.php");
}
else if(isset($_POST["sortButton"])){
	//ソートする
	$_POST["EDIT_SORT"] = $_POST["sort"];
	unset($_POST["sortButton"]);
}
else if(isset($_POST["searchButton"])){
	//検索する
	$_POST["EDIT_SORT"] = $_POST["sort"];
	$searchResult = DoSearch($_POST["searchWord"]);
	if($searchResult == -1)
	{
		$message = $_POST["searchWord"] . "は見つかりませんでした。";
	}
	else
	{
		$message = $_POST["searchWord"] . "が見つかりました。";
	}
	unset($_POST["searchButton"]);
}
else if (isset($_POST["add"])) {
	$_SESSION["EDITMODE"] = 1;
	header("Location: editmain.php");
}
else if (isset($_POST["edit"])) {
	$_SESSION["EDITMODE"] = 2;
	$_SESSION["ElementID"] = $_POST["word"];
	header("Location: editmain.php");
}


//リストボックスのHTMLを出力する
function MakeListBox()
{
	$mysqli = OpenDb();
	$query = "SELECT * FROM ltelement WHERE TableID=" . $_SESSION["TABLEID"];
	if(EditSort() == 1){
		$query .= " ORDER BY ElementID";
	}
	else if(EditSort() == 2){
		$query .= " ORDER BY Question";
	}
	$result = ExecQuery($mysqli, $query);

	$retStr = "<select size=\"10\" name=\"word\">";
	while ($row = $result->fetch_assoc()) {

		$docQ = new SimpleXMLElement($row["Question"]);
		$retStr .= "<option ";
		$retStr .= "value=\"" . $row["ElementID"] . "\"";
		//検索でヒットしたらそのアイテムを選択しておく
		if($GLOBALS['searchResult'] == $row['ElementID'])
		{
			$retStr .= " selected";
		}
		$retStr .=  ">" . htmlspecialchars($docQ->WORD, ENT_QUOTES) . "</option>";
	}
	$retStr .= "</select>";

	$mysqli->close();

	return $retStr;
}

function EditSort()
{
	if(!isset($_POST["EDIT_SORT"])){
		return 1;
	}
	else
	{
		if($_POST["EDIT_SORT"] == 1){
			return 1;
		}
		else if($_POST["EDIT_SORT"] == 2){
			return 2;

		}
	}
}

//検索する
function DoSearch($word)
{
	$mysqli = OpenDb();
	$query = "SELECT * FROM ltelement WHERE TableID=" . $_SESSION["TABLEID"] . " AND Question LIKE '<r><WORD>$word%'";
	$result = ExecQuery($mysqli, $query);
	$ret = -1;

	if($result->num_rows > 0)
	{
		$row = $result->fetch_assoc();
		$ret = $row['ElementID'];
	}

	$mysqli->close();
	return $ret;

}

?>


<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>問題集編集</title>
</head>

<body>
  <form id="editForm" name="editForm" action="" method="POST">
  <fieldset>
  <legend>問題集編集</legend>
  <div><?php echo $message ?></div>
  <p>問題集：<?=htmlspecialchars(GetTableName()); ?></p>
  <input type="submit" id="add" name="add" value="新規追加">
  <BR>
  <BR>
  <label for="userid">問題選択：</label><?php echo MakeListBox() ?>
  <input type="radio" id="sortElementID" name="sort" value="1" <?php if (EditSort() == 1) {echo 'checked';}?>>登録順
  <input type="radio" id="sortQuestion" name="sort" value="2"<?php if (EditSort() == 2) {echo 'checked';}?>>単語順
  <input type="submit" id="sort" name="sortButton" value="並べ替える">
  <BR>
  <input type="submit" id="edit" name="edit" value="編集">
  <BR>
  <input type="text" id="searchWord" name="searchWord" value="">
  <input type="submit" id="search" name="searchButton" value="検索">
  <BR>
  <BR>
  <input type="submit" id="return" name="return" value="戻る">
  </fieldset>
  </form>
</body>

</html>