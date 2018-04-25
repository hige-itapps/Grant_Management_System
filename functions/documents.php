<?php
	ob_start();
	
	include_once(dirname(__FILE__) . "/../Net/SFTP.php");
	
	
	/*if(isset($_REQUEST["uploadDocs"]) || isset($_REQUEST["uploadDocsF"]))
		uploadDocs($_REQUEST["updateID"]);*/

	/*list documents for a given app ID*/
	function listDocs($id) {
		$settings = parse_ini_file(dirname(__FILE__) . '/../config.ini');
		$ssh = new Net_SFTP($settings["hostname"]);
		if (!$ssh->login($settings["host_user"], $settings["host_password"])) {
			$success = 0;
			for($i = 0; $i <= 3 && $success == 0; $i++)
			{
				sleep(2);
				if($ssh->login($settings["host_user"], $settings["host_password"]))
					$success = 1;
			}
			if($success == 0)
				exit('Auth Failed');
		}
		$ret = $ssh->nlist($id);
		unset($ssh);
		return $ret;
	}
	
	/*Download a document for a given app ID @ document name*/
	function downloadDocs($id, $doc) {
		$settings = parse_ini_file(dirname(__FILE__) . '/../config.ini');
			
		$ssh = new Net_SFTP('hige-iefdf-vm.wade.wmich.edu');
		if (!$ssh->login($settings["host_user"], $settings["host_password"])) {
			$success = 0;
			for($i = 0; $i <= 3 && $success == 0; $i++)
			{
				sleep(2);
				if($ssh->login($settings["host_user"], $settings["host_password"]))
					$success = 1;
			}
			if($success == 0)
				return false;
		}
		//file_put_contents($doc, $ssh->get($id . '/' . $doc));
		//$file = $settings["hostname"] . '/uploads/' . $id . '/' . $doc;
		$file = dirname(__FILE__) . '/../uploads/' . $id . '/' . $doc;
		header('Connection: Keep-Alive');
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="'.basename($file).'"');
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . filesize($file));
		ob_clean();
		flush();
		readfile($file);
	}
	/*Upload the documents; return true on success, false on failure*/
	function uploadDocs($id) {
		$settings = parse_ini_file(dirname(__FILE__) . '/../config.ini');
		$ssh = new Net_SFTP($settings["hostname"]);
		if (!$ssh->login($settings["host_user"], $settings["host_password"])) {
			$success = 0;
			for($i = 0; $i <= 3 && $success == 0; $i++)
			{
				sleep(2);
				if($ssh->login($settings["host_user"], $settings["host_password"]))
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
					echo $ssh->mkdir($id);
				}
				catch (Exception $e) 
				{
					echo 'Upload exception: ',  $e->getMessage(), "\n";
				}
				
				$ssh->put(/*$settings["uploads_dir"] . */$id . '/' . $doc, $file, NET_SFTP_LOCAL_FILE);
				
			}
		}
		$fsD = $_FILES["sD"];
		if(!empty($fsD))
		{
			for($i = 0; $i < count($fsD["name"]); $i++)
			{
				if ($fsD['size'][$i] != 0)
				{
					$file = $fsD["tmp_name"][$i];
					$doc = "S" . $id . "-" . $fsD["name"][$i];
					
					$ssh->put(/*$settings["uploads_dir"] . */$id . '/' . $doc, $file, NET_SFTP_LOCAL_FILE);
				}
			}
		}
		if(isset($_FILES["followD"]))
		{
			$followD = $_FILES["followD"];
			if(!empty($followD))
			{
				for($i = 0; $i < count($followD["name"]); $i++)
				{
					if ($followD['size'][$i] != 0)
					{
						$file = $followD["tmp_name"][$i];
						$doc = "F" . $id . "-" . $followD["name"][$i];
						
						$ssh->put(/*$settings["uploads_dir"] . */$id . '/' . $doc, $file, NET_SFTP_LOCAL_FILE);
					}
				}
			}
		}
		unset($ssh);
		/*everything was successful*/
		if(isset($_REQUEST["uploadDocs"]))
			header("Location: ../application.php?id=" . $id);
		if(isset($_REQUEST["uploadDocsF"]))
			header("Location: ../follow_up.php?id=" . $id);
		return true;
	}
?>