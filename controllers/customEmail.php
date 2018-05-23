<?php

	ob_start();
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

	
	function customEmail($email, $eb) {
			
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
		$mail->addAddress($email);
		$mail->Subject = "IEFDF Application Update";
		$mail->Body    = $eb . "<br><br><b>Please do not reply to this email, this account is not being monitored.<br>If you need more information, please contact the IEFDF administrator directly.</b>";

		$mail->send();
		
	}
	
?>