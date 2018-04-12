<?php
	ob_start();
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;

	
	/*Debug user validation*/
	/*include "include/debugAuthentication.php";*/
	include_once(dirname(__FILE__) . "/../include/CAS_login.php");

	//include('../Net/SFTP.php');
	include_once(dirname(__FILE__) . "/../functions/database.php");
	$conn = connection(); //connect to database
	
	include_once(dirname(__FILE__) . "/../functions/documents.php");
	
	/*Verification functions*/
	include_once(dirname(__FILE__) . "/../functions/verification.php");
	
	if (!class_exists('PHPMailer'))
		require_once dirname(__FILE__) . '/../PHPMAILER/src/Exception.php';
	if (!class_exists('PHPMailer'))
		require_once dirname(__FILE__) . '/../PHPMAILER/src/PHPMailer.php';
	if (!class_exists('PHPMailer'))
		require_once dirname(__FILE__) . '/../PHPMAILER/src/SMTP.php';
	
	// Please specify your Mail Server - Example: mail.example.com.
	ini_set("SMTP","mail.example.com");

	// Please specify an SMTP Number 25 and 8889 are valid SMTP Ports.
	ini_set("smtp_port","25");

	// Please specify the return address to use
	ini_set('sendmail_from', 'info@hige.com');
	
	$isAdmin = isAdministrator($conn, $CASbroncoNetId);

	/*Verify that user is allowed to make an application*/
	if(isUserAllowedToCreateApplication($conn, $CASbroncoNetId, $CASallPositions, true) || $isAdmin)
	{
		//echo "User is allowed to create an application!";
		
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

			/*get nextCycle or currentCycle*/
			$nextCycle = 0;

			if(isset($_POST["cycleChoice"]))
			{
				if(strcmp($_POST["cycleChoice"], "next") == 0) //user chose to submit next cycle
				{$nextCycle = 1;}
			}

			
			//echo "current broncoNetID: ".$_SESSION['broncoNetID'];
			
			/*Insert data into database - receive the new application id if success, or 0 if failure*/
			/*parameters: DB connection, name, email, department, dep. mail stop, dep. chair email, travel from, travel to, activity from, activity to, title, destination, amount requested,
			purpose1, purpose2, purpose3, purpose4Other, other funding, proposal summary, goal1, goal2, goal3, goal4, budgetArray*/
			if($isAdmin)
			{
				$successAppID = insertApplication($conn, true, $_POST["updateID"], $CASbroncoNetId, $_POST["inputName"], $_POST["inputEmail"], $_POST["inputDept"], $_POST["inputDeptCE"], 
					$_POST["inputTFrom"], $_POST["inputTTo"], $_POST["inputAFrom"], $_POST["inputATo"], $_POST["inputRName"], $_POST["inputDest"], $_POST["inputAR"], 
					$pr1, $pr2, $pr3, $pr4, $_POST["eS"], $_POST["props"], $pg1, $pg2, $pg3, $pg4, $nextCycle, $budgetArray);
			}
			else
			{
				$successAppID = insertApplication($conn, false, null, $CASbroncoNetId, $_POST["inputName"], $_POST["inputEmail"], $_POST["inputDept"], $_POST["inputDeptCE"], 
					$_POST["inputTFrom"], $_POST["inputTTo"], $_POST["inputAFrom"], $_POST["inputATo"], $_POST["inputRName"], $_POST["inputDest"], $_POST["inputAR"], 
					$pr1, $pr2, $pr3, $pr4, $_POST["eS"], $_POST["props"], $pg1, $pg2, $pg3, $pg4, $nextCycle, $budgetArray);
			}
			
			echo "<br>Insert status: ".$successAppID.".<br>";
			
			$successUpload = 0; //initialize value to 0, should be made to something > 0 if upload is successful
			
			if($successAppID > 0) //if insert into DB was successful, continue
			{
				echo "<br>Uploading docs...<br>";
				$successUpload = uploadDocs($successAppID); //upload the documents
				
				echo "<br>Upload status: ".$successUpload.".<br>";
			}
			else
			{
				echo "<br>ERROR: could not insert application, app status: ".$successAppID."!<br>";
			}
			
			if($successUpload > 0 && !$isAdmin) //upload was successful- send email if not administrator
			{
				$body = "<p>Dear Department Chair, </p>
					<p>Your approval is needed for an IEFDF application for #name. Your name confirms that the applicant is part of the bargaining unit and therefore, eligible to receive IEFDF funds. Directions:</p>

					<p>1. Go to the IEFDF website at www.wmich.edu/international/iefdf</p>

					<p>2. Click on the application system log in</p>

					<p>3. Log in with your bronco net id</p>

					<p>4. Click on the link to view the application</p>

					<p>5. At the bottom of the page, type your name in the signature field</p>

					<p>6. Submit</p>

					<p>If you have questions please contact Dr. Michelle Metro-Roland (michelle.metro-roland@wmcih.edu) or 7-3908.</p>
					
					<p>Best Regards, Dr. Michelle Metro-Roland</p>";//file_get_contents('customEmail.html');
				$body = str_replace("#name", nl2br($_POST["inputName"]), $body);
				$body = str_replace("#dept", nl2br($_POST["inputDept"]), $body);

				
				
				$mail = new PHPMailer(true); 
				//Server settings
				$mail->SMTPDebug = 2;                                 // Enable verbose debug output
				$mail->isSMTP();                                      // Set mailer to use SMTP
				$mail->Host = 'outlook.office365.com';  // Specify main and backup SMTP servers
				$mail->SMTPAuth = true;                               // Enable SMTP authentication
				$mail->Username = 'hige_iefdf_not@wmich.edu';         // SMTP username
				$mail->Password = 'r!cr8juqUwe=';                     // SMTP password
				$mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
				$mail->Port = 587;                                    // TCP port to connect to

				//Recipients
				$mail->setFrom('hige_iefdf_not@wmich.edu', 'Mailer');
				$mail->addReplyTo('no-reply@wmich.edu', 'No-Reply');
				//Content
				$mail->isHTML(true);                                  // Set email format to HTML
				$mail->addAddress($_POST["inputDeptCE"]);
				$mail->Subject = "New HIGE Grant Application - Do Not Reply";
				$mail->Body    = $body;

				$mail->send();
				
				//redirect back to homepage
				//header('Location: /');
				header('Location: ../index.php');
			}
			else if($successUpload > 0 && $isAdmin) //upload was successful for admin, so go back
			{
				header('Location: ../index.php');
			}
			else
			{
				echo "<br>ERROR: could not upload application documents, upload status: ".$successUpload."!<br>";
			}
			
		}
		catch(Exception $e)
		{
			echo "Error adding application: " . $e->getMessage();
		}
		
	}
	
	$conn = null; //close connection -- NOT NEEDED.
?>