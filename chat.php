<?php
date_default_timezone_set('Asia/Tokyo');
$log_file = 'log.cgi'; //ログファイル。cgiにしてあるのはダウンロードを防ぐため
$html_file = 'chat.html'; //HTMLのキャッシュ
$show_log_size = 50; //表示する行数。この2倍になったらログを切り捨て

//加工したパラメタを得る
function get_param($key, $len) {
	$value = htmlspecialchars($_POST[$key], ENT_QUOTES, "utf-8");
	$value = str_replace("\n", "&nbsp;", $value);
	$value = str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;", $value);
	$value = mb_substr($value, 0, $len);
	return $value;
}

//書き込みモード
if($_POST['mode'] === "0"){
	$name = get_param('name', 40);
	$message = get_param('message', 140);
	if (!$name || !$message) {
		echo "illegal params";
		exit;
	}
	//コマンドは未実装
	$first = mb_substr($message, 0, 1);
	if ($first === '#' || $first === '/') {
		echo "could not command";
		exit;
	}
	$inputValue = date("Y/m/d H:i:s") . "\t" . $name . "\t" . $message . "\n";
	//ファイルにデータを書き込み
	if($inputValue){
		//ファイルをオープンできたか
		if(!$fp = fopen($log_file, "a")){
			echo "could not open";
			exit;
		}
		//書き込みできたか
		if(fwrite($fp, $inputValue) === false) {
			echo "could not write";
			exit;
		}
		//終了処理
		fclose($fp);
	} else {
		echo "not writable";
		exit;
	}
}
//ログを返す
if (filemtime($log_file) > filemtime($html_file)) {
	//ログが新しく、HTMLの生成が必要な場合
	$val = file_get_contents($log_file);
	$val = rtrim($val, "\n");
	$list = array_reverse(explode("\n", $val));
	$log_size = count($list);
	$list = array_slice($list, 0, $show_log_size);
	//ログが表示行数の2倍になったら、表示行数を残して切り捨て
	if ($log_size >= $show_log_size * 2) {
		file_put_contents($log_file, join("\n", array_reverse($list)) . "\n", LOCK_EX);
	}
	//HTMLの生成
	$cnt = count($list);
	$html = '';
	for ($i = 0; $i < $cnt; $i++) {
		$line = explode("\t", $list[$i]);
		if (count($line) < 3) continue;
		if ($i != 0) { $html .= "<br>"; }
		$message = $line[2];
		$message = preg_replace("/(https?:\/\/)([-_.!~*'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)/", "<a href=\"\\1\\2\" target=\"_blank\" rel=\"nofollow\">\\1\\2</a>", $message);
		$html .= "<b>" . $line[1] . "</b> ＞ " . $message . ' <span style="font-size:x-small">[' . $line[0] . "]</span>\n";
	}
	file_put_contents($html_file, $html, LOCK_EX);
	echo $html;
} else {
	//ログが古く、HTMLの生成が不要な場合
	echo file_get_contents($html_file);
}
?>