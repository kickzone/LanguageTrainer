<?php
function OpenDb(){
	// mysqlへの接続
	$mysqli = new mysqli("mysql.hostinger.jp", "u583451352_db1", "nyounyou");
	if ($mysqli->connect_errno) {
		print('<p>データベースへの接続に失敗しました。</p>' . $mysqli->connect_error);
		exit();
	}

	// データベースの選択
	$mysqli->select_db("u583451352_db1");


	return $mysqli;
}

//問題集の名前をゲットする
function GetTableName()
{
	$mysqli = OpenDb();
	$query = "SELECT * FROM lttable WHERE TableID=" . $_SESSION["TABLEID"];
	$result = ExecQuery($mysqli, $query);
	$row = $result->fetch_assoc();
	$ret = $row["TableName"];
	$mysqli->close();

	return $ret;
}

function ExecQuery($mysqli, $query){
	$result = $mysqli->query($query);
	if (!$result) {
		print('クエリーが失敗しました。' . $mysqli->error);
		$mysqli->close();
		exit();
	}
	return $result;
}

?>