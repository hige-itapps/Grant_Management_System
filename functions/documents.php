<?php
	ob_start();
	
	set_include_path('/home/egf897jck0fu/public_html/');
	include('Net/SFTP.php');

	/*list documents for a given app ID*/
	function listDocs($id) {
		$settings = parse_ini_file('config.ini');
		$ssh = new Net_SFTP('www.codigo-tech.com');
		if (!$ssh->login($settings["host_user"], "Default1234$")) {
			exit('Auth Failed');
		}
		return $ssh->nlist($settings["uploads_dir"] . $id);
	}
	
	/*Download a document for a given app ID @ document name*/
	function downloadDocs($id, $doc) {
		$settings = parse_ini_file('config.ini');
			
			$ssh = new Net_SFTP('www.codigo-tech.com');
		if (!$ssh->login($settings["host_user"], "Default1234$")) {
			$ssh->login($settings["host_user"], "Default1234$");
		}
		//file_put_contents($doc, $ssh->get($settings["uploads_dir"] . $id . '/' . $doc));
		header("Content-type:application/pdf");
		// It will be called downloaded.pdf
		header("Content-Disposition:attachment;filename=" . $doc);
		readfile($settings["uploads_dir"] . $id . '/' . $doc);
		exit('Downloaded');
	}
	
	/*Upload the documents; return true on success, false on failure*/
	function uploadDocs($id) {
		$settings = parse_ini_file('config.ini');
		if(isset($_FILES["fD"]))
		{
			if ($_FILES['fD']['size'] != 0)
			{
				$file = $_FILES["fD"]["tmp_name"];
				$doc = "P" . $id . ".pdf";
				
				
				$ssh = new Net_SFTP('www.codigo-tech.com');
				if (!$ssh->login($settings["host_user"], "Default1234$")) {
					//header("Location: application_builder.php?status=failedfileFu");
					return false;
				}
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
		}else{
			echo "Could not upload file, application NOT submitted.";
			//header("Location: application_builder.php?status=failedfileF");
			return false;
		}
		if(isset($_FILES["sD"]))
		{
			if ($_FILES['sD']['size'] != 0)
			{
				$file = $_FILES["sD"]["tmp_name"];
				$doc = "S" . $id . ".pdf";
				
				$ssh = new Net_SFTP('www.codigo-tech.com');
				if (!$ssh->login($settings["host_user"], "Default1234$")) {
					//header("Location: application_builder.php?status=failedfileSu");
					return false;
				}
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
		}else{
			echo "Could not upload file, application NOT submitted.";
			//header("Location: application_builder.php?status=failedfileS");
			return false;
		}
		
		/*everything was successful*/
		return true;
	}
?>