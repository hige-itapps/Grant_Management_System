<?php
	ob_start();
	$settings = parse_ini_file('functions/config.ini');
	if(isset($_FILES["fD"]))
	{
		$file = $_FILES["fD"]["tmp_name"];
		$doc = $_FILES["fD"]["name"];
		$ftp = ftp_connect($settings["hostname"]) or die("Could not connect to $ftp_server");
		$login = ftp_login($ftp, $settings["host_user"], "Default1234$");
		ftp_pasv($ftp, true);
		if(ftp_put($ftp, $settings["uploads_dir"] . $doc, $file, FTP_BINARY))
		{
			ftp_close($ftp);
		}else{
			echo "Could not upload file, application NOT submitted.";
			ftp_close($ftp);
			//header("Location: application_builder.php?status=failedfileF");
		}
	}else{
		echo "Could not upload file, application NOT submitted.";
		//header("Location: application_builder.php?status=failedfileF");
	}
	if(isset($_FILES["sD"]))
	{
		$file = $_FILES["sD"]["tmp_name"];
		$doc = $_FILES["sD"]["name"];
		$ftp = ftp_connect($settings["hostname"]) or die("Could not connect to $ftp_server");
		$login = ftp_login($ftp, $settings["host_user"], "Default1234$");
		ftp_pasv($ftp, true);
		if(ftp_put($ftp, $settings["uploads_dir"] . $doc, $file, FTP_BINARY))
		{
			ftp_close($ftp);
		}else{
			echo "Could not upload file, application NOT submitted.";
			ftp_close($ftp);
			//header("Location: application_builder.php?status=failedfileS");
		}
	}else{
		echo "Could not upload file, application NOT submitted.";
		//header("Location: application_builder.php?status=failedfileS");
	}
	insert();
	function connection()
	{
		try 
		{
			$settings = parse_ini_file('functions/config.ini');
			//var_dump($settings);
			$conn = new PDO("mysql:host=" . $settings["hostname"] . ";dbname=" . $settings["database_name"] . ";charset=utf8", $settings["database_username"], 
				$settings["database_password"], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
			// set the PDO error mode to exception
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$conn->setAttribute( PDO::ATTR_EMULATE_PREPARES, false );
			//echo "Connected successfullyn\n\n\n\n\n"; 
			return $conn;
		}
		catch(PDOException $e)
		{
			echo "Connection failed: " . $e->getMessage();
		}
	}
	function insert()
	{
		try
		{
			$pr1 = 0;
			if(isset($_POST["pr1"]))
				$pr1 = 1;
			$pr2 = 0;
			if(isset($_POST["pr2"]))
				$pr2 = 1;
			$pr3 = 0;
			if(isset($_POST["pr3"]))
				$pr3 = 1;
			$pr4 = "";
			if(isset($_POST["pr4"]))
				$pr4 = $_POST["pr4"];
			$pg1 = 0;
			if(isset($_POST["pg1"]))
				$pg1 = 1;
			$pg2 = 0;
			if(isset($_POST["pg2"]))
				$pg2 = 1;
			$pg3 = 0;
			if(isset($_POST["pg3"]))
				$pg3 = 1;
			$pg4 = 0;
			if(isset($_POST["pg4"]))
				$pg4 = 1;
			$dummyBNID = 'pol0808';
			$date = date("Y/m/d");
			$conn = connection(); 
			$sql = $conn->prepare("INSERT INTO applications VALUES (DEFAULT, :applicant, :date, :name, 
				:dept, :dmail, :email, :rtitle, :tstart, :tend, :estart, :eend, :dest, :ar, :isr, :isc,
				:isca, :iso, :of, :ps, :fg1, :fg2, :fg3, :fg4, :dpce, :dpcs, :apr)");
			$sql->bindParam(':applicant', $dummyBNID);
			$sql->bindParam(':date', $date);
			$sql->bindParam(':name', $_POST["inputName"]);
			$sql->bindParam(':dept', $_POST["inputDept"]);
			$sql->bindParam(':dmail', $_POST["inputDeptM"]);
			$sql->bindParam(':email', $_POST["inputEmail"]);
			$sql->bindParam(':rtitle', $_POST["inputRName"]);
			$sql->bindParam(':tstart', $_POST["inputTFrom"]);
			$sql->bindParam(':tend',  $_POST["inputTTo"]);
			$sql->bindParam(':estart',  $_POST["inputAFrom"]);
			$sql->bindParam(':eend',  $_POST["inputTTo"]);
			$sql->bindParam(':dest',  $_POST["inputDest"]);
			$sql->bindParam(':ar',  $_POST["inputAR"]);
			$sql->bindParam(':isr', $pr1);
			$sql->bindParam(':isc', $pr2);
			$sql->bindParam(':isca', $pr3);
			$sql->bindParam(':iso', $pr4);
			$otherF = $_POST["eS"];
			$sql->bindParam(':of', $otherF);
			$sql->bindParam(':ps',  $_POST["props"]);
			$sql->bindParam(':fg1', $pg1);
			$sql->bindParam(':fg2', $pr2);
			$sql->bindParam(':fg3', $pr3);
			$sql->bindParam(':fg4',$pr4);
			$dpce = 'adw';
			$dpcs = 'adq';
			$dp = '1';
			$sql->bindParam(':dpce', $dpce);
			$sql->bindParam(':dpcs', $dpcs);
			$sql->bindParam(':apr', $dp);
			$sql->execute();
			header("Location: application_builder.php?status=success");
		}
		catch(Exception $e)
		{
			echo "Error: " . $e->getMessage();
		}
	}
?>