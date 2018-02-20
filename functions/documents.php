<?php
	include('Net/SFTP.php');

	function listDocs($id) {
		$settings = parse_ini_file('config.ini');
		$ssh = new Net_SFTP('www.codigo-tech.com');
		if (!$ssh->login($settings["host_user"], "Default1234$")) {
			exit('Auth Failed');
		}
		return $ssh->nlist($settings["uploads_dir"] . $id);
	}
	/*if(isset($_GET["doc"]))
	{
		downloadDocs($_GET["id"], $_GET["doc"]);
	}*/
	function downloadDocs($id, $doc) {
		$settings = parse_ini_file('config.ini');
			
			$ssh = new Net_SFTP('www.codigo-tech.com');
		if (!$ssh->login($settings["host_user"], "Default1234$")) {
			$ssh->login($settings["host_user"], "Default1234$");
		}
		//file_put_contents($doc, $ssh->get($settings["uploads_dir"] . $id . '/' . $doc));
		header("Content-type:application/pdf");
		// It will be called downloaded.pdf
		header("Content-Disposition:attachment;filename=" . $doc);
		readfile($settings["uploads_dir"] . $id . '/' . $doc);
		exit('Downloaded');
	}
?>