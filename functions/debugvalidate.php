<?php
	/*For debugging purposes, set the validation variables by giving parameters through the URL.
		Example: https://codigo-tech.com?broncoNetID=tew2042&email=joe.shmoe@wmich.edu*/
	include('include/validationvariables.php');
	//echo 'BroncoNetID is ' . htmlspecialchars($_GET["broncoNetID"]) . '!';
	if(isset($_GET["broncoNetID"])){$broncoNetID = htmlspecialchars($_GET["broncoNetID"]);}
	//echo 'Email address is ' . htmlspecialchars($_GET["email"]) . '!';
	if(isset($_GET["email"])){$email = htmlspecialchars($_GET["email"]);}
	
	if(isset($_GET["position"])){$position = htmlspecialchars($_GET["position"]);}
?>