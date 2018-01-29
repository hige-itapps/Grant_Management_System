<?php

	/*Parse the config file & return it as an array*/
	function parseConfig($input)
	{
		return parse_ini_file($input, false);
	}
?>