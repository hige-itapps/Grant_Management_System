<?php
	ob_start();
	try
	{
		if(isset($_FILES["file"]))
		{
			$file = $_FILES["file"]["tmp_name"];
			$doc = $_FILES["file"]["name"];
			$ftp = ftp_connect("codigo-tech.com") or die("Could not connect to $ftp_server");
			$login = ftp_login($ftp, "egf897jck0fu", "Default1234$");
			ftp_pasv($ftp, true);
			if(ftp_put($ftp, "/public_html/" . $doc, $file, FTP_BINARY))
			{
				ftp_close($ftp);
				echo "success";
			}
		}else{
			echo "no file";
		}
	}
	catch(Exception  $e)
	{
		echo $e;
	}
?>