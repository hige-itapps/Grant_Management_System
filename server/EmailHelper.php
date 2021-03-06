<?php
/*This class is used to save and send emails.*/

/*Get DB connection*/
include_once(dirname(__FILE__) . "/DatabaseHelper.php");

/*Use PHP mailer functions*/
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

/*Logger*/
include_once(dirname(__FILE__) . "/Logger.php");

class EmailHelper
{
	private $thisLocation; //get current location of file for logging purposes;
	private $logger; //for logging to files
	private $mailHost; //mail server information from config.ini
	private $mailAddress; //public address
	private $mailUsername; //private login username
	private $mailNoReply; //noreply address
	private $mailPassword;
	private $mailPort;
	private $defaultSubject; //the default subject line for when it isn't specified
	private $customFooter; //custom email footer to be attached to the bottom of every sent message

	/* Constructior retrieves configurations and initializes private vars */
	public function __construct($logger){
		$this->thisLocation = dirname(__FILE__).DIRECTORY_SEPARATOR.basename(__FILE__);

		$this->logger = $logger;
		$config_url = dirname(__FILE__).'/../config.ini'; //set config file url
		$settings = parse_ini_file($config_url); //get all settings		
		$this->mailHost = $settings["mail_host"]; //load mail host
		$this->mailAddress = $settings["mail_address"]; //load mail address
		$this->mailUsername = $settings["mail_username"]; //load mail username
		$this->mailNoReply = $settings["mail_noreply"]; //load mail no-reply address
		$this->mailPassword = $settings["mail_password"]; //load mail password
		$this->mailPort = $settings["mail_port"]; //load mail port number

		$this->defaultSubject = "IEFDF Application Update";
		$this->customFooter = "

		<strong>Please do not reply to this email, this account is not being monitored.
		If you need more information, please contact the IEFDF administrator (michelle.metro-roland@wmich.edu or 387-3908).</strong>";
	}

	//Send an email to a specific address, with a custom message and subject. If the subject is left blank, a default one is prepared instead.
	//NOTE- must save to the database first! Use the appID to save it correctly.
	public function customEmail($appID, $toAddress, $customMessage, $customSubject, $CASbroncoNetID) {
		$this->logger->logInfo("Sending Email", $CASbroncoNetID, $this->thisLocation);

		$data = array(); // array to pass back data

		$customSubject = trim($customSubject); //remove surrounding spaces
		if($customSubject == null || $customSubject === ''){//it's blank, so just use the default subject
			$customSubject = $this->defaultSubject;
		}

		$fullMessage = $customMessage . $this->customFooter; //combine everything

		$database = new DatabaseHelper($this->logger); //database helper object used for some verification and insertion

		$saveResult = $database->saveEmail($appID, $customSubject, $fullMessage); //try to save the email message
		$data["saveSuccess"] = $saveResult; //save it to return it later
		$data["sendSuccess"] = false; //initialize to false, set to true if it sends correctly

		if($saveResult === true){//if it saved, then try to send it
			//insert <br>s where newlines are so the message renders correctly in email clients
			$fullMessage = nl2br($fullMessage);

			$mail = new PHPMailer(true); //set exceptions to true
			try{
				//Server settings
				//$mail->SMTPDebug = 2;                                 // Enable verbose debug output
				$mail->isSMTP();                                      // Set mailer to use SMTP
				$mail->Host = $this->mailHost;							  // Specify main and backup SMTP servers
				$mail->SMTPAuth = true;                               // Enable SMTP authentication
				$mail->Username = $this->mailUsername;				      // SMTP username
				$mail->Password = $this->mailPassword;	                  // SMTP password
				$mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
				$mail->Port = $this->mailPort;                              // TCP port to connect to

				//Recipients
				$mail->setFrom($this->mailAddress, 'Mailer');
				$mail->addReplyTo($this->mailNoReply, 'No-Reply');

				//Content
				$mail->isHTML(true);                                  // Set email format to HTML
				$mail->addAddress($toAddress);
				$mail->Subject = $customSubject;
				$mail->Body    = $fullMessage;

				$data["sendSuccess"] = $mail->send(); //notify of successful sending of message (or unsuccessful if it fails)
				if(!$data["sendSuccess"]){ //error
					$errorMessage = $this->logger->logError("Email message could not be sent: ".$mail->ErrorInfo, $CASbroncoNetID, $this->thisLocation, true);
					$data["sendError"] = "Error: Email message could not be sent. ".$errorMessage;
				}
			}
			catch (phpmailerException $e) { //catch phpMailer specific exceptions
				$errorMessage = $this->logger->logError("Email message could not be sent: ".$e->errorMessage(), $CASbroncoNetID, $this->thisLocation, true);
				$data["sendError"] = "Error: Email message could not be sent. ".$errorMessage;
			}
			catch (Exception $e) {
				$errorMessage = $this->logger->logError("Email message could not be sent: ".$e->getMessage(), $CASbroncoNetID, $this->thisLocation, true);
				$data["sendError"] = "Error: Email message could not be sent. ".$errorMessage;
			}
		}
		else{
			$errorMessage = $this->logger->logError("Email could not be saved to the database.", $CASbroncoNetID, $this->thisLocation, true);
			$data["saveError"] = "Error: Email could not be saved to the database. ".$errorMessage;
		}

		$database->close(); //close database connections

		return $data; //pass back the data array
	}

	//The email to send to the department chair to let them know of their needed approval. Let them know the applicant's name and email
	public function chairApprovalEmail($appID, $toAddress, $applicantName, $applicantEmail, $CASbroncoNetID){
		$subject = "IEFDF Application - Chair Approval Required";

		$body = "Dear Department Chair,
			Your approval is needed for an IEFDF application for #name (#email). Your name confirms that the applicant is part of the bargaining unit and is therefore eligible to receive IEFDF funds. Directions:

			1. Go to the IEFDF website at iefdf.wmich.edu

			2. Log in with your Bronco NetID

			3. Click on the 'Approve Applications' link

			4. Navigate to the application you want to review, and click the 'View Application' link

			5. At the bottom of the page, type your name into the department chair approval field

			6. Click the 'Approve Application' button

			If there are no errors, you should then be redirected to the homepage with a confirmation message. You can go back to the site at any point to review applications that you have approved.

			Best Regards, Dr. Michelle Metro-Roland";
		$body = str_replace("#name", nl2br($applicantName), $body); //insert the applicant's name into the message
		$body = str_replace("#email", nl2br($applicantEmail), $body); //insert the applicant's email into the message

		return $this->customEmail($appID, $toAddress, $body, $subject, $CASbroncoNetID);
	}
}

?>
