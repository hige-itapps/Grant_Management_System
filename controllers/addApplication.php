<?php
	ob_start();
	include('../Net/SFTP.php');
	include('../functions/database.php');
	
	/* APPROVE APPLICATION */
	/*if(isset($_POST["approveA"]))
		approveApplication(connection(), $_POST["appID"]);*/
	
	/* DENY APPLICATION */
	/*if(isset($_POST["denyA"]))
		denyApplication(connection(), $_POST["appID"]);*/
	
	if(isset($_POST["sub"])) //submit button to form
	{
		$tempConn = connection(); //connect to database
		
		try
		{
			/*Set budgetArray*/
			$budgetArray = [[]];
			$count = 0; //index. Use this +1 to find name of current index (see below)
			
			while(true) //loop until no more budget items remaining
			{
				if(isset($_POST["amount" . ($count+1)])) {//make sure this index is used
					$budgetArray[$count][0] = $_POST["expense" . ($count+1)];
					$budgetArray[$count][1] = $_POST["comm" . ($count+1)];
					$budgetArray[$count][2] = $_POST["amount" . ($count+1)];
				}else{
					break;
				}
				$count++;
			}
			
			/*get the 4 purposes and 4 goals*/
			$pr1 = 0; $pr2 = 0; $pr3 = 0; $pr4 = ""; 
			$pg1 = 0; $pg2 = 0; $pg3 = 0; $pg4 = 0;
			if(isset($_POST["purpose1"])){$pr1 = 1;}
			if(isset($_POST["purpose2"])){$pr2 = 1;}
			if(isset($_POST["purpose3"])){$pr3 = 1;}
			if(isset($_POST["purposeOther"])){$pr4 = $_POST["purposeOther"];}
			if(isset($_POST["goal1"])){$pg1 = 1;}
			if(isset($_POST["goal2"])){$pg2 = 1;}
			if(isset($_POST["goal3"])){$pg3 = 1;}
			if(isset($_POST["goal4"])){$pg4 = 1;}
			
			
			/*Insert data into database - receive the new application id if success, or 0 if failure*/
			/*parameters: DB connection, name, email, department, dep. mail stop, dep. chair email, travel from, travel to, activity from, activity to, title, destination, amount requested,
			purpose1, purpose2, purpose3, purpose4Other, other funding, proposal summary, goal1, goal2, goal3, goal4, budgetArray*/
			$successAppID = insertApplication($tempConn, $_POST["inputName"], $_POST["inputEmail"], $_POST["inputDept"], $_POST["inputDeptM"], $_POST["inputDeptCE"], 
				$_POST["inputTFrom"], $_POST["inputTTo"], $_POST["inputAFrom"], $_POST["inputATo"], $_POST["inputRName"], $_POST["inputDest"], $_POST["inputAR"], 
				$pr1, $pr2, $pr3, $pr4, $_POST["eS"], $_POST["props"], $pg1, $pg2, $pg3, $pg4, 
				$budgetArray);
				
			echo "Insert status: ".$successAppID.".";
			if($successAppID > 0) //if insert into DB was successful, continue
			{
				echo "Now going to upload docs";
				$successUpload = uploadDocs($successAppID); //upload the documents
				
				echo "Upload status: ".$successUpload.".";
			}
		}
		catch(Exception $e)
		{
			echo "Error adding application: " . $e->getMessage();
		}
		
		$tempConn = null; //close connection
	}
	
	/*Upload the documents; return true on success, false on failure*/
	function uploadDocs($id) {
		$settings = parse_ini_file('../config.ini');
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

				echo $ssh->exec('mkdir ' . $settings["uploads_dir"] . $id);
				$ssh->put($settings["uploads_dir"] . $id . '/' . $doc, $file, NET_SFTP_LOCAL_FILE);
				
				echo $sftp->getSFTPLog();
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

				echo $ssh->exec('mkdir ' . $settings["uploads_dir"] . $id);
				$ssh->put($settings["uploads_dir"] . $id . '/' . $doc, $file, NET_SFTP_LOCAL_FILE);
				
				
				echo $sftp->getSFTPLog();
			}
		}else{
			echo "Could not upload file, application NOT submitted.";
			//header("Location: application_builder.php?status=failedfileS");
			return false;
		}
		
		/*everything was successful*/
		return true;
	}
	
	function listDocs($id) {
		$settings = parse_ini_file('functions/config.ini');
		$ssh = new Net_SFTP('www.codigo-tech.com');
		if (!$ssh->login($settings["host_user"], "Default1234$")) {
			exit('Auth Failed');
		}
		return $ssh->nlist($settings["uploads_dir"] . $id);
	}
	if(isset($_GET["doc"]))
	{
		downloadDocs($_GET["id"], $_GET["doc"]);
	}
	function downloadDocs($id, $doc) {
		$settings = parse_ini_file('functions/config.ini');
			
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
?>