<?php
	ob_start();
	
	set_include_path('/home/egf897jck0fu/public_html/');
	//include('../Net/SFTP.php');
	include('../functions/database.php');
	$conn = connection(); //connect to database
	
	include('../functions/documents.php');
	
	/*Debug user validation*/
	include "include/debugAuthentication.php";
	
	/*Verification functions*/
	include "functions/verification.php";
	
	/*Verify that user is allowed to make an application*/
	if(isUserAllowedToCreateApplication($conn, $_SESSION['broncoNetID'], $_SESSION['position']))
	{
		//echo "User is allowed to create an application!";
		
		if(isset($_POST["sub"])) //submit button to form
		{
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
				
				//echo "current broncoNetID: ".$_SESSION['broncoNetID'];
				
				/*Insert data into database - receive the new application id if success, or 0 if failure*/
				/*parameters: DB connection, name, email, department, dep. mail stop, dep. chair email, travel from, travel to, activity from, activity to, title, destination, amount requested,
				purpose1, purpose2, purpose3, purpose4Other, other funding, proposal summary, goal1, goal2, goal3, goal4, budgetArray*/
				$successAppID = insertApplication($conn, $_SESSION['broncoNetID'], $_POST["inputName"], $_POST["inputEmail"], $_POST["inputDept"], $_POST["inputDeptM"], $_POST["inputDeptCE"], 
					$_POST["inputTFrom"], $_POST["inputTTo"], $_POST["inputAFrom"], $_POST["inputATo"], $_POST["inputRName"], $_POST["inputDest"], $_POST["inputAR"], 
					$pr1, $pr2, $pr3, $pr4, $_POST["eS"], $_POST["props"], $pg1, $pg2, $pg3, $pg4, 
					$budgetArray);
					
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
				
				if($successUpload > 0) //upload was successful
				{
					$to = $_POST["inputDeptCE"];
					$body = "<p>Hello - </p><p>A new HIGE Grant application has been submitted by #name of the #dept
					department that requires his/her department chair's signature.</p>
					<p>Please go to the HIGE website and follow the instructions to sign the application.</p>";//file_get_contents('customEmail.html');
					$body = str_replace("#name", nl2br($_POST["inputName"]), $body);
					$body = str_replace("#dept", nl2br($_POST["inputDept"]), $body);

					$subject = "New HIGE Grant Application - Do Not Reply";

					$headers = "From: HIGE <donotreply@codigo-tech.com> \r\n";
					$headers .= "Reply-To: info@codigo-tech.com \r\n";
					$headers .= "MIME-Version: 1.0\r\n";
					$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
					$headers .= "X-Priority: 1 (Highest)\n";
					$headers .= "X-MSMail-Priority: High\n";
					$headers .= "Importance: High\n";
						
					mail($to, $subject, $body, $headers);
					
					//redirect back to homepage
					header('/');
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
	}
	
	$conn = null; //close connection -- NOT NEEDED.
?>