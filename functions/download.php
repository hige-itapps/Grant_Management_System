<?php
	ob_clean();
	if(isset($_GET["doc"]))
	{
		$file = dirname(__FILE__) . '/../uploads/' . $_GET["id"] . '/' . $_GET["doc"];
		ob_clean();
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
?>