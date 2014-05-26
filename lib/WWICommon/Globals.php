<?php
namespace WWICommon;

class Globals{

	/*
	Required variables
	db
	Config
	AppicationPath
	 */

	public static function set($name, $value)
	{
		$GLOBALS[$name] = $value;
	}

	public static function get($name)
	{
		return $GLOBALS[$name];
	}

	public static function camelize($word) {
		return preg_replace('/(^|_)([a-z])/e', 'strtoupper("\\2")', $word);
	}

	public static function appendStarkORMSchema($table,$schema)
	{
		$cols = SchemaCache::getTableSchema($table);
		$setSchemaKeys = array_keys($schema);

		foreach ($cols as $key => $value) {
			if(!in_array($key, $setSchemaKeys)){
				$schema[$key] = array();
			}
		}

		return $schema;

	}


	public static function appendStarkORMRelations($table,$relations)
	{
		$rel = SchemaCache::getTableRelations($table);
		$setRelKeys = array_keys($relations);

		$config = Globals::get("Config");

		foreach ($rel['CONSTRAINTS'] as $key => $value) {

			switch ($value->TABLE_TYPE) {
				case 'LINKER':

					$linkerRel = SchemaCache::getTableRelations($value->REFERENCED_TABLE_NAME);
					foreach ($linkerRel['CONSTRAINTS'] as $key => $linkerValue) {
						if($linkerValue->REFERENCED_TABLE_NAME != $table){
							$foreign_table = $linkerValue->REFERENCED_TABLE_NAME;
						}
					}
					$linkerSchema = SchemaCache::getTableSchema($table);
					foreach ($linkerSchema as $col => $values) {
						if($values->Key == 'PRI'){
							$primary_key = $col;
							break;
						}
					}
					if(empty($relations[$foreign_table])){

						$relations[ camelize($foreign_table)] = array( 
								'relationship'                    => 'many_to_many',
								'use'                             => $config['wwicommon']['modelNamespace'].ucfirst(camelize($foreign_table)),
								'foreign_table'                   => $foreign_table,                      # The final table of the object we will be getting
								'join_table'                      => $value->REFERENCED_TABLE_NAME,                 # The in-between table that has both pKeys
								'foreign_table_pkey'              => $primary_key,                   # The pKey of the final table (NOTE: can be THIS table's pKey)
								);
					}

					break;
				
				default:
					if(empty($relations[$value->REFERENCED_TABLE_NAME])){
						if($value->RELATION = 'ONE2ONE'){

							$relations[ camelize($value->REFERENCED_TABLE_NAME)] = array( 
								'relationship' => 'has_one',
								'use'          => $config['wwicommon']['modelNamespace'].ucfirst(camelize($value->REFERENCED_TABLE_NAME)),
								'columns'      => $value->REFERENCED_COLUMN_NAME, 
							);
						}else if($value->RELATION = 'ONE2MANY'){
							$relations[camelize($value->REFERENCED_TABLE_NAME)] = array( 
								'relationship'        => 'has_many',
								'use'                 => $config['wwicommon']['modelNamespace'].ucfirst(camelize($value->REFERENCED_TABLE_NAME)),
								'foreign_table'       => $value->REFERENCED_TABLE_NAME,
								'foreign_key_columns' => $value->REFERENCED_COLUMN_NAME,
								'foreign_table_pkey'  => array('class_id','student_id'),
							);


						}

					}

					break;
			}

		}

		return $relations;

	}

}