<?php

	//ob_start();
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
			
		$customSubject = trim($customSubject); //remove surrounding spaces
		if($customSubject == null || $customSubject === '')//it's blank, so just use a default subject
		{
			$customSubject = "IEFDF Application Update";
		}
		//custom footer to be attached to the end of every message
		$footer = "<br><br><b>Please do not reply to this email, this account is not being monitored.<br>If you need more information, please contact the IEFDF administrator directly.</b>";


		$mail = new PHPMailer(true); 
		//Server settings
		//$mail->SMTPDebug = 2;                                 // Enable verbose debug output
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
		$mail->addAddress($toAddress);
		$mail->Subject = $customSubject;
		$mail->Body    = $customMessage . $footer;

		$mail->send();
	}

	//The email to send to the department chair to let them know of their needed approval. Let them know the applicant's name and email
	function chairApprovalEmail($toAddress, $applicantName, $applicantEmail)
	{
		$subject = "New HIGE Grant Application - Do Not Reply";

		$body = "<p>Dear Department Chair, </p>
			<p>Your approval is needed for an IEFDF application for #name(#email). Your name confirms that the applicant is part of the bargaining unit and therefore, eligible to receive IEFDF funds. Directions:</p>

			<p>1. Go to the IEFDF website at www.wmich.edu/international/iefdf</p>

			<p>2. Click on the application system log in</p>

			<p>3. Log in with your bronco net id</p>

			<p>4. Click on the link to view the application</p>

			<p>5. At the bottom of the page, type your name in the signature field</p>

			<p>6. Submit</p>

			<p>If you have questions please contact Dr. Michelle Metro-Roland (michelle.metro-roland@wmcih.edu) or 7-3908.</p>
			
			<p>Best Regards, Dr. Michelle Metro-Roland</p>";
		$body = str_replace("#name", nl2br($applicantName), $body); //insert the applicant's name into the message
		$body = str_replace("#email", nl2br($applicantEmail), $body); //insert the applicant's email into the message

		customEmail($toAddress, $body, $subject);
	}
	
?>