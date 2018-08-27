<?php
	/*Get DB connection*/
	include_once(dirname(__FILE__) . "/../functions/database.php");

	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;
	
	require dirname(__FILE__) . '/../PHPMAILER/src/PHPMailer.php';
	require dirname(__FILE__) . '/../PHPMAILER/src/SMTP.php';
 
	if (!class_exists('PHPMailer'))
		require_once dirname(__FILE__) . '/../PHPMAILER/src/Exception.php';
	if (!class_exists('PHPMailer'))
		require_once dirname(__FILE__) . '/../PHPMAILER/src/PHPMailer.php';
	if (!class_exists('PHPMailer'))
		require_once dirname(__FILE__) . '/../PHPMAILER/src/SMTP.php';

	//Send an email to a specific address, with a custom message and subject. If the subject is left blank, a default one is prepared instead.
	//NOTE- must save to the database first! Use the appID to save it correctly.
	function customEmail($appID, $toAddress, $customMessage, $customSubject) {
		/*VERY IMPORTANT! In order to utilize the config.ini file, we need to have the url to point to it! set that here:*/
		$config_url = dirname(__FILE__).'/../config.ini';
		$mailHost = parse_ini_file($config_url)["mail_host"]; //load mail host
		$mailUsername = parse_ini_file($config_url)["mail_username"]; //load mail username
		$mailPassword = parse_ini_file($config_url)["mail_password"]; //load mail password
		$mailPort = parse_ini_file($config_url)["mail_port"]; //load mail port number
		
		$data = array(); // array to pass back data

		$customSubject = trim($customSubject); //remove surrounding spaces
		if($customSubject == null || $customSubject === '')//it's blank, so just use a default subject
		{
			$customSubject = "IEFDF Application Update";
		}
		//custom footer to be attached to the end of every message
		$footer = "
		
		<strong>Please do not reply to this email, this account is not being monitored.
		If you need more information, please contact the IEFDF administrator (michelle.metro-roland@wmich.edu or 7-3908).</strong>";

		$fullMessage = $customMessage . $footer; //combine everything

		$conn = connection();//get DB connection

		$saveResult = saveEmail($conn, $appID, $customSubject, $fullMessage); //try to save the email message
		$data["saveSuccess"] = $saveResult; //save it to return it later

		if($saveResult === true) //if it saved, then try to send it
		{
			//insert <br>s where newlines are so the message renders correctly in email clients
			$fullMessage = nl2br($fullMessage);

			$mail = new PHPMailer(true); //set exceptions to true
			try{
				//Server settings
				//$mail->SMTPDebug = 2;                                 // Enable verbose debug output
				$mail->isSMTP();                                      // Set mailer to use SMTP
				$mail->Host = $mailHost;							  // Specify main and backup SMTP servers
				$mail->SMTPAuth = true;                               // Enable SMTP authentication
				$mail->Username = $mailUsername;				      // SMTP username
				$mail->Password = $mailPassword;	                  // SMTP password
				$mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
				$mail->Port = $mailPort;                              // TCP port to connect to

				//Recipients
				$mail->setFrom('hige_iefdf_not@wmich.edu', 'Mailer');
				$mail->addReplyTo('no-reply@wmich.edu', 'No-Reply');
					
				//Content
				$mail->isHTML(true);                                  // Set email format to HTML
				$mail->addAddress($toAddress);
				$mail->Subject = $customSubject;
				$mail->Body    = $fullMessage;

				$data["sendSuccess"] = $mail->send(); //notify of successful sending of message (or unsuccessful if it fails)
			}
			catch (Exception $e) {
				$data["sendSuccess"] = false; //notify of message sending failure
				$data["sendError"] = 'Message could not be sent. Mailer Error: '.$mail->ErrorInfo;
			}
		}

		$conn = null; //close connection

		return $data; //pass back the data array
	}

	//The email to send to the department chair to let them know of their needed approval. Let them know the applicant's name and email
	function chairApprovalEmail($appID, $toAddress, $applicantName, $applicantEmail)
	{
		$subject = "IEFDF Application - Chair Approval Required";

		$body = "Dear Department Chair, 
			Your approval is needed for an IEFDF application for #name (#email). Your name confirms that the applicant is part of the bargaining unit and therefore, eligible to receive IEFDF funds. Directions:

			1. Go to the IEFDF website at www.wmich.edu/international/iefdf

			2. Click on the application system log in

			3. Log in with your bronco net id

			4. Click on the link to view the application

			5. At the bottom of the page, type your name in the signature field

			6. Submit
			
			Best Regards, Dr. Michelle Metro-Roland";
		$body = str_replace("#name", nl2br($applicantName), $body); //insert the applicant's name into the message
		$body = str_replace("#email", nl2br($applicantEmail), $body); //insert the applicant's email into the message

		return customEmail($appID, $toAddress, $body, $subject);
	}
	
?>