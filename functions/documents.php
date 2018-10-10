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
			$res = null; //return value. res["error"] will contain an array of any given errors.

			/*VERY IMPORTANT! In order to utilize the config.ini file, we need to have the url to point to it! set that here:*/
			$config_url = dirname(__FILE__).'/../config.ini';

			//get the max file upload size
			$maxUploadSize = file_upload_max_size();
		
			try
			{
				$uploadDir = parse_ini_file($config_url)["uploads_dir"]; //load upload directory
				$uploadTo = dirname(__FILE__) ."/..".$uploadDir."/".$appID; //get specific directory for this application

				if (!file_exists($uploadTo)){ mkdir($uploadTo); } //First, try to make the directory for this file if it doesn't already exist.
				//$file = $files["tmp_name"];
				//$doc = "P" . $id . "-" . $files["name"];

				if (file_exists($uploadTo)){ //if the directory was created
					foreach ($files as $filename => $file) //try to upload each file
					{
						//make sure file isn't too large -- this probably won't be useful because the files probably wouldn't have uploaded anyway if they were too large
						if($file["size"] > $maxUploadSize){
							$res["error"][] = 'File "'.$file["name"].'" is too large to upload';
							continue; //iterate to next item in loop
						}

						//make sure file size is > 0
						if($file["size"] <= 0){
							$res["error"][] = 'File "'.$file["name"].'" is empty!';
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
							$res["error"][] = 'Upload prefix not accepted for file "'.$file["name"].'"';
							continue; //iterate to next item in loop
						}

						$target = $uploadTo."/".$prefix.$file["name"];
						$fileType = strtolower(pathinfo($target, PATHINFO_EXTENSION));//get the file's type

						//make sure file type is an accepted format
						if($fileType != "txt" && $fileType != "rtf" && $fileType != "doc" && $fileType != "docx" && $fileType != "xls" 
							&& $fileType != "xlsx" && $fileType != "ppt" && $fileType != "pptx" && $fileType != "pdf" && $fileType != "jpg"
							&& $fileType != "png" && $fileType != "bmp" && $fileType != "tif"){
							$res["error"][] = 'Filetype: "'.$fileType.'" not accepted for file "'.$file["name"].'"';
							continue; //iterate to next item in loop
						}

						//move file to the uploads directory
						if(!move_uploaded_file($file["tmp_name"], $target)){
							$res["error"][] = 'Unable to move file "'.$file["name"].'" to uploads directory'; //if it failed to move
							continue; //iterate to next item in loop
						}
					}
				}else{
					$res["error"][] = "Unable to create upload directory";
				}

				if(!isset($res["error"])){$res = true;} //no errors, so success!
			}
			catch(Exception $e)
			{
				$res["error"][] = "Unable to upload document: " . $e->getMessage();
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
	
	/* Returns a file size limit in bytes based on the PHP upload_max_filesize and post_max_size
	found at https://stackoverflow.com/questions/13076480/php-get-actual-maximum-upload-size */
	if(!function_exists('file_upload_max_size')) {
		function file_upload_max_size() {
			static $max_size = -1;
		
			if ($max_size < 0) {
				// Start with post_max_size.
				$post_max_size = parse_size(ini_get('post_max_size'));
				if ($post_max_size > 0) {$max_size = $post_max_size;}
			
				// If upload_max_size is less, then reduce. Except if upload_max_size is
				// zero, which indicates no limit.
				$upload_max = parse_size(ini_get('upload_max_filesize'));
				if ($upload_max > 0 && $upload_max < $max_size) {$max_size = $upload_max;}
			}
			return $max_size;
		}
	}
	
	if(!function_exists('parse_size')) {
		function parse_size($size) {
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