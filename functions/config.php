<?php

	/*Parse the config file & return it as an array*/
	if(!function_exists('parseConfig')) {
		function parseConfig($input)
		{
			return parse_ini_file($input, false);
		}
	}
?>