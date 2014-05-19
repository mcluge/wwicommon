<?php
namespace WWICommon;

class SchemaCache{

	private $config;
	private $ApplicationPath;
	private $path;

	public function __construct(){
		$this->config = Globals::get("Config");
		$this->ApplicationPath = Globals::get("ApplicationPath");
		$this->path = $this->ApplicationPath ."/".$this->config['wwicommon']["dbCacheDir"];
	}


	public static function getTableSchema($table){
		$SchemaCache = new self;

		if($SchemaCache->config['wwicommon']['dbCacheActive']){
			$file = $SchemaCache->getCacheFilePath($table,'definition');
			$SchemaCache->checkCacheExpiration($file);

			$handle = fopen($file, "r");
			$contents = (array) json_decode(fread($handle, filesize($file)));
			fclose($handle);
			

		}else{
			$schema = $SchemaCache->getSchema();
			$tableSchema = json_encode($schema[$table]['DEFINITION']);

			$contents = (array) json_decode( $tableSchema );
		}

		return $contents;

	}

	public static function getTableRelations($table){
		$SchemaCache = new self;

		if($SchemaCache->config['wwicommon']['dbCacheActive']){
			$file = $SchemaCache->getCacheFilePath($table,'relations');
			$SchemaCache->checkCacheExpiration($file);

			$handle = fopen($file, "r");
			$contents = (array) json_decode(fread($handle, filesize($file)));
			fclose($handle);
			

		}else{
			$schema = $SchemaCache->getSchema();
			$tabledata = $schema[$table];
			unset($tabledata['DEFINITION']);
			$tableRelations = json_encode($tabledata);
			$contents = (array) json_decode( $tableRelations );
		}

		return $contents;
	}

	private function checkCacheExpiration($file){
		if(is_file($file)){
			$creation = filemtime ( $file );
			$now = date("U");
			$expiration = $creation+($this->config['wwicommon']["dbCacheExperation"]*60);
			if($expiration < $now){
				$this->updateCache();
			}else{

			}


		}else{
			$this->updateCache();
			if(!is_file($file)){
				trigger_error("Unable to find cache file!",E_USER_ERROR);
			}
		}

		return true;
	}

	private function getCacheFilePath($table,$type){
		switch ($type) {
			case 'definition':
				return $this->path."/".$table."-definition.json";
				break;
			case 'relations':
				return $this->path."/".$table."-relations.json";
			default:
				trigger_error("You must pass a valid file type!",E_USER_ERROR);
				break;
		}
		
	}

	private function updateCache(){

		$schema = $this->getSchema();

		$path = $this->path;

		if(!is_dir($path)){
			if(!mkdir($path)){
				trigger_error("Unable to create cache folder!",E_USER_ERROR);
			}
		}
		if(!is_writable($path)){
			trigger_error("Cache folder is un writable!",E_USER_ERROR);
		}
		$dir = scandir($path);
		foreach($dir as $entity){
			if(!in_array($entity,array('.','..'))){
				unlink($path."/".$entity);
			}
		}

		foreach ($schema as $table => $values) {
			if(!empty($table)){
				$tableDefinitionFile = $path."/".$table."-definition.json";
				$handle = fopen($tableDefinitionFile, 'w');

				if(!fwrite($handle, json_encode($values['DEFINITION']))){
					trigger_error("Unable to write to $tableDefinitionFile!",E_USER_ERROR);
				}

				$tableRelationsFile = $path."/".$table."-relations.json";
				$handle = fopen($tableRelationsFile, 'w');
				unset($values['DEFINITION']);
				if(!fwrite($handle, json_encode($values))){
					trigger_error("Unable to write to $tableRelationsFile!",E_USER_ERROR);
				}
			}
		}



	}


	public function getSchema(){

		$dbinfo = $this->getDatabaseInfo();

		if($dbinfo && $dbinfo->type == "mysql"){

			$schema = \WWICommon\Schema\Mysql\SchemaBuilder::getSchema($dbinfo->dbname);

		}
		
		if(!empty($schema)){
			return $schema;
		}else{
			trigger_error("Error unable to get $schema",E_USER_ERROR);
		}
	}

	private function getDatabaseInfo(){

		if(!empty($this->config['db']['dsn'])){
			$dsn1 = explode(";", $this->config['db']['dsn']);
			$dsn2 = explode(":",$dsn1[0]);
			$dsn3 = explode("=",$dsn2[1]);

			return (object) array(
				"type" => $dsn2[0],
				"dbname" => $dsn3[1],
				);
			
		}else{
			trigger_error("Error: No DSN to parse",E_USER_ERROR);
		}
		
	}

}