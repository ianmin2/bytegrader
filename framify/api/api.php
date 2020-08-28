<?php
header("Content-Type:application/json");
class DissertationAPI
{
    public $c;

    public function __construct($connection)
    {

        $this->c  = $connection;
    }


    public function getRoutes()
    {
        return $this->c->printQueryResults("SELECT * FROM routes;");
    }


    public function getAssignments()
    {
        return $this->c->printQueryResults("SELECT * FROM assignments;");
    }


    public function getUsers()
    {

        return $this->c->printQueryResults("SELECT id,name,email,created_at FROM users;");
    }


    public function getChainings()
    {
        return $this->c->printQueryResults("SELECT * FROM chainings;");
    }

    public function getAttempts()
    {
        return $this->c->printQueryResults("SELECT * FROM attempts;");
    }



    //@ ADDER FUNCTION
    public function addFunc($addData)
    {

        $table  = $addData["table"];
        $extras = @$addData["extras"];

        unset($addData["table"]);
        unset($addData["extras"]);
        unset($addData["specifics"]);


        $keys   = [];
        $values = [];

        while ($field_name = current($addData)) {
            //echo key($addData).' '.$field_name.'<br>';
            array_push($keys, key($addData));
            array_push($values, $field_name);
            next($addData);
        }

        $field_names  = "(";
        $field_values = "(";



        foreach ($keys as $pos => $field) {

            $field_names  .= $field . ",";

            $field_values .= "'" . $this->sanitize($values[$pos]) . "',";
        }

        $field_names     = rtrim($field_names, ",") . ")";
        $field_values    = rtrim($field_values, ",") . ")";

        $query = "INSERT INTO " . $table . " " . $field_names . " VALUES " . $field_values . " " . @$extras;

        // return $this->c->makeResponse(200,$query);
        return $this->c->aQuery($query, true, $table . " record added", "Failed");
    }

    //@ CUSTOM COUNTER
    public function countFunc($countData)
    {

        $table      = $countData["table"];
        $extras     = @$countData["extras"];
        $specifics    = (@$getData["specifics"] != NULL) ? $getData["specifics"] : "*";

        unset($countData["table"]);
        unset($countData["extras"]);
        unset($countData["specifics"]);

        $keys   = [];
        $values = [];

        while ($field_name = current($countData)) {
            //echo key($addData).' '.$field_name.'<br>';
            array_push($keys, key($countData));
            array_push($values, $field_name);
            next($countData);
        }

        $conditions = [];

        foreach ($keys as $pos => $field) {

            array_push($conditions, $field . "='" . $this->sanitize($values[$pos]) . "'");
        }
        $conditions = (sizeof($conditions) > 0) ? (" WHERE " . implode(" AND ", $conditions)) : "";

        $query = "SELECT count($specifics) FROM " . $table . " " . $conditions . "" . @$extras;

        //return $this->c->wrapResponse(200,$query,"");
        $result = $this->c->printQueryResults($query, true, false);

        $postgres     = ($result[0]["count"] != null && $result[0]["count"] != "") ? $result[0]["count"] : $result[0]["count(" . $specifics . ")"];
        $mysql         = ($result[0]["count(*)"] != null && $result[0]["count(*)"] != "") ? $result[0]["count(*)"] : $result[0]["count(" . $specifics . ")"];

        $count = ($result[0]["count"] != null && $result[0]["count"] != "") ? $postgres : $mysql;

        // return json_encode( $count );

        return $this->c->wrapResponse(200, $count, true);
    }

    //@ SPECIFIC GETTER
    public function getFunc($getData)
    {

        $table      = $getData["table"];
        $extras     = (@$getData["extras"] != null) ? $getData['extras'] : " ";
        $specifics    = (@$getData["specifics"] != NULL) ? $getData["specifics"] : "*";

        unset($getData["table"]);
        unset($getData["extras"]);
        unset($getData["specifics"]);

        $keys   = [];
        $values = [];

        while ($field_name = current($getData)) {
            //echo key($addData).' '.$field_name.'<br>';
            array_push($keys, key($getData));
            array_push($values, $field_name);
            next($getData);
        }

        $conditions = [];

        foreach ($keys as $pos => $field) {

            array_push($conditions, $field . "='" . $this->sanitize($values[$pos]) . "'");
        }
        $conditions = (sizeof($conditions) > 0) ? (" WHERE " . implode(" AND ", $conditions)) : "";

        $query = "SELECT " . $specifics . " FROM " . $table . " " . $conditions . " " . @$extras;

        //return $this->c->wrapResponse(200,$query,"");
        return $this->c->printQueryResults($query, true, true);
    }

    //@ COMPREHENSIVE GETTER
    public function getAllFunc($getAllData)
    {

        $table  = $getAllData["table"];
        $extras = @$getAllData["extras"];
        $specifics    = (@$getAllData["specifics"] != NULL) ? $getAllData["specifics"] : "*";

        unset($getAllData["table"]);
        unset($getAllData["extras"]);
        unset($getAllData["specifics"]);

        $query = "SELECT " . $specifics . " FROM " . $table . " " . @$extras;

        //return $this->c->wrapResponse( 200, $query );

        return $this->c->printQueryResults($query, true, true);
    }

    //@ DELETE  FUNCTION ASSIST
    public function delFunc($deleteData)
    {

        $table  = $deleteData["table"];
        $extras = @$deleteData["extras"];
        //$specifics	= ( @$deleteData["specifics"] != NULL ) ? $deleteData["specifics"] : "*";

        unset($deleteData["table"]);
        unset($deleteData["extras"]);
        unset($deleteData["specifics"]);

        $keys   = [];
        $values = [];

        while ($field_name = current($deleteData)) {
            //echo key($addData).' '.$field_name.'<br>';
            array_push($keys, key($deleteData));
            array_push($values, $field_name);
            next($deleteData);
        }

        $conditions = [];

        foreach ($keys as $pos => $field) {

            array_push($conditions, $field . "='" . $this->sanitize($values[$pos]) . "'");
        }


        $conditions = (sizeof($conditions) > 0) ? (implode(" AND ", $conditions)) : " null=null ";

        //PREVENT THE DELETION OF ALL RECORDS WHERE NO IMPLICIT RULE IS SET {{using WHERE}}
        $query = "DELETE FROM " . $table . " WHERE " . $conditions . " " . @$extras;

        //return $this->c->wrapResponse(200,$query,"");
        return $this->c->aQuery($query, true, $table . " record deleted.", "Failed.");
    }

    //@ UPDATE FUNCTION ASSIST
    public function updateFunc($updateData)
    {

        $table  = $updateData["table"];
        $extras = (@$updateData["extras"] != NULL) ? $updateData["extras"] : "null=null";

        unset($updateData["table"]);
        unset($updateData["extras"]);
        unset($updateData["specifics"]);


        $keys   = [];
        $values = [];

        while ($field_name = current($updateData)) {
            array_push($keys, key($updateData));
            array_push($values, $field_name);
            next($updateData);
        }

        $update_string = "";



        foreach ($keys as $pos => $field) {

            $update_string .= $field . "='" . $this->sanitize($values[$pos]) . "',";
        }

        $update_string     = rtrim($update_string, ",");

        //PREVENT THE UPDATING OF ALL RECORDS WHERE NO IMPLICIT RULE IS SET {{using WHERE}}
        $query = "UPDATE " . $table . " SET " . $update_string . " WHERE " . @$extras;

        // return $this->c->makeResponse( 200, $query );
        return $this->c->aQuery($query, true, $table . " record updated.", "Failed.");
    }

    //@ TRUNCATE FUNCTION ASSIST
    public function truncateFunc($truncateData)
    {

        $table  = $truncateData["table"];
        $extras = @$truncateData["extras"];

        unset($truncateData["table"]);
        unset($truncateData["extras"]);
        unset($truncateData["specifics"]);

        $field_name  = array_keys($truncateData)[0];
        $field_value = $truncateData[$field_name];

        $query = "TRUNCATE TABLE " . $table . " " . @$extras;

        return $this->c->aQUery($query, true, $table . " table truncated.", "Failed.");
    }

    //@ DROP FUNCTION ASSIST
    public function dropFunc($dropData)
    {

        $table  = $dropData["table"];
        $extras = @$dropData["extras"];

        unset($dropData["table"]);
        unset($dropData["extras"]);
        unset($dropData["specifics"]);

        $field_name  = array_keys($dropData)[0];
        $field_value = $dropData[$field_name];

        $query = "DROP TABLE " . $table . " " . @$extras;

        return $this->c->aQUery($query, true, $table . " table dropped.", "Failed.");
    }


    //@ CUSTOM QUERYSTRING;
    public function customFunc($customData)
    {
        $ret = $this->c->printQueryResults($customData['query'], true, true);
        $v   = (array) json_decode($ret);
        $vd  = (array) $v["data"];
        $vm  = (array) $vd["message"];
        if (!$vm[0] && isset($customData['query2'])) {
            $ret = $this->c->printQueryResults($customData['query2'], true, true);
        }
        return $ret;
    }
}
