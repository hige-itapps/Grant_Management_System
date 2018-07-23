<?php

	/* Returns array of all associated file names */
	if(!function_exists('getFileNames')) {
		function getFileNames($appID)
		{
			/*VERY IMPORTANT! In order to utilize the config.ini file, we need to have the url to point to it! set that here:*/
			$config_url = dirname(__FILE__).'/../config.ini';
			$uploadDir = parse_ini_file($config_url)["uploads_dir"]; //load upload directory
			$uploadTo = dirname(__FILE__) ."/..".$uploadDir."/".$appID; //get specific directory for this application
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
	}

	/* Upload files for the associated application */
	if(!function_exists('uploadDocs')) {
		function uploadDocs($appID, $files)
		{
			$res = null; //return value

			/*VERY IMPORTANT! In order to utilize the config.ini file, we need to have the url to point to it! set that here:*/
			$config_url = dirname(__FILE__).'/../config.ini';
		
			try
			{
				$uploadDir = parse_ini_file($config_url)["uploads_dir"]; //load upload directory
				$uploadTo = dirname(__FILE__) ."/..".$uploadDir."/".$appID; //get specific directory for this application

				if (!file_exists($uploadTo)){ mkdir($uploadTo); } //First, try to make the directory for this file if it doesn't already exist.
				//$file = $files["tmp_name"];
				//$doc = "P" . $id . "-" . $files["name"];
				
				foreach ($files as $filename => $file) //try to upload each file
				{
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

					if($prefix !== null)//upload prefix accepted
					{
						$target = $uploadTo."/".$prefix.$file["name"];
						$fileType = strtolower(pathinfo($target, PATHINFO_EXTENSION));//get the file's type

						if($fileType == "txt" || $fileType == "rtf" || $fileType == "doc" || $fileType == "docx" || $fileType == "xls" 
							|| $fileType == "xlsx" || $fileType == "ppt" || $fileType == "pptx" || $fileType == "pdf" || $fileType == "jpg"
							|| $fileType == "png" || $fileType == "bmp" || $fileType == "tif")//make sure file type is an accepted format
						{
							if(!move_uploaded_file($file["tmp_name"], $target))//move file to the uploads directory
							{
								$res["error"] = "Unable to move file to uploads directory"; //if it failed to move
								break; //prematurely exit loop
							} 
						}
						else
						{
							$res["error"] = "Filetype: ".$fileType." not accepted";
							break; //prematurely exit loop
						}
					}
					else //not accepted
					{
						$res["error"] = "Upload prefix not accepted";
						break; //prematurely exit loop
					}
				}

				if(!isset($res["error"])){$res = true;} //no errors, so success!
			}
			catch(Exception $e)
			{
				$res["error"] = "Unable to upload document: " . $e->getMessage();
			}

			return $res;
		}
	}

	/* Download a file for the associated application */
	if(!function_exists('downloadDoc')) {
		function downloadDoc($appID, $filename)
		{
			/*VERY IMPORTANT! In order to utilize the config.ini file, we need to have the url to point to it! set that here:*/
			$config_url = dirname(__FILE__).'/../config.ini';
			$uploadDir = parse_ini_file($config_url)["uploads_dir"]; //load upload directory
			$uploadTo = dirname(__FILE__) ."/..".$uploadDir."/".$appID; //get specific directory for this application
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
				return false;
			}
			
		}
	}
	
?>