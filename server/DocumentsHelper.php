<?php
/* This class is used to read and write to documents. */

/*Logger*/
include_once(dirname(__FILE__) . "/logger.php");

class DocumentsHelper
{
	private $logger; //for logging to files
	private $uploadDir; //file uploads directory

	/* Constructior retrieves configurations */
	public function __construct(){
		$config_url = dirname(__FILE__).'/../config.ini'; //set config file url
		$settings = parse_ini_file($config_url); //get all settings		
		$this->uploadDir = dirname(__FILE__) ."/..".$settings["uploads_dir"]; //get absolute path to uploads directory
		$this->logger = new Logger(); //initialize the logger
	}

	/* Returns array of all associated file names */
	public function getFileNames($appID)
	{
		$uploadTo = $this->uploadDir."/".$appID; //get specific directory for this application
		$fileNames = []; //empty array of filenames

		if(file_exists($uploadTo))//only check directory if it exists
		{
			foreach (new DirectoryIterator($uploadTo) as $file) {
				if ($file->isFile()) {
					array_push($fileNames, $file->getFilename());
				}
			}
		}

		return $fileNames;
	}

	/* Upload files for the associated application */
	public function uploadDocs($appID, $files, $CASbroncoNetID)
	{
		$this->logger->logInfo("Uploading documents", $CASbroncoNetID, dirname(__FILE__));

		$res = null; //return value. res["error"] will contain an array of any given errors.

		//get the max file upload size
		$maxUploadSize = $this->file_upload_max_size();
	
		try
		{
			$uploadTo = $this->uploadDir."/".$appID; //get specific directory for this application

			if (!file_exists($uploadTo)){ mkdir($uploadTo); } //First, try to make the directory for this file if it doesn't already exist.
			//$file = $files["tmp_name"];
			//$doc = "P" . $id . "-" . $files["name"];

			if (file_exists($uploadTo)){ //if the directory was created
				foreach ($files as $filename => $file) //try to upload each file
				{
					//make sure file isn't too large -- this probably won't be useful because the files probably wouldn't have uploaded anyway if they were too large
					if($file["size"] > $maxUploadSize){
						$res["error"][] = "File '".$file["name"]."' is too large to upload";
						continue; //iterate to next item in loop
					}

					//make sure file size is > 0
					if($file["size"] <= 0){
						$errorID = $this->logger->logError("File '".$file["name"]."' is empty", $CASbroncoNetID, dirname(__FILE__));
						$res["error"][] = "Error: File '".$file["name"]."' is empty. This has been logged as error id '".$errorID."' Please notify the system admin of this error's id and time of occurence.";
						continue; //iterate to next item in loop
					}

					$prefix = null; //set to a valid prefix based on the type of upload (supporting document, proposal narrative, etc.)
					if(strncmp($filename, "supportingDoc", 13) === 0){
						$totalExisting = count(preg_grep('~^SD.*~', scandir($uploadTo)));//find number of already existing files with this prefix
						$prefix = "SD".($totalExisting+1)."_";//increment the prefix
					}
					else if(strncmp($filename, "proposalNarrative", 17) === 0){
						$totalExisting = count(preg_grep('~^PN.*~', scandir($uploadTo)));//find number of already existing files with this prefix
						$prefix = "PN".($totalExisting+1)."_";//increment the prefix
					}
					else if(strncmp($filename, "finalReportDoc", 11) === 0){
						$totalExisting = count(preg_grep('~^FD.*~', scandir($uploadTo)));//find number of already existing files with this prefix
						$prefix = "FD".($totalExisting+1)."_";//increment the prefix
					}

					if($prefix == null){//upload prefix not accepted
						$errorID = $this->logger->logError("Upload prefix not accepted for file '".$file["name"]."'", $CASbroncoNetID, dirname(__FILE__));
						$res["error"][] = "Error: Upload prefix not accepted for file '".$file["name"]."'. This has been logged as error id '".$errorID."' Please notify the system admin of this error's id and time of occurence.";
						continue; //iterate to next item in loop
					}

					$target = $uploadTo."/".$prefix.$file["name"];
					$fileType = strtolower(pathinfo($target, PATHINFO_EXTENSION));//get the file's type

					//make sure file type is an accepted format
					if($fileType != "pdf"){
						$res["error"][] = "Filetype: '".$fileType."' not accepted for file '".$file["name"]."'";
						continue; //iterate to next item in loop
					}

					//move file to the uploads directory
					if(!move_uploaded_file($file["tmp_name"], $target)){ //if it failed to move
						$errorID = $this->logger->logError("Unable to move file '".$file["name"]."' to uploads directory", $CASbroncoNetID, dirname(__FILE__));
						$res["error"][] = "Error: Unable to move file '".$file["name"]."' to uploads directory. This has been logged as error id '".$errorID."' Please notify the system admin of this error's id and time of occurence.";
						continue; //iterate to next item in loop
					}
				}
			}else{
				$errorID = $this->logger->logError("Unable to create upload directory", $CASbroncoNetID, dirname(__FILE__));
				$res["error"][] = "Error: Unable to create upload directory. This has been logged as error id '".$errorID."' Please notify the system admin of this error's id and time of occurence.";
			}

			if(!isset($res["error"])){$res = true;} //no errors, so success!
		}
		catch(Exception $e)
		{
			$errorID = $this->logger->logError("Unable to upload document: " . $e->getMessage(), $CASbroncoNetID, dirname(__FILE__));
			$res["error"][] = "Error: Unable to upload document due to an internal exception. This has been logged as error id '".$errorID."' Please notify the system admin of this error's id and time of occurence.";
		}

		return $res;
	}

	/* Download a file for the associated application */
	public function downloadDoc($appID, $filename, $CASbroncoNetID)
	{
		/*VERY IMPORTANT! In order to utilize the config.ini file, we need to have the url to point to it! set that here:*/
		$uploadTo = $this->uploadDir."/".$appID; //get specific directory for this application
		$file = $uploadTo . '/' . $filename;
		if(file_exists($file))
		{
			header('Connection: Keep-Alive');
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename="'.basename($file).'"');
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: ' . filesize($file));
			readfile($file);
			exit;
		}
		else
		{
			$errorID = $this->logger->logError("File '".$filename."' does not exist at '".$file."'", $CASbroncoNetID, dirname(__FILE__));
			return "Error: File '".$filename."' does not exist in the uploads directory. This has been logged as error id '".$errorID."' Please notify the system admin of this error's id and time of occurence.";
		}
		
	}
	
	/* Returns a file size limit in bytes based on the PHP upload_max_filesize and post_max_size
	found at https://stackoverflow.com/questions/13076480/php-get-actual-maximum-upload-size */
	public function file_upload_max_size() {
		$max_size = -1;
	
		if ($max_size < 0) {
			// Start with post_max_size.
			$post_max_size = $this->parse_size(ini_get('post_max_size'));
			if ($post_max_size > 0) {$max_size = $post_max_size;}
		
			// If upload_max_size is less, then reduce. Except if upload_max_size is
			// zero, which indicates no limit.
			$upload_max = $this->parse_size(ini_get('upload_max_filesize'));
			if ($upload_max > 0 && $upload_max < $max_size) {$max_size = $upload_max;}
		}
		return $max_size;
	}
	/*Used in the method above to find the size of a shorthand byte notation string in standard bytes*/
	private function parse_size($size) {
		$unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
		$size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
		if ($unit) {
			// Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
			return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
		}
		else {
			return round($size);
		}
	}

}
?>