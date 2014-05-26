<?php
namespace WWICommon\Stark;

use WWICommon\Globals;

class StarkOrmLocal extends \WWICommon\Stark\StarkOrm
{
	private $__db;


	public function __construct($pk_value = array(), $select_star_data = null, $sync_dbh = null,$sync_select_dbh = null) {

		parent::__construct($pk_value, $select_star_data, $sync_dbh,$sync_select_dbh);
		
		if(empty($this->__db)){
			$this->__db = $this->provide_dbh();
		}

		//append schema
		$this->__schema = Globals::appendStarkORMSchema($this->__table,$this->__schema);
		$this->__relations = Globals::appendStarkORMRelations($this->__table,$this->__relations);

	
	}


    protected function provide_dbh()
    {
		$db = Globals::get("db");
        return $db; 
    }
    protected function provide_dbh_type()
    {
        return 'mysql'; 
    } 
   // protected function include_prefix()
   // {
     //   return APPLICATION_PATH .'/models/'; 
    //}



    public static function get_where($where = null, $limit_or_only_one = false, $order_by = null) {


        $db = Globals::get("db");
        ///  Because we are STATIC, and most everything we need is NON-STATIC
        ///    we first need a backtrace lead to tell us WHICH object is even
        ///    our parent, and then we can create an empty parent Non-Static
        ///    object to get the few params we need...
        $bt = debug_backtrace();
        if ( $bt[1]['function'] != 'get_where' && $bt[1]['function'] != 'get_where_cached' ) {
            trigger_error("Use of get_where() when not set up!  The hack for whetever object you are calling is not set up!<br/>\n
                           You need to add a get_where() stub to your object (the one you are referring to in ". $bt[0]['file'] ." on line ". $bt[0]['line'] ."), that looks like:<br/>\n".'
                           public static function get_where($where = null, $limit_or_only_one = false, $order_by = null) { return parent::get_where($where, $limit_or_only_one, $order_by);'."<br/>\n".'
                           public static function get_where_cached($where = null, $limit_or_only_one = false, $order_by = null) { return parent::get_where($where, $limit_or_only_one, $order_by); }
                           ' , E_USER_ERROR);
        }
        $parent_class = $bt[1]['class'];

        ///  Otherwise, just get the parent object and continue
        $tmp_obj = new $parent_class ();
        
        ///  Assemble a generic SQL based on the table of this object
        $values = array();

        $where_ary = array();
		if( $where ) {
		    foreach ($where as $col => $val) {
                ///  If the where condition is just a string (not an assocative COL = VALUE), then just add it..
                if ( is_int($col) ) { $where_ary[] = $val; }
                ///  Otherwise, basic ( assocative COL = VALUE )
                else { $where_ary[] = "$col = ?";  $values[] = $val; }
            }
        }
        $sql = "SELECT *
                  FROM ". $tmp_obj->get_table() ."
                 WHERE ". ( $where_ary ? join(' AND ', $where_ary) : '1' ) ."
                 ". ( ! is_null($order_by) ? ( "ORDER BY ". $order_by ) : '' ) ."
		   	  ". ( ( $limit_or_only_one !== true && $limit_or_only_one ) ? ( "LIMIT " . $limit_or_only_one ) : '' ) ."
                ";

       // if($debug){ bug($sql);exit; }
       // START_TIMER('SimpleORM get_where()', Simpleorm::$_SQL_PROFILE);
        if ( $bt[1]['function'] == 'get_where_cached' ) {
            $data = $db->fetchCached( array( $sql, $values) );
        }
        else { 
         //   START_TIMER('Zend->db->query', Simpleorm::$_SQL_PROFILE);
           // if ( Simpleorm::$_SQL_DEBUG ) trace_dump();
           // if ( Simpleorm::$_SQL_DEBUG ) bug($sql, $values);
			$sth = $db->prepare($sql,$values);
			$sth->execute();
			$data = $sth->fetchAll();
  
          //  END_TIMER('Zend->db->query', Simpleorm::$_SQL_PROFILE);
        }
        //END_TIMER('SimpleORM get_where()', Simpleorm::$_SQL_PROFILE);
  
        ///  Get the objs
        $objs = array();
        foreach ( $data as $row ) {
            $pk_values = array(); foreach( $tmp_obj->get_primary_key() as $pkey_col ) $pk_values[] = $row[ $pkey_col ];
            $objs[] = new $parent_class ( $pk_values, $row );
        }

        ///  If they only ask asking for one object, just guve them that, not the array
        return ( ($limit_or_only_one === true || $limit_or_only_one === 1) ? ( empty( $objs ) ? null :  (object)$objs[0] ) : $objs );
    }




}

