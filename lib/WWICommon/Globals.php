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


  

    	foreach ($rel['CONSTRAINTS'] as $key => $value) {
    		//dbug($key);
    		/*
    		if(!in_array($key, $setSchemaKeys)){
    			$schema[$key] = array();
    		}
    		*/
    	}
    	return $relations;

    }

}