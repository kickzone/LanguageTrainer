<?php
require 'password.php';
require 'funcs.php';

date_default_timezone_set('Asia/Tokyo');

$_SESSION = array();

// セッション開始
session_start();

// エラーメッセージの初期化
$errorMessage = "";

// ログインボタンが押された場合
if (isset($_POST["login"])) {
	// １．ユーザIDの入力チェック
	if (empty($_POST["username"])) {
		$errorMessage = "ユーザIDが未入力です。";
	} else if (empty($_POST["password"])) {
		$errorMessage = "パスワードが未入力です。";
	}

	// ２．ユーザIDとパスワードが入力されていたら認証する
	if (!empty($_POST["username"]) && !empty($_POST["password"])) {

		$mysqli = OpenDb();

		// 入力値のサニタイズ
		$username = $mysqli->real_escape_string($_POST["username"]);

		// クエリの実行
		$query = "SELECT * FROM ltuser WHERE UserName = '" . $username . "'";
		$result = ExecQuery($mysqli, $query);

		$user_count = $result->num_rows;
		$db_hashed_pwd = "";
		$UserID = "";

		while ($row = $result->fetch_assoc()) {
			// パスワード(暗号化済み）の取り出し
			$db_hashed_pwd = $row['Password'];
			$UserID = $row['UserID'];
		}

		// データベースの切断
		$mysqli->close();

		// ３．画面から入力されたパスワードとデータベースから取得したパスワードのハッシュを比較します。
		//if ($_POST["password"] == $pw) {
		if (password_verify($_POST["password"], $db_hashed_pwd)) {
			// ４．認証成功なら、セッションIDを新規に発行する
			session_regenerate_id(true);
			$_SESSION["USERNAME"] = $_POST["username"];
			$_SESSION["USERID"] = $UserID;
			header("Location: main.php");
			exit;
		}
		else {
			// 認証失敗
			if($user_count == 0){
				$errorMessage = "ユーザーが登録されていません。";
			}
			else {
				$errorMessage = "パスワードに誤りがあります。";
			}
		}
	} else {
		// 未入力なら何もしない
	}
}
else if (isset($_POST["register"])) {
	//新規登録
	// １．ユーザIDの入力チェック
	if (empty($_POST["username"])) {
		$errorMessage = "ユーザIDが未入力です。";
		return;
	} else if (empty($_POST["password"])) {
		$errorMessage = "パスワードが未入力です。";
	}

	// ２．ユーザIDとパスワードが入力されていたら認証する
	if (!empty($_POST["username"]) && !empty($_POST["password"])) {
		// mysqlへの接続
		$mysqli = OpenDb();

		// 入力値のサニタイズ
		$username = $mysqli->real_escape_string($_POST["username"]);

		// クエリの実行
		$query = "SELECT * FROM ltuser WHERE UserName = '" . $username . "'";
		$result = ExecQuery($mysqli, $query);

		$user_count = $result->num_rows;
		if($user_count > 0)
		{
			$errorMessage = "そのユーザー名は既に登録されています。";
		}
		else {
			//パスワードを暗号化
			$passwordHash = password_hash($_POST["password"], PASSWORD_DEFAULT);
			//最大値を得る
			$query = "SELECT MAX(UserID) FROM ltuser";
			$result = ExecQuery($mysqli, $query);
			$row = $result->fetch_assoc();
			$MaxUserID = $row['MAX(UserID)'];
			$NewUserID = $MaxUserID+1;

			//1レコード挿入
			$query = "INSERT INTO ltuser(UserID, UserName, Password) VALUES(" . $NewUserID . ", '" . $_POST["username"] . "', '" . $passwordHash . "')";
			//print($query);
			//exit();
			$result = ExecQuery($mysqli, $query);

			//このままログインする
			session_regenerate_id(true);
			$_SESSION["USERNAME"] = $_POST["username"];
			$_SESSION["USERID"] = $row['UserID'];
			header("Location: main.php");
		}
	}
}

?>

<!doctype html>
<html>
  <head>
  <meta charset="UTF-8">
  <title>LanguageTrainer</title>
  </head>
  <body>
  <h1>LanguageTrainer</h1>
  <P>
  サーバー時間：<?php echo date('l jS \of F Y h:i:s A'); ?><BR>
  ユーザーIDとパスワードを入力してください。<BR>
  登録済みのユーザーは「ログイン」、初めてこのアプリケーションを使用する場合は「新規登録」を押してください。
  </P>
  <!-- $_SERVER['PHP_SELF']はXSSの危険性があるので、actionは空にしておく -->
  <!--<form id="loginForm" name="loginForm" action="<?php print($_SERVER['PHP_SELF']) ?>" method="POST">-->
  <form id="loginForm" name="loginForm" action="" method="POST">
  <fieldset>
  <legend>ログインフォーム</legend>
  <div><?php echo $errorMessage ?></div>
  <label for="userid">ユーザID</label><input type="text" id="username" name="username" value="">
  <br>
  <label for="password">パスワード</label><input type="password" id="password" name="password" value="">
  <br>
  <input type="submit" id="login" name="login" value="ログイン">
  <input type="submit" id="register" name="register" value="新規登録">
  </fieldset>
  </form>
  <P>更新履歴
  <BR>2014/10/19 編集画面に検索機能を追加
  <BR>2014/10/18 GitHubに追加 https://github.com/kickzone/LanguageTrainer
  <BR>2014/10/17 統計機能に合計を追加
  <BR>2014/10/05 統計機能を実装した
  <BR>2014/09/28 単語登録時、BRタグの後に改行コードが入ったままDBに記録されてしまう問題を修正、DBの余分な改行を除去
  <BR>2014/09/27 単語登録画面で、画面上の改行コードが自動的に挿入されてしまう問題を修正
  <BR>2014/09/07 一覧表示機能追加
  <BR>2014/09/02 タイムゾーンが未定義であったため時刻が13時間遅れだったのを修正
  <BR>2014/08/18 復習が必要な問題数のカウント方法を変更
  <BR>2014/08/17 登録済み問題の編集機能を追加、その他問題数表示など細かい追加機能搭載、一通り使えるバージョンの完成
  <BR>2014/08/14 復習用のSQL文が間違っていたので修正
  <BR>2014/08/13 多数バグ修正 データベース増強(随時)
  <BR>2014/08/10 初版
  </P>
  </body>
</html>