<?php

if((!empty($_FILES["pdb_file"])) && ($_FILES['pdb_file']['error'] == 0)) {

	include "config/upload_defs.php";

	try {
		// open administration db
		$db = new PDO(DB,DB_USER,DB_PWD);
// 		$db = new PDO("sqlite:admin.db");

		// delete old id: db entry and files
		$query_1 = $db->prepare("SELECT ID FROM log_file_analysis_upload_admin WHERE TIMESTAMPDIFF(SECOND,Timestamp,CURRENT_TIMESTAMP()) > ".SEC_COUNT_ID." AND ID<>'".SPECIAL_ID."'");
// 		$query_1 = $db->prepare("SELECT ID FROM Sessions WHERE strftime('%s','now')-strftime('%s',Timestamp) > ".SEC_COUNT_ID);
		if($query_1->execute()) {
			while($row_1 = $query_1->fetch(PDO::FETCH_ASSOC)) {
				$query_2 = $db->prepare("DELETE FROM log_file_analysis_upload_admin WHERE ID=\"".$row_1['ID']."\"");
				$query_2->execute();
				unlink("upload/".$row_1['ID'].".pdb");
			}
		}

		// fill stats db with upload file attempt
		$query_1 = $db->prepare("SELECT COUNT(*) as count FROM log_file_analysis_upload_admin");
		$query_1->execute();
		if($row_1 = $query_1->fetch(PDO::FETCH_ASSOC)) {
			$max_uploads_same_time = $row_1['count']+1;
		} else {
			$max_uploads_same_time = 0;
		}
		
		$query_1 = $db->prepare("SELECT Date,Total_Uploads,Max_Uploads_Same_Time FROM log_file_analysis_upload_stats WHERE Date=CURRENT_DATE()");
		$query_1->execute();
		if($row_1 = $query_1->fetch(PDO::FETCH_ASSOC)) {
			if(!$row_1['Max_Uploads_Same_Time'] | $max_uploads_same_time > $row_1['Max_Uploads_Same_Time']) {
				$query_1 = $db->prepare("UPDATE log_file_analysis_upload_stats SET Total_Uploads=Total_Uploads+1,Max_Uploads_Same_Time=".$max_uploads_same_time." WHERE Date=CURRENT_DATE()");
			} else {
				$query_1 = $db->prepare("UPDATE log_file_analysis_upload_stats SET Total_Uploads=Total_Uploads+1 WHERE Date=CURRENT_DATE()");
			}
			$query_1->execute();
		} else {
			$query_1 = $db->prepare("INSERT INTO log_file_analysis_upload_stats (Date,Total_Uploads,Successful_Uploads,Max_Uploads_Same_Time) VALUES (CURRENT_DATE(),1,0,".$max_uploads_same_time.")");
			$query_1->execute();
		}

		// check maximum total number of id
		$query_1 = $db->prepare("SELECT count(*) as count_id FROM log_file_analysis_upload_admin");
		$query_1->execute();
		if($row_1 = $query_1->fetch(PDO::FETCH_ASSOC)) {
			$count_id = $row_1['count_id'];
		} else {
			$count_id = COUNT_ID;
		}
		if($count_id < COUNT_ID) {
	
			if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
			  $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
			}
			$ip = $_SERVER['REMOTE_ADDR'];
	
			// check maximum number of id for transmitted ip
			$query_1 = $db->prepare("SELECT count(*) as count_ip FROM log_file_analysis_upload_admin WHERE IP=\"".$ip."\" AND CURRENT_TIMESTAMP()-Timestamp <= ".SEC_COUNT_IP);
// 			$query_1 = $db->prepare("SELECT count(*) as count_ip FROM Sessions WHERE IP=\"".$ip."\" AND strftime('%s','now')-strftime('%s',Timestamp) <= ".SEC_COUNT_IP);
			$query_1->execute();
			if($row_1 = $query_1->fetch(PDO::FETCH_ASSOC)) {
				$count_ip = $row_1['count_ip'];
			} else {
				$count_ip = COUNT_IP;
			}
			
			if($count_ip < COUNT_IP) {

				//$filename = basename($_FILES['pdb_file']['name']);
				//$ext = substr($filename, strrpos($filename, '.') + 1);

				// forward pdbfile to https://pokerth.net/log_file_analysis/upload.php
				$data = array(
						'pdb_file' => new CURLFile($_FILES['pdb_file']['tmp_name'],$_FILES['pdb_file']['type'], $_FILES['pdb_file']['name']),
				); 

				//**Note :CURLFile class will work if you have PHP version >= 5**

				$ch = curl_init();

				curl_setopt($ch, CURLOPT_URL, "https://pokerth.net/log_file_analysis/upload.php");
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
				curl_setopt($ch, CURLOPT_TIMEOUT, 86400); // 1 Day Timeout
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60000);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);

				$response = curl_exec($ch);
				if (curl_errno($ch)) {

						$msg = "ERROR ".ERROR_ID;
				} else {
						$msg = $response;
				}

				curl_close($ch);
				echo $msg;

			} else echo "ERROR ".ERROR_MAX_NUM_IP;
		} else echo "ERROR ".ERROR_MAX_NUM_TOTAL;

		$db = null;

	} catch (PDOException $e) {

		echo "ERROR ".ERROR_OPEN_DB;
		die();

	}

} else echo "ERROR ".ERROR_NO_FILE;

function generateHash() {
	$result = "";
	$charPool = '0123456789abcdefghijklmnopqrstuvwxyz';
	for($p = 0; $p<15; $p++)
		$result .= $charPool[mt_rand(0,strlen($charPool)-1)];
	return sha1($result);
}

?> 