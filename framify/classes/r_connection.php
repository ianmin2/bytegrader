<?php

/**
    m4Labs Framework
    Simple PHP PDO based Database interaction class
    by: Ian Innocent  <ianmin2@live.com>
    
 */

class connection
{

    private $db_driver;
    public  $db_name;
    public  $db_host;
    private $db_username;
    private $db_password;
    private $db_port;
    private $con;

    public  $allbkup;
    public  $bkpath;
    public  $json_callback;
    public  $query_error = array();

    /* 
     * THE CLASS CONSTRUCTOR 
     * */
    function __construct($db_name = '', $db_host = 'localhost', $db_username = 'root', $db_password = '', $db_driver = 'mysql', $json_callback = '', $db_port = '3306')
    {

        $this->json_callback = $json_callback;

        if (@$db_host != '' && @$db_name != '' && @$db_username != '') :

            /* 
            * Instantiate the global variables 
            * */
            $this->db_host = @$db_host;
            $this->db_name = @$db_name;
            $this->db_driver = @$db_driver;
            $this->db_password = @$db_password;
            $this->db_username = @$db_username;
            $this->db_port = @$db_port;



            /* 
            * Establish a database connection 
            * */
            $this->db_connect();

        else :

            echo $this->wrap($this->makeResponse(500, "Could not initiate a connection due to insufficient data"));
            exit;

        endif;
    }

    /**
     *  THE ACTUAL DATABASE CONNECTION 
     *  */
    private final function db_connect()
    {

        $db_connection_string = "";

        switch (strtolower($this->db_driver)) {
            case 'sqlsrv':
                $db_connection_string =  @$_ENV["DEBUG"] == "true" ?  "{$this->db_driver}:server={$this->db_host},{$this->db_port};Database={$this->db_name};ConnectionPooling=10;" : "{$this->db_driver}:server={$this->db_host},{$this->db_port};Database={$this->db_name};ConnectionPooling=10;"; //Initial Catalog=${$this->db_name};Integrated Security=SSPI;";
                break;

            default:
                $db_connection_string = "{$this->db_driver}:host={$this->db_host};dbname={$this->db_name};port={$this->db_port}";
                break;
        }

        try {

            $this->con = @$_ENV["DEBUG"] == "true" ?  new PDO($db_connection_string, "$this->db_username", "$this->db_password") : new PDO($db_connection_string, NULL, NULL);
        } catch (PDOException $e) {

            echo $this->wrap($this->makeResponse(500, $e->getMessage()));
            exit;
        }
    }



    /** 
     * SHOW THE DATABASE CONNECTION DRIVERS AVAILABLE ON THE HOST MACHINE 
     * */
    public final function show_drivers()
    {

        return json_encode(PDO::getAvailableDrivers());
    }

    /** 
     * MAKE A RESPONSE BASING ON THE AVAILABILITY OF THE JSON_CALLBACK VARIABLE 
     * */
    public final function wrap($json_encoded_content = '')
    {

        $json_encoded_content = is_array($json_encoded_content) ? json_encode($json_encoded_content) : $json_encoded_content;

        if ($this->json_callback == "") :

            return $json_encoded_content;

        else :

            return $this->json_callback . "(" . $json_encoded_content . ")";

        endif;
    }


    /**
     * MAKE A WRAPPED RESPONSE 
     */
    public final function wrapResponse($status, $message = '', $command = '')
    {

        return $this->wrap($this->makeResponse($status, $message, $command));
    }

    /** 
     * THE BASIC DIE ON ERROR HANDLER 
     * */
    private final function die_on_error($stops = 'true', $data = "")
    {


        if ($stops) :

            /* 
    		 * Return the error to the client and stop execution 
    		 * */
            echo $this->wrap($this->makeResponse(500, $this->con->errorInfo(), $data));
            exit;

        else :

            /* 
    		 * If an error exists, add it to this sessions list of errors ( then / otherwise ) continue running the program  
    		 * */
            if (@$this->con->errorInfo()) {
                $this->query_error[] = $this->con->errorInfo();
            }

        endif;
    }

    /** 
     * THE BASIC QUERY PERFORMER 
     * */
    public final function query($statement, $stops = false)
    {

        /* 
    	 * Execute the query then fetch the returned associateive array for all values 
    	 * */
        // echo "Running the query `{$statement}`\n\n";
        $query = $this->con->query($statement) or $this->die_on_error($stops, $statement);
        return $query;
    }

    /** 
     * THE BASIC MULTI QUERY PERFORMER 
     * */
    public final function mQuery($statement, $stops = false)
    {

        /* 
    	 * Execute the query then fetch the returned associateive array for all values 
    	 * */
        $query = $this->con->multi_query($statement) or $this->die_on_error($stops, $statement);
        return $query;
    }


    /** 
     * THE ADVANCED QUERY  
     * { basic query with pre-defined conditional results }   
     * [ ideal for inserts, updates, and deletion  ]
     * */
    public final function aQuery($statement, $stops = false, $success, $failure, $scommand = '', $fcommand = '')
    {

        /* 
    	 * Do a basic query 
    	 * */
        $query = $this->query($statement, $stops);


        /* 
    	 * If the query is successfull 
    	 * */
        if ($query) :

            /* 
    		 * Model and return a success message 
    		 * */
            return $this->wrap($this->makeResponse(200, $success, $scommand));

        /* 
    	 * If the query is not successfull 
    	 */
        else :

            /* 
    		 * Model and return a failure message 
    		 */
            return $this->wrap($this->makeResponse(500, $failure, $fcommand));

        endif;
    }


    /** 
     * 	THE SUPER QUERY 
     * 	{ Returns all the values from a specific table field with the specified delimeter for each after the first} 
     * 	[ ideal for medium level queries ]
     * */
    public final function sQuery($statement, $dbField, $delimeter, $stops = false)
    {

        $elementArr = $this->query($statement, $stops);

        $elements = array();

        while ($el = $elementArr->fetch(PDO::FETCH_ASSOC)) {
            $elements[] = (count($elements) == 0) ? $el[$dbField] : "," . $el[$dbField];
        }

        return $elements;
    }

    public final function ctr($table)
    {
        $rsp = $this->query("Select count(*) as total FROM $table", true)->fetch(PDO::FETCH_ASSOC);
        return $this->wrapResponse(200, $rsp);
    }


    /**
     *  THE NUMBER OF ROWS ENUMERATOR
     *  */
    public final function num_rows($statement, $stops = false)
    {
        return  $this->query($statement, $stops)->rowCount();
    }


    /**
     * A SIMPLE QUERY RESULTS GENERATOR  
     */
    public final function  printQueryResults($statement, $stops = false, $encoded = false)
    {

        if (!$encoded) :

            /*
	    	 * return the results of the query as an associative array
	    	 * */
            return $this->query($statement, $stops)->fetchAll(PDO::FETCH_ASSOC);

        else :

            /*
    		 * return the results in JSON format
    		 * */
            return $this->wrap($this->makeResponse(200,  $this->query($statement, $stops)->fetchAll(PDO::FETCH_ASSOC), ""));

        endif;
    }

    /**
     * SIMPLE KEY => VALUE TYPE ARRAY GENERATOR
     * EXPECTS AN SQL STATEMENT, THE NAME OF THE IDENTIFIER KEY AND THE NAME OF THE VALUE KEY
     */
    public final function keyVal($sql_statement, $key_name, $value_name, $stops = false)
    {

        $arr = $this->query($sql_statement, $stops);
        $kvArr = [];

        foreach ($arr as $elem) {

            $kvArr[$elem[$key_name]] = $elem[$value_name];
        }

        return $kvArr;
    }


    /**
     * THE IMPLICIT ERROR DISPLAYER
     */
    public final function error_alert($status = true)
    {

        if ($status) :

            return $this->wrap($this->makeResponse(500, $this->query_error, ""));

        else :
            return 0;
        endif;
    }


    /**
     * PERFORM A TABLE SPECIFIC BACKUP
     */
    public final function doStore($table, $i = 0, $bkup_path = 'backup', $bkup_path_all = 'backup/history')
    {

        $bkupfile     = $this->bkpath . $table[$i] . '.sql';
        $pbkup         = $this->allbkup . $table[$i] . date("Y-M-D H.i.s") . '.sql';

        @unlink(str_replace("\\", "/", $bkupfile));


        $bkup = $this->query("SELECT * INTO OUTFILE '" . str_replace("\\", "/", $bkupfile) . "' FROM $table[$i]", true);

        if ($bkup);
        /*
    	 * Reserved for future ammendment
    	 	$respArray[] = $this->makeResponse(200," Stage 1 of backup complete! ", "", false);
    	else:
    		$respArray[] = $this->makeResponse(500," Stage 1 of backup failed! ", "", false);
    	endif;
    	*/
        $bkup    =    $this->query("SELECT * INTO OUTFILE '" . str_replace("\\", "/", $pbkup) . "' FROM $table[$i]", true);
        if ($bkup);
        /*
    	 * Reserved for future ammendment
    	 	$respArray[] = $this->makeResponse(200," Stage 2 of backup complete! ", "", false);
    	else:
    		$respArray[] = $this->makeResponse(500," Stage 2 of backup failed! ", "", false);
    	endif;
    	
    	return $this->wrap( json_encode( $respArray ) );
    	
    	*/
    }


    /**
     * PERFORM A COMPLETE [ or selective  ] BACKUP OF TABLES ( multiple ) 
     */
    public final function bkup($tables = "*", $bkup_path = 'backup', $bkup_path_all = 'backup/history')
    {

        $this->bkpath = getcwd() . '/' . $bkup_path . '/';
        $this->allbkup = getcwd() . '/' . $bkup_path_all . '/';

        /* 
    	 * If all the tables are to be backed up 
    	 * */
        if ($tables == '*') :
            $i = 0;
            $tables = $this->query("SHOW TABLES", true);
            while ($table = $tables->fetch(PDO::FETCH_ASSOC)) {
                $this->doStore($table, $i, $bkup_path, $bkup_path_all);
                $i++;
            }

        else :

            /*
    		 * Extract table names from the comma delimited string and perform a backup for each one
    		 */
            $i = 0;
            $tables = explode(",", $tables);

            foreach ($tables as $tabl) {

                $this->doStore($tables, $i, $bkup_path, $bkup_path_all);
                $i++;
            }

        endif;
    }

    /** 
     * PERFORM A COMPLETE DATABASE BACKUP
     */
    private  function bkup_restore($keyer = NULL, $tables = '*', $bkpath = 'backup')
    {

        /*
    	 * If credible authentication is provided, commence with backup
    	 * */

        if ($keyer == $this->db_password) :

            $i = 0;

            if ($tables == '*') :

                /*
	    		 * Restore data to all the tables in the database
	    		 * */
                $tables = $this->query("SHOW TABLES", true);
                while ($table = $tables->fetch(PDO::FETCH_ASSOC)) {
                    $this->doRestore($table, $i, $bkpath);
                }

            else :

                /*
    			 * Restore data to the specified tables
    			 * */
                $i = 0;
                $tables = explode(",", $tables);

                foreach ($tables as $tabl) {
                    $this->doRestore($tables, $i, $bkpath);
                    $i++;
                }

            endif;

        else :

            /*
    		 * Inform the user of their failed attempt to bridge security
    		 * */
            return $this->wrap($this->makeResponse(500, "Please provide a valid authentication key to proceed with the database restore", ""));


        endif;
    }


    /*
     * PERFORM AN ACTUAL DATABASE RESTORE
     * */
    public final function doRestore($table, $i = 0, $bkpath = 'backup')
    {

        /*
    	 * $success = $error = '';
    	 */

        $this->bkpath = getcwd() . '/' . $bkpath . '/';
        $bkupfile = $this->bkpath . $table[$i] . 'sql';

        $trunc = $this->query("Truncate $table[$i]", true);
        if ($trunc);

        $load = $this->query("LOAD DATA INFILE '" . str_replace("\\", "/", $bkupfile) . "' INTO TABLE $table[$i] ", true);
        if ($load);
    }


    /** 
     * THE API STYLE JSON [optional] RESPONSE FORMULATOR 
     * */
    public final function makeResponse($response = '', $message = '', $command = '', $encode = true, $wrap = false)
    {

        if ($encode) :

            /*
	         * JSON encode and return the response
	         * */
            if (!$wrap) {
                return json_encode(array("response" => strtoupper($response), "data" => array("message" => $message, "command" => $command)));
            } else {
                return $this->wrap(json_encode(array("response" => strtoupper($response), "data" => array("message" => $message, "command" => $command))));
            }

        else :

            /*
        	 * return the response as is
        	 * */
            return array("response" => strtoupper($response), "data" => array("message" => $message, "command" => $command));


        endif;
    }


    /**
     * RECURRSIVELY SEARCH IN ARRAY
     **/
    public final function inArray($needle, $haystack)
    {

        $found = false;

        foreach ($haystack as $item) {

            if ($item == $needle) {
                $found = $item;
            } else if (is_array($item)) {

                $found = $this->inArray($needle, $item);
                if ($found) {
                    break;
                }
            }

            return $found;
        }
    }
}

/*
$test = new connection("tests", "localhost", "root", "146450f7", "mysql", @$_REQUEST['callback']);

echo ( $test->printQueryResults( "SELECT * FROM tests.1464", true , true ) );

//echo "\n<pre>\n", print_r( $test->printQueryResults( "SELECT * FROM tests.1464", true , false ) ), "\n</pre>\n";

//echo "\n",$test->num_rows("SELECT * FROM tests.1464", true ),"\n";

//echo "\n",$test->aQuery("INSERT INTO tests.1464 ( username, password ) VALUES ( 'ianmin2', 'ianmin2' ) ", false, "Query successfully executed", "An Error was met whilst performing the query"),"\n";  

//echo "\n",$test->aQuery("SELECT * 1464 responses", true, "IT WORKED", "IT FAILED"),"\n";

//echo "\n<pre>\n",print_r( $test->sQuery("SELECT * FROM 1464", "username", ",", true ) ), "\n</pre>\n";

//echo "\n<pre>\n";

    //print( $test->show_drivers() );

//echo "\n</pre>\n";

*/
