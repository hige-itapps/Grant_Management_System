<?php

	ob_start();
	function approvalEmail($email, $eb) {
		
		$to = $email;
		$body = $eb;

		$subject = "Your HIGE Grant Application has been Approved";

		$headers = "From: HIGE <donotreply@codigo-tech.com> \r\n";
		$headers .= "Reply-To: info@codigo-tech.com \r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
		$headers .= "X-Priority: 1 (Highest)\n";
		$headers .= "X-MSMail-Priority: High\n";
		$headers .= "Importance: High\n";
			
		if(!mail($to, $subject, $body, $headers))
			file_put_contents('./mail_log.txt', 'mail failed', FILE_APPEND);
		
	}
	
	
	function denialEmail($email, $eb) {
		
		$to = $email;
		$body = $eb;

		$subject = "Your HIGE Grant Application has been Denied";

		$headers = "From: HIGE <donotreply@codigo-tech.com> \r\n";
		$headers .= "Reply-To: info@codigo-tech.com \r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
		$headers .= "X-Priority: 1 (Highest)\n";
		$headers .= "X-MSMail-Priority: High\n";
		$headers .= "Importance: High\n";
		
		if(!mail($to, $subject, $body, $headers))
			file_put_contents('./mail_log.txt', 'mail failed', FILE_APPEND);
	}

	
	function onHoldEmail($email, $eb) {
		
		$to = $email;
		$body = $eb;

		$subject = "Your HIGE Grant Application is on Hold";

		$headers = "From: HIGE <donotreply@codigo-tech.com> \r\n";
		$headers .= "Reply-To: info@codigo-tech.com \r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
		$headers .= "X-Priority: 1 (Highest)\n";
		$headers .= "X-MSMail-Priority: High\n";
		$headers .= "Importance: High\n";
			
		if(!mail($to, $subject, $body, $headers))
			file_put_contents('./mail_log.txt', 'mail failed', FILE_APPEND);
		
	}
	
?>