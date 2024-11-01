<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ben
 * Date: 3/31/15
 * Time: 10:58 AM
 * To change this template use File | Settings | File Templates.
 */
class DSCF_DTG_StandardLinkRecord{

    const TABLE_NAME = '';
    const COLUMN_A = '';
    const COLUMN_B = '';


    public function __construct($valueA, $valueB){

        global $wpdb;
        $table_name = static::getTableName();

        //Save in the DB
        try{
            $wpdb->insert(
                $table_name,
                array(
                    'blogId' => get_current_blog_id(),
                    static::COLUMN_A => $valueA,
                    static::COLUMN_B => $valueB
                ),
                array(
                    '%d',
                    '%d',
                    '%d'
                )
            );

            // Good finish - return true
            return true;

        }catch(Exception $e){
            // An error occurred
        }

        return false;

    }

    /*
     * Return the table name -- Child classes override this method
     */
    public static function getTableName(){
        global $wpdb;
        $name = $wpdb->base_prefix . WPDEVHUB_CONST_DTG_SLUG . '-' . static::TABLE_NAME;
        $name = str_replace("-","_",$name);
        return $name;
    }

    public static function deleteSingleRelationship($valueA, $valueB){
        // Setup the variables
        global $wpdb;
        $tableName = static::getTableName();

        // Execute the Query
        $result = $wpdb->delete(
            $tableName,
            array(
                'blogId' => get_current_blog_id(),
                static::COLUMN_A => $valueA,
                static::COLUMN_B => $valueB ),
            array(
                '%d',
                '%d',
                '%d'
            )
        );

        // Return the result
        return $result;
    }

    public static function deleteAllForColumn($column, $value){
        // Setup the variables
        global $wpdb;
        $tableName = static::getTableName();

        // Column Name Validation
        if( ($column != static::COLUMN_A) && ($column != static::COLUMN_B) ){
            // Invalid Column passed - exit
            return false;
        }

        // Execute the Query
        $result = $wpdb->delete(
            $tableName,
            array(
                'blogId' => get_current_blog_id(),
                $column => $value ),
            array(
                '%d',
                '%d'
            )
        );

        // Return the result
        return $result;
    }

    public static function getAll($start=0, $limit=10000){

        // Setup the variables
        global $wpdb;
        $tableName = static::getTableName();

        // Query the Data
        $sql = $wpdb->prepare(
            "
            SELECT * FROM $tableName
            WHERE blogId=%d
            ORDER BY id DESC
            LIMIT %d,%d
            ",
            get_current_blog_id(),
            $start,
            $limit
        );

        // Get the results
        $results = self::executeQuery($sql, ARRAY_A);

        return $results;
    }

    public static function getAllByForColumn($column, $value){



        // Setup the variables
        global $wpdb;
        $tableName = static::getTableName();

        // Column Name Validation
        if( ($column != static::COLUMN_A) && ($column != static::COLUMN_B) ){
            // Invalid Column passed - exit
            error_log("Invalid Column Name Passed - exiting function[".__CLASS__."::".__FUNCTION__."]");
            return false;
        }

        //error_log(__CLASS__."::".__FUNCTION__." - Inside function column[$column] value[$value] tablename[$tableName]");

        // Query the Data
        $sql = $wpdb->prepare(
            "
            SELECT * FROM $tableName
            WHERE $column = %d
              AND blogId=%d
            ",
            $value,
            get_current_blog_id()
        );

        //error_log(__CLASS__."::".__FUNCTION__." - SQL : $sql");

        // Get the results
        $results = self::executeQuery($sql, ARRAY_A);

        return $results;

    }

    public static function getSingleRelationship($valueA, $valueB, $start=0, $limit=10000){

        // Setup the variables
        global $wpdb;
        $tableName = static::getTableName();
        $columnA = static::COLUMN_A;
        $columnB = static::COLUMN_B;

        // Query the Data
        $sql = $wpdb->prepare(
            "
            SELECT * FROM $tableName
            WHERE $columnA = %d
              AND $columnB = %d
              AND blogId=%d
            ORDER BY id DESC
            LIMIT %d,%d
            ",
            $valueA,
            $valueB,
            get_current_blog_id(),
            $start,
            $limit
        );

        // Get the result as a single row
        $result = self::executeRowQuery($sql, ARRAY_A);

        return $result;

    }

    public static function getCountForColumn($column, $value){

        // Setup the variables
        global $wpdb;
        $tableName = static::getTableName();

        // Query the Data
        $sql = $wpdb->prepare(
            "
            SELECT count(*) FROM $tableName
            WHERE %s = %d
              AND blogId=%d
            ",
            $column,
            $value,
            get_current_blog_id()
        );

        // Get the result as a single row
        $count= self::executeColQuery($sql);

        return $count;

    }

    /*
    * A generic Query wrapper to get a single result from the DB
    */
    public static function executeColQuery( $sql, $offset=0 ){
        global $wpdb;
        return $wpdb->get_col( $sql, $offset );
    }

    /*
    * A generic Query wrapper to get a single result from the DB
    */
    public static function executeRowQuery( $sql, $outputType = ARRAY_A, $offset=0 ){
        global $wpdb;
        return $wpdb->get_row( $sql, $outputType, $offset );
    }

    /*
     * A generic query wrapper to execute a query that returns results
     */
    public static function executeQuery( $sql, $outputType = ARRAY_A ){
        global $wpdb;
        return $wpdb->get_results( $sql, $outputType );
    }

    /*
    * A generic query wrapper to execute a query, returns a numeric value indicating number of rows effected
    */
    public static function executeGenericQuery( $sql){
        global $wpdb;
        return $wpdb->query( $sql );
    }
}
