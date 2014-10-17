<?php
require 'funcs.php';

$message = "";
$tWord = "";
$tSentence = "";
$tAnswer = "";

// セッション開始
session_start();

$editBtnMsg = "\"追加\"";

// ログイン状態のチェック
if (!isset($_SESSION["USERID"])) {
	header("Location: index.php");
	exit;
}

// 開始
if(isset($_POST["return"])){
	header("Location: edit.php");
}
if (isset($_POST["edit"])) {
	if(!empty($_POST["word"]) &&  !empty($_POST["answer"])){
		$mysqli = OpenDb();
		if($_SESSION["EDITMODE"] == 1){
			$query = "SELECT MAX(ElementID) FROM ltelement WHERE TableID=" . $_SESSION["TABLEID"];
			$result = ExecQuery($mysqli, $query);
			$row = $result->fetch_assoc();
			$MaxElementID = $row['MAX(ElementID)'];
			$NewElementID = $MaxElementID+1;

			//ここはTableTypeによって変える予定
			//XMLで書いて、HTML出力のことは後で考える
			$question = "<r><WORD>" . $_POST["word"] . "</WORD><SENTENCE>" . htmlspecialchars(preg_replace('/\n|\r|\r\n/', '', nl2br($_POST["sentence"], ENT_QUOTES))) . "</SENTENCE></r>";
			$answer = "<r><ANSWER>" . htmlspecialchars(preg_replace('/\n|\r|\r\n/', '', nl2br($_POST["answer"]))) . "</ANSWER></r>";
			$query = "INSERT INTO ltelement(TableID, ElementID, Question, Answer) VALUES(" . $_SESSION["TABLEID"] . ", " . $NewElementID . ", '" .
				$mysqli->escape_string($question) . "', '" . $mysqli->escape_string($answer) . "')";
			$result = ExecQuery($mysqli, $query);
			$message = $_POST["word"] . "を追加しました。";
		}
		else{
			//ここはTableTypeによって変える予定
			//XMLで書いて、HTML出力のことは後で考える
			$question = "<r><WORD>" . $_POST["word"] . "</WORD><SENTENCE>" . htmlspecialchars(preg_replace('/\n|\r|\r\n/', '', nl2br($_POST["sentence"], ENT_QUOTES))) . "</SENTENCE></r>";
			$answer = "<r><ANSWER>" . htmlspecialchars(preg_replace('/\n|\r|\r\n/', '', nl2br($_POST["answer"]))) . "</ANSWER></r>";
			$query = "UPDATE ltelement SET Question='" . $mysqli->escape_string($question) . "', Answer='" . $mysqli->escape_string($answer) .
				"' WHERE TableID=" . $_SESSION["TABLEID"] . " AND ElementID=" . $_SESSION["ElementID"];
			$result = ExecQuery($mysqli, $query);
			$message = $_POST["word"] . "を保存しました。";
		}
		$mysqli->Close();

	}
	else {
		$message = "情報を入力してください";
	}
}

if($_SESSION["EDITMODE"] == 2){
	//編集モードのときはDBの情報を表示する
	//ToDo:TableTypeによって動作を変える
	$editBtnMsg = "\"保存\"";
	$mysqli = OpenDb();
	$query = "SELECT * FROM ltelement WHERE TableID=" . $_SESSION["TABLEID"] . " AND ElementID=". $_SESSION["ElementID"];
	$result = ExecQuery($mysqli, $query);
	$row = $result->fetch_assoc();
	$docQ = new SimpleXMLElement($row["Question"]);
	$docA = new SimpleXMLElement($row["Answer"]);
	$tWord = br2nl($docQ->WORD);
	$tSentence = br2nl($docQ->SENTENCE);
	$tAnswer = br2nl($docA->ANSWER);
	$mysqli->Close();
}

function br2nl($string)
{
	// 大文字・小文字を区別しない
	return preg_replace('/<br[[:space:]]*\/?[[:space:]]*>/i', "\n", $string);
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
  <br>
 単語<textarea rows="1" cols="100" wrap="soft" name="word" id="word"><?php echo $tWord; ?></textarea><BR>
 例文<textarea rows="10" cols="100" wrap="soft" name="sentence" id="sentence"><?php echo $tSentence; ?></textarea><BR>
 解答<textarea rows="10" cols="100" wrap="soft" name="answer" id="answer"><?php echo $tAnswer; ?></textarea><BR>
  <input type="submit" id="edit" name="edit" value=<?php echo $editBtnMsg  ?>>
  <BR>
  <input type="submit" id="return" name="return" value="戻る">
  </fieldset>
  </form>
</body>

</html>