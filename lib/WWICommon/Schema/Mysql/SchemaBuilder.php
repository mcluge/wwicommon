<?php
namespace WWICommon\schema\Mysql;

use WWICommon\Globals;

class SchemaBuilder{

	private $db;
	private $database;
	private $schema;

	public function __construct(){
		$this->db = Globals::get("db");
		$this->schema = array();
	}

	public static function getSchema($database){
		$SchemaBuilder = new self;

		$SchemaBuilder->database = $database;

		$tables = $SchemaBuilder->getTables();
		$schema = array();
		foreach($SchemaBuilder->schema as $table => $value){
			$SchemaBuilder->getTableType($table);
			$SchemaBuilder->getTableDefinition($table);
			$SchemaBuilder->getTableRelations($table);
		}
		$SchemaBuilder->parseRelations();
 		return $SchemaBuilder->schema;
	}

	private function parseRelations(){

		//Add constraints
		foreach($this->schema as $table => $values){

			foreach($values["TMP"] as $constraints){
				$this->schema[$table]['CONSTRAINTS'][$constraints['COLUMN_NAME']] = array(
					"REFERENCED_TABLE_NAME" => (($constraints['REFERENCED_TABLE_NAME'])?:null), 
					"REFERENCED_COLUMN_NAME" => (($constraints['REFERENCED_COLUMN_NAME'])?:null) , 
					"TABLE_TYPE" => ((!empty($this->schema[$constraints['REFERENCED_TABLE_NAME']]['TYPE']))? $this->schema[$constraints['REFERENCED_TABLE_NAME']]['TYPE'] :null),
					"RELATION" => (!empty($this->schema[$constraints['REFERENCED_TABLE_NAME']]['definition'][$constraints['REFERENCED_COLUMN_NAME']]['Key']) && $this->schema[$constraints['REFERENCED_TABLE_NAME']]['definition'][$constraints['REFERENCED_COLUMN_NAME']]['Key'] == "PRI" )?"ONE2ONE":"ONE2MANY"
					 );
				$this->schema[$constraints['REFERENCED_TABLE_NAME']]['CONSTRAINTS'][$constraints['REFERENCED_COLUMN_NAME']] = array(
					"REFERENCED_TABLE_NAME" => $table, 
					"REFERENCED_COLUMN_NAME" => $constraints['COLUMN_NAME'] , 
					"TABLE_TYPE" => ((!empty($this->schema[$table]['TYPE']))? $this->schema[$table]['TYPE'] :null),
					"RELATION" => (!empty($this->schema[$constraints['REFERENCED_TABLE_NAME']]['definition'][$constraints['REFERENCED_COLUMN_NAME']]['Key']) && $this->schema[$constraints['REFERENCED_TABLE_NAME']]['definition'][$constraints['REFERENCED_COLUMN_NAME']]['Key'] == "PRI" )?"ONE2MANY":"ONE2ONE"
				);

			}
	
			unset($this->schema[$table]['TMP']);

		}
		return true;
	}


	private function getTableRelations($table){
		$sql = "
				SELECT
					TABLE_NAME,
					COLUMN_NAME,
					REFERENCED_TABLE_NAME,
					REFERENCED_COLUMN_NAME
				FROM
				information_schema.KEY_COLUMN_USAGE us
				WHERE 1
				AND CONSTRAINT_NAME != 'PRIMARY'
				AND us.CONSTRAINT_SCHEMA = '$this->database'
				AND us.TABLE_NAME = '$table'
		";

		$statement = $this->db->prepare( $sql );
		$statement->execute();
		$constraintsResults = $statement->fetchall();


		$this->schema[$table]['TMP'] = $constraintsResults;
		return true;
	}

	private function getTableType($table){
		$sql = "SELECT
				count(COLUMN_NAME)
				FROM
				information_schema.COLUMNS
				WHERE 1
				AND TABLE_SCHEMA = '$this->database'
				AND TABLE_NAME = '$table'
				AND COLUMN_KEY = ''";

		$statement = $this->db->prepare( $sql );
		$statement->execute();
		$tableTyperesults = array_values($statement->fetch());
		
		$this->schema[$table]['TYPE'] = ($tableTyperesults[0] == "0")?"LINKER":"STANDARD";


	}

	private function getTableDefinition($table){

		$sql = "DESCRIBE $table;";
		$statement = $this->db->prepare( $sql );
		$statement->execute();
		$results = $statement->fetchAll(\PDO::FETCH_ASSOC);
		$definition = array();
		foreach ($results as  $col) {
			$definition[$col['Field']] = array(
				'Type' => $col['Type'],
          		'Null' => $col['Null'],
          		'Key' => $col['Key'],
          		'Default' => $col['Default'],
          		'Extra' => $col['Extra'],
			);
		}


		$this->schema[$table]['DEFINITION'] = $definition;

		return true;


	}

	private function getTables(){
		$sql = "show tables;";
		$statement = $this->db->prepare( $sql );
		$statement->execute();
		$results = $statement->fetchAll(\PDO::FETCH_ASSOC);
		foreach($results as $table){
			$this->schema[$table['Tables_in_unity']] = array();
		}
		
		return true;
	}

}