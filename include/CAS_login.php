<?php

/**
 *   Example for a simple cas 2.0 client
 *
 * PHP Version 5
 *
 * @file     example_simple.php
 * @category Authentication
 * @package  PhpCAS
 * @author   Joachim Fritschi <jfritschi@freenet.de>
 * @author   Adam Franco <afranco@middlebury.edu>
 * @license  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link     https://wiki.jasig.org/display/CASC/phpCAS
 */

// Load the settings from the central config file
require_once 'config.php';
// Load the CAS lib
require_once $phpcas_path . '/CAS.php';

// Enable debugging
phpCAS::setDebug();
// Enable verbose error messages. Disable in production!
phpCAS::setVerbose(true);

// Initialize phpCAS
phpCAS::client(SAML_VERSION_1_1, $cas_host, $cas_port, $cas_context);

// For production use set the CA certificate that is the issuer of the cert
// on the CAS server and uncomment the line below
 phpCAS::setCasServerCACert($cas_server_ca_cert_path);

// For quick testing you can disable SSL validation of the CAS server.
// THIS SETTING IS NOT RECOMMENDED FOR PRODUCTION.
// VALIDATING THE CAS SERVER IS CRUCIAL TO THE SECURITY OF THE CAS PROTOCOL!
// phpCAS::setNoCasServerValidation();

/* echo 'BEFORE DUMP';
var_dump $_POST;
return; */

// check CAS authentication
$auth = phpCAS::checkAuthentication();

if(!$auth) {
	//echo "INSIDE: " . $auth;
	//return;
	phpCAS::forceAuthentication();
	
	//return;
}
// force CAS authentication


// at this step, the user has been authenticated by the CAS server
// and the user's login name can be read with phpCAS::getUser().

// logout if desired
if (isset($_REQUEST['logout'])) {
	phpCAS::logout();
}


$CASinfo = phpCAS::getAttributes();

if(isset($CASinfo) && !is_null($CASinfo) && !empty($CASinfo)) {
	//try to get bronco net ID, positions, and email 
	if(isset($CASinfo['uid']) && !is_null($CASinfo['uid']) && !empty($CASinfo['uid']))
		$CASbroncoNetId = $CASinfo['uid'];
	if(isset($CASinfo['wmuEduPersonPrimaryAffiliation']) && !is_null($CASinfo['wmuEduPersonPrimaryAffiliation']) && !empty($CASinfo['wmuEduPersonPrimaryAffiliation']))
		$CASprimaryPosition = $CASinfo['wmuEduPersonPrimaryAffiliation'];
	if(isset($CASinfo['wmuEduPersonAffiliation']) && !is_null($CASinfo['wmuEduPersonAffiliation']) && !empty($CASinfo['wmuEduPersonAffiliation']))
		$CASallPositions = $CASinfo['wmuEduPersonAffiliation']; //an array! search through this to see if a staff or faculty or student 
	else 
		$CASallPositions = $CASprimaryPosition;
	if(isset($CASinfo['mail']) && !is_null($CASinfo['mail']) && !empty($CASinfo['mail']))
		$CASemail = $CASinfo['mail'];
	else 
		$CASemail = "NO_EMAIL";
}

// for this test, simply print that the authentication was successfull
?>