<?php
namespace WWICommon;

class Globals{

	private static $db;
	private static $ServiceManager;
	private static $config;

	public static function get($prop)
	{
		if(isset(self::$prop)) {
			return self::$prop;
		}else{
			trigger_error("Developer error:: property does not exist!");
		}
	}

	public static function set($prop,$value)
	{
		if(self::$prop = $value) {
			return true;
		}else{
			trigger_error("Developer error:: property does not exist!");
		}

	}

}