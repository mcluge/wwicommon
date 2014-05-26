<?php

	if( !function_exists( "dbug" ) ) {
		function dbug( $data) {
			$sapi_type = php_sapi_name();
			$tr = debug_backtrace();
			$level = 0;
			$color = null;
			$colorReset = null;
			if($sapi_type != "cli"){
				$color = "\033";
				$colorReset = "\033[0m";
			}
			if($sapi_type == "cli"){
				echo "\033[35m"."DBUG called line ".$tr[$level]['line']." of ".$tr[$level]['file']."\n"."\033[31m";
				echo "\033[31m".var_export( func_get_args() )."\n"."\033[0m";

			}else{
				echo "<pre>";
				echo "DBUG called line ".$tr[$level]['line']." of ".$tr[$level]['file']."\n";

				var_export( func_get_args() )."\n";
				echo "</pre>";
			}

		}
	}
	
	if(!function_exists("camelize")){
		function camelize($word) {
			return preg_replace('/(^|_)([a-z])/e', 'strtoupper("\\2")', $word);
		}
	}