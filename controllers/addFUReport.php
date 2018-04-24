<?php
	ob_start();



	/*Debug user validation*/
	/*include "include/debugAuthentication.php";*/
	include_once(dirname(__FILE__) . "/../include/CAS_login.php");
	
	//include('../Net/SFTP.php');
	include_once(dirname(__FILE__) . "/../functions/database.php");
	$conn = connection(); //connect to database
	
	include_once(dirname(__FILE__) . "/../functions/documents.php");
	
	/*Verification functions*/
	include_once(dirname(__FILE__) . "/../functions/verification.php");
	
	$isAdmin = isAdministrator($conn, $CASbroncoNetId);

	/*Verify that user is allowed to make an application*/
	if(isUserAllowedToCreateFollowUpReport($conn, $CASbroncoNetId, $_POST['updateID']) || $isAdmin)
	{
		//echo "User is allowed to create an application!";
		
		try
		{
			
			//echo "current broncoNetID: ".$_SESSION['broncoNetID'];
			
			/*Insert data into database - receive the new application id if success, or 0 if failure*/
			/*parameters: DB connection, name, email, department, dep. mail stop, dep. chair email, travel from, travel to, activity from, activity to, title, destination, amount requested,
			purpose1, purpose2, purpose3, purpose4Other, other funding, proposal summary, goal1, goal2, goal3, goal4, budgetArray*/
			if($isAdmin)
			{
				$successAppID = insertFollowUpReport($conn, true, $_POST['updateID'], $_POST["inputTFrom"], $_POST["inputTTo"], $_POST["inputAFrom"], $_POST["inputATo"], 
					$_POST["projs"], $_POST["aAw"]);
			}
			else
			{
				$successAppID = insertFollowUpReport($conn, false, $_POST['updateID'], $_POST["inputTFrom"], $_POST["inputTTo"], $_POST["inputAFrom"], $_POST["inputATo"], 
					$_POST["projs"], $_POST["aAw"]);
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
			
			if($successUpload > 0) //upload was successful
			{
				header('Location: /');
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