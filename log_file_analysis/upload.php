<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

if((!empty($_FILES["pdb_file"])) && ($_FILES['pdb_file']['error'] == 0)) {

	include "config/upload_defs.php";

	try {
		// open administration db
		$db = new PDO("mysql:host=mariadb;dbname=".DB,DB_USER,DB_PWD);
		// $db = new PDO(DB,DB_USER,DB_PWD);
// 		$db = new PDO("sqlite:admin.db");

		// delete old id: db entry and files
		$query_1 = $db->prepare("SELECT ID FROM log_file_analysis_upload_admin WHERE TIMESTAMPDIFF(SECOND,Timestamp,CURRENT_TIMESTAMP()) > ".SEC_COUNT_ID." AND ID<>'".SPECIAL_ID."'");
		// $query_1 = $db->prepare("SELECT ID FROM Sessions WHERE strftime('%s','now')-strftime('%s',Timestamp) > ".SEC_COUNT_ID);
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

				$filename = basename($_FILES['pdb_file']['name']);
				$ext = substr($filename, strrpos($filename, '.') + 1);

				// check file size
				if($_FILES["pdb_file"]["size"] < 2000000) {

					// check file extension
					if($ext == "pdb") {

						// check sqlite-header
						$header_string = bin2hex(file_get_contents($_FILES['pdb_file']['tmp_name'],NULL,NULL,0,16));
						$max_emb_payload_frac = hexdec(bin2hex(file_get_contents($_FILES['pdb_file']['tmp_name'],NULL,NULL,21,1)));
						$min_emb_payload_frac = hexdec(bin2hex(file_get_contents($_FILES['pdb_file']['tmp_name'],NULL,NULL,22,1)));
						$reserved = hexdec(bin2hex(file_get_contents($_FILES['pdb_file']['tmp_name'],NULL,NULL,68,24)));
						if($header_string=="53514c69746520666f726d6174203300" & $max_emb_payload_frac==64 & $min_emb_payload_frac==32 & $reserved==0) {

							// generate id
							$id = generateHash();

							// test if id already exist
							$query_1 = $db->prepare("SELECT ID FROM log_file_analysis_upload_admin WHERE ID=\"".$id."\"");
							$query_1->execute();

							if(!($row_1 = $query_1->fetch(PDO::FETCH_ASSOC))) {

								$newname = $id.".pdb";
								// move uploaded file to the folder
								if(move_uploaded_file($_FILES['pdb_file']['tmp_name'],"upload/".$newname)) {
								//if(copy($_FILES['pdb_file']['tmp_name'],"upload/".$newname)) {

									// fill administration db with id, timestamp, ip
									$query_1 = $db->prepare("INSERT INTO log_file_analysis_upload_admin (ID,Timestamp,IP) VALUES (\"".$id."\",CURRENT_TIMESTAMP(),\"".$ip."\")");
// 									$query_1 = $db->prepare("INSERT INTO Sessions (ID,Timestamp,IP) VALUES (\"".$id."\",datetime('now'),\"".$ip."\")");
									$query_2 = $db->prepare("UPDATE log_file_analysis_upload_stats SET Successful_Uploads=Successful_Uploads+1 WHERE Date=CURRENT_DATE()");
									if($query_1->execute() & $query_2->execute()) {
										echo "OK ".$id;
									} else {
										unlink("upload/".$newname);
										echo "ERROR ".ERROR_INSERT_DB;
									}
								} else echo "ERROR ".ERROR_FILE_MOVE;
							} else echo "ERROR ".ERROR_ID;
						} else echo "ERROR ".ERROR_FILE_HEAD;
					} else echo "ERROR ".ERROR_FILE_EXT;
				} else echo "ERROR ".ERROR_FILE_SIZE;
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