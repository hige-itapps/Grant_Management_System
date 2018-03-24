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
	if(!hasFUReport($conn, $_POST["appID"]))
	{
		//echo "User is allowed to create an application!";
		
		try
		{
			
			//echo "current broncoNetID: ".$_SESSION['broncoNetID'];
			
			/*Insert data into database - receive the new application id if success, or 0 if failure*/
			/*parameters: DB connection, name, email, department, dep. mail stop, dep. chair email, travel from, travel to, activity from, activity to, title, destination, amount requested,
			purpose1, purpose2, purpose3, purpose4Other, other funding, proposal summary, goal1, goal2, goal3, goal4, budgetArray*/
			$successAppID = insertFollowUpReport($conn, $_POST['appID'], $_POST["inputTFrom"], $_POST["inputTTo"], $_POST["inputAFrom"], $_POST["inputATo"], 
				$_POST["projs"], $_POST["aAw"]);
				
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