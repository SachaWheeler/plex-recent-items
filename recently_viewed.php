<?php

	// get the data
	$token = "43u79eEyGPG15hq5nZ6p";
	$url = "http://localhost:32400/status/sessions/history/all"."?X-Plex-Token=".$token;
	$data = new SimpleXMLElement(file_get_contents($url));
	$latest = (string)$data->Video[0]['viewedAt'];
	$lock_file = "./lock.txt";
	$lock = file_get_contents($lock_file);

	if($lock == $latest)
		exit(0);
	else
		file_put_contents($lock_file, $latest);
	$count = 0;
	$already_seen = array();
	foreach($data as $row){
		if(isset($row['grandparentTitle']) && isset($row['grandparentThumb'])){
			$thumb = $row['grandparentThumb'];
			$title = $row['grandparentTitle'];
			$viewed = $row['viewedAt'];
		}elseif(isset($row['title']) && isset($row['thumb'])){
			$thumb = $row['thumb'];
			$title = $row['title'];
			$viewed = $row['viewedAt'];
		}else{
			continue;
		}

		// [grandparentThumb] /library/metadata/10554/thumb/1467121976
		if(preg_match("/metadata\/(\d+)\/thumb/", $thumb, $matches))
			$thumb_id = $matches[1];
		if(in_array($thumb_id, $already_seen))
			continue;
		$already_seen[] = $thumb_id;
		$poster = "http://127.0.0.1:32400".$thumb ."?X-Plex-Token=".$token;
		file_put_contents("./".$thumb_id.".jpg",
			@file_get_contents($poster));
		$file_contents = realpath('./'.$thumb_id.'.jpg');
		if (filesize($file_contents) == 0) continue;

		// post it to the server
		$ch = curl_init();

		$url = "http://sachawheeler.com/home/plexdata.php";
		$fields = array(
				'title' => urlencode($title),
				'id' => $thumb_id,
				'count' => $count,
				'viewed' => $viewed,
				'file_contents' => '@'.$file_contents
				);
		foreach($fields as $key=>$value)
			$fields_string .= $key.'='.$value.'&';
		rtrim($fields_string, '&');

		//set the url, number of POST vars, POST data
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_POST, 1);
		curl_setopt($ch,CURLOPT_POSTFIELDS, $fields);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);

		//execute post
		$result = curl_exec($ch);

		//close connection
		curl_close($ch);
		unlink("./".$thumb_id.".jpg");
		$count++;
		if($count == 4) break;
	}

 ?>
