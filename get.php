<?php
/*******************************************************
ダウンロードしたい動画のidを同ディレクトリのvideo_idという
ファイルに一行に１つずつ書いてから実行してください。
改行コードはLF（\n）です。
*******************************************************/

error_reporting(E_ALL);
define("PROGRESS_WIDTH",40);
define("PROGRESS_STEP",100/PROGRESS_WIDTH);

$savedir = "./save";
if(!file_exists($savedir))mkdir($savedir,0777,true);
$tmpdir = "./tmp/json";
if(!file_exists($tmpdir))mkdir($tmpdir,0777,true);


//IDのファイル読み込み
$id_file = "./video_id";
if($argc>1){
	$id_file = $argv[1];
	if(!file_exists($id_file)){
		echo "\tinvalid file specified: ".$id_file."\n";
		exit;
	}
}
echo "reading ".$id_file."\n";

$ids = file_get_contents($id_file);
if(strlen($ids)){
	$ids = explode("\n",$ids);
}else{
	echo "invalid file\n";
	exit;
}

foreach($ids as $key => $id){
	if(!strlen($id))unset($ids[$key]);
}
echo count($ids)." ids found.\n";

$m3u8_file = "./m3u8-list.txt";
$json_file = "./json-list.txt";
if(file_exists($m3u8_file))unlink($m3u8_file);
if(file_exists($json_file))unlink($json_file);
foreach($ids as $id){
	echo "#".$id." ";

	$json_local = "./tmp/json/".$id.".json";
	$thumbnail_url = "https://www.nhk.or.jp/figure/thumbnail/".$id."_LL.jpg";
	
	//JSONを取得
	$jsonp_url = "https://www.nhk.or.jp/figure/live-data/js_data/".$id.".jsonp";
	if(!file_exists($json_local)||!filesize($json_local)){
		$jsonp = file_get_contents($jsonp_url);
		$json_data = substr($jsonp, 13, strlen($jsonp)-14);
		file_put_contents($json_local, $json_data);
	}else{
		$json_data = file_get_contents($json_local);
	}
	
	//JSONから情報を読み込む
	$json = json_decode($json_data,true);	
	if(is_array($json) && count($json)){
		$json = $json[0];
	}
	if(!isset($json["mp4filename"])){
		echo "\tinvalid json: ".$json_local."\n";
		var_dump($json);
		break;
	}

	$mp4filename = $json["mp4filename"];
	$category = $json["category"];
	$segment  = $json["segment"];
	$title    = $json["title"];
	$player   = $json["player_name"];
	echo "".$category." - ".$segment." - ".$player."\n";

	//JSON list
	file_put_contents($json_file, "# ".$title."\n".$jsonp_url."\n", FILE_APPEND);

	//保存ディレクトリ
	$savedir = "./save/".$category."/".$segment;
	if(!file_exists($savedir))mkdir($savedir,0777,true);
	
	//サムネイル
	$thumb_local = $savedir."/".$title.".jpg";
	if(!file_exists($thumb_local)||!filesize($thumb_local)){
		file_put_contents($thumb_local,file_get_contents($thumbnail_url));
	}

	//一時ディレクトリ
	$tmpdir = "./tmp/".$title;
	if(!file_exists($tmpdir))mkdir($tmpdir,0777,true);

	//master.m3u8 これに含まれるURLは一定時間後に無効になる
	$m3u_url = "https://nhk-vh.akamaihd.net/i/figure/r/figure/". $mp4filename .".mp4/master.m3u8";
	file_put_contents($m3u8_file, "# ".$title."\n".$m3u_url."\n", FILE_APPEND);
	$m3u_local = $tmpdir."/master.m3u8";
	if(!file_exists($m3u_local)||!filesize($m3u_local)){
		echo "\tfetching ".$mp4filename.".mp4/master.m3u8\n";
		$m3u = file_get_contents($m3u_url);
		file_put_contents($m3u_local,$m3u);
	}else{
		echo "\treading local ".$mp4filename.".mp4/master.m3u8\n";
		$m3u = file_get_contents($m3u_local);
	}
	$m3u = explode("\n",$m3u);

	//index_0_av.m3u8
	$video_list = "";
	foreach($m3u as $line){
		if(!strlen($line) || $line[0] == "#") continue;
		if(strpos($line,"https://")===0){
			$list_url = $line;
			$list_local = $tmpdir."/index_0_av.m3u8";
			if(!file_exists($list_local)||!filesize($list_local)){
				echo "\tfetching ".$mp4filename.".mp4/index_0_av.m3u8\n";
				$video_list = file_get_contents($list_url);
				file_put_contents($list_local, $video_list);
			}else{
				echo "\treading local ".$mp4filename.".mp4/index_0_av.m3u8\n";
				$video_list = file_get_contents($list_local);
			}
			break;
		}
	}
	
	if(!strlen($video_list)){
		echo "\tfailed to fetch index\n";
		exit;
	}


	$video_list = explode("\n",$video_list);
	
	//crypt.key
	$key_file = $tmpdir."/crypt.key";
	if(!file_exists($key_file)||!filesize($key_file)){
		foreach($video_list as $line){
			if(!strlen($line)) continue;
			if(strpos($line, "#EXT-X-KEY")===0){
				$key_url = substr($line, strpos($line,"https"));
				$key_url = substr($key_url, 0, strlen($key_url)-1);
				echo "\tfetching ".$mp4filename.".mp4/crypt.key\n";//.$key_url."\n";
				file_put_contents($key_file,file_get_contents($key_url));
				break;
			}
		}
	}else{
		echo "\treading local ".$mp4filename.".mp4/crypt.key\n";
	}
	if(!file_exists($key_file)){
		echo "\tcrypt.key not found";
		exit;
	}else{
		$key = file_get_contents($key_file);
	}
	//$key_hex = bin2hex($key);//exec("cat \"".$key_file."\" | hexdump -e '16/1 \"%02x\"'");
	
	// SAVE VIDEOS
	$counter = 0;

	//最終ファイル：既存の場合は削除
	$video_filename_one = $savedir."/".$title.".ts";
	if(file_exists($video_filename_one))unlink($video_filename_one);
	
	//動画リスト：ffmpegで結合する場合に使う
	$ts_list_file = $tmpdir."/_ts_list.txt";
	if(file_exists($ts_list_file))unlink($ts_list_file);
	
	//動画のURL以外はリストから削除
	foreach($video_list as $key => $line){
		if(!strlen($line) || $line[0]=="#") unset($video_list[$key]);
	}

	$video_splits = count($video_list);
	foreach($video_list as $line){
		if(strpos($line,"http://")===0){

			//プログレスバーの表示
			$progress = max(1,floor(($counter+1)*PROGRESS_WIDTH/$video_splits));
			$remain = PROGRESS_WIDTH - $progress;
			echo "\r";
			echo "\tfetching video ".sprintf("%02s/%02s",$counter+1,$video_splits)." [".str_repeat("#",$progress).str_repeat(" ",$remain)."]";

			//復号したファイルの保存名
			$video_filename = $tmpdir."/video_decrypted_".$counter.".ts";
			//file_put_contents($ts_list_file, $video_filename."\n", FILE_APPEND);

			//暗合されたファイルを取得
			$crypted_video_filename = $tmpdir."/video_".$counter.".crypted.ts";
			if(!file_exists($crypted_video_filename) || !filesize($crypted_video_filename)){
				file_put_contents($crypted_video_filename,file_get_contents($line));
			}			
			
			//復号
			if(!file_exists($video_filename)||!filesize($video_filename)){
				#echo "\tdecrypt video #".$counter."\n";
				$iv = $counter+1;//sprintf("%032x",$counter);
				//$decrypt = "openssl enc -d -aes-128-cbc -in \"".$crypted_video_filename."\" -K ".$key_hex." -iv ".$iv." -out \"".$video_filename."\"";
				$decrypt = "openssl enc -d -aes-128-cbc -in \"".$crypted_video_filename."\" -K $(cat \"".$key_file."\" | hexdump -e '16/1 \"%02x\"') -iv ".$iv." -out \"".$video_filename."\"";

				#echo "\n".$decrypt."\n";
				exec($decrypt);
/*
				$result = openssl_decrypt($crypted_video_filename,"aes-128-cbc",bin2hex($key),0,"0000000000000001");
				if($result){
					file_put_contents($video_filename,$result);
				}else{
					echo "\tFailed to decrypt.\n";
				//	break;
				}
*/
			}
			file_put_contents($video_filename_one, file_get_contents($video_filename), FILE_APPEND);
			$counter++;
//			sleep(max(mt_rand(0,10),8)-8);
			continue;
		}
	}

	//$concat = "ffmpeg -f concat -i \"".$ts_list_file."\" -c copy \"".$video_filename_one."\"";
	//exec($concat);
	echo "\n";
	echo "\tsaved as ".$video_filename_one."\n";
//	sleep(max(mt_rand(0,10),7)-7);

}

