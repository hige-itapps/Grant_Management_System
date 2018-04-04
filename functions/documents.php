<?php
	ob_start();
	
	include_once(dirname(__FILE__) . "/../Net/SFTP.php");
	
	
	if(isset($_POST["uploadDocs"]) || isset($_POST["uploadDocsF"]))
		uploadDocs($_POST["appID"]);

	/*list documents for a given app ID*/
	function listDocs($id) {
		$settings = parse_ini_file('config.ini');
		$ssh = new Net_SFTP('www.codigo-tech.com');
		if (!$ssh->login($settings["host_user"], "Default1234$")) {
			$success = 0;
			for($i = 0; $i <= 3 && $success == 0; $i++)
			{
				sleep(2);
				if($ssh->login($settings["host_user"], "Default1234$"))
					$success = 1;
			}
			if($success == 0)
				exit('Auth Failed');
		}
		$ret = $ssh->nlist($settings["uploads_dir"] . $id);
		unset($ssh);
		return $ret;
	}
	
	/*Download a document for a given app ID @ document name*/
	function downloadDocs($id, $doc) {
		$settings = parse_ini_file('config.ini');
			
			/*$ssh = new Net_SFTP('www.codigo-tech.com');
		if (!$ssh->login($settings["host_user"], "Default1234$")) {
			$success = 0;
			for($i = 0; $i <= 3 && $success == 0; $i++)
			{
				sleep(2);
				if($ssh->login($settings["host_user"], "Default1234$"))
					$success = 1;
			}
			if($success == 0)
				return false;
		}*/
		//file_put_contents($doc, $ssh->get($settings["uploads_dir"] . $id . '/' . $doc));
		header("Content-type:application/pdf");
		// It will be called downloaded.pdf
		header("Content-Disposition:attachment;filename=" . $doc);
		readfile($settings["uploads_dir"] . $id . '/' . $doc);
		exit('Downloaded');
	}
	/*
	function reArrayFiles($file)
	{
		$file_ary = array();
		$file_count = count($file['name']);
		$file_key = array_keys($file);
	   
		for($i=0;$i<$file_count;$i++)
		{
			foreach($file_key as $val)
			{
				$file_ary[$i][$val] = $file[$val][$i];
			}
		}
		return $file_ary;
	}
	*/
	/*Upload the documents; return true on success, false on failure*/
	function uploadDocs($id) {
		$settings = parse_ini_file('../config.ini');
		$ssh = new Net_SFTP('www.codigo-tech.com');
		if (!$ssh->login($settings["host_user"], "Default1234$")) {
			$success = 0;
			for($i = 0; $i <= 3 && $success == 0; $i++)
			{
				sleep(2);
				if($ssh->login($settings["host_user"], "Default1234$"))
					$success = 1;
			}
			if($success == 0)
				return false;
		}
		if(isset($_FILES["fD"]))
		{
			if ($_FILES['fD']['size'] != 0)
			{
				$file = $_FILES["fD"]["tmp_name"];
				$doc = "P" . $id . "-" . $_FILES["fD"]["name"];
				
				try
				{
					echo $ssh->exec('mkdir ' . $settings["uploads_dir"] . $id);
				}
				catch (Exception $e) 
				{
					echo 'Upload exception: ',  $e->getMessage(), "\n";
				}
				
				$ssh->put($settings["uploads_dir"] . $id . '/' . $doc, $file, NET_SFTP_LOCAL_FILE);
				
			}
		}
		$fsD = $_FILES["sD"];
		if(!empty($fsD))
		{
			for($i = 0; $i < count($fsD["name"]); $i++)
			{
				$file = $fsD["tmp_name"][$i];
				$doc = "S" . $id . "-" . $fsD["name"][$i];
				
				$ssh->put($settings["uploads_dir"] . $id . '/' . $doc, $file, NET_SFTP_LOCAL_FILE);
			}
		}
		$followD = $_FILES["followD"];
		if(!empty($followD))
		{
			for($i = 0; $i < count($followD["name"]); $i++)
			{
				$file = $followD["tmp_name"][$i];
				$doc = "F" . $id . "-" . $followD["name"][$i];
				
				$ssh->put($settings["uploads_dir"] . $id . '/' . $doc, $file, NET_SFTP_LOCAL_FILE);
			}
		}
		unset($ssh);
		/*everything was successful*/
		if(isset($_POST["uploadDocs"]))
			header("Location: ../application.php?id=" . $id);
		if(isset($_POST["uploadDocsF"]))
			header("Location: ../follow_up.php?id=" . $id);
		return true;
	}
?>