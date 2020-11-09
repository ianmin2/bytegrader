<?php
header("Content-Type:application/json");
include __DIR__."/../workers/grading/index.php";

class DissertationAPI
{
    public $c;
    public $grading_worker;

    public function __construct($connection)
    {        
        $this->c  = $connection;
    }

    private function generateToken($userInfo)
    {
        return $GLOBALS['jwt']->encode($userInfo);
    }


    private function processFieldNamesAndValues($fieldData)
    {
        while ($field_name = current(($fieldData))) {
            $fieldData[key($fieldData)] = $this->sanitize($field_name);
            next($fieldData);
        }
        return $fieldData;
    }

    private function getFieldNamesAndValues($fieldsData, $returnUpdateString = false)
    {
        $keys   = [];
        $values = [];
        $updateString = "";

        $field_names  = "(";
        $field_values = "(";
        $field_indexer = [];

        foreach ($fieldsData as $key => $value) {
            if ($value != null) {
                array_push($keys, $key);
                array_push($values, $value);

                $current_key = $this->sanitize($key) . ",";
                $current_val =  ($key == 'password')
                    ?
                    "'{$value}'"
                    : ($key == "updated_at" || $key == "assignment_last_modified")
                    ?
                    "getdate()"
                    :
                    "'" . $this->sanitize(is_array($value)
                        ?
                        json_encode($value)
                        :
                        $value) . "',";

                if ($returnUpdateString) $updateString .= " {$this->sanitize($key)}=${current_val}";

                $field_names  .= $current_key;
                $field_values .=  $current_val;
            }
        }

        $field_names     = rtrim($field_names, ",") . ")";
        $field_values    = rtrim($field_values, ",") . ")";
        $updateString    = rtrim($updateString, ",");

        return ["keys" => $field_names, "values" => $field_values, "raw_keys" => $keys, "raw_values" => $values, 'update_string' => $updateString];
    }

    private function ensureExists($expected = [], $provided = [])
    {
        $validated = true;
        foreach ($expected as $pos => $field) {
            if (@$provided[$field] == null) $validated = false;
        }
        return $validated;
    }


    //=============================================================================
    //# USERS
    //=============================================================================

    public function getUsers()
    {
        return $this->c->printQueryResults("SELECT id,name,email,username,user_active,user_last_seen,created_at FROM users;", true, true);
    }


    public function addUser($userData)
    {

        $userData['password'] = password_hash($this->sanitize($userData['password']), PASSWORD_DEFAULT);

        $processed_values = $this->getFieldNamesAndValues($userData);

        $procesed_email = $this->sanitize($userData['email']);

        //@ Encrypt the provided password [if one is defined]
        // return $this->c->makeResponse(200, $userData);
        // return $this->c->makeResponse(200, "INSERT INTO users {$processed_values['keys']} VALUES {$processed_values['values']}");
        $this->c->aQuery("INSERT INTO users {$processed_values['keys']} VALUES {$processed_values['values']}", true, " User registered.", "User Registration Failed!");

        //@ Fetch the newly inserted user data [inefficient I know but it ain't fun re-doing a project from scratch; was previously using an ORM with laravel]
        $specificData = $this->c->printQueryResults("SELECT id,name,email,username,user_active,user_last_seen,created_at FROM users WHERE email='{$procesed_email}' OR username='{$procesed_email}'");
        if (is_array($specificData)) $specificData = $specificData[0];

        //@ Update the "last seen" field
        $this->c->query("Update users set user_last_seen=getdate() WHERE id=" . $specificData["id"] . ";");

        //@ return the JWT token [where applicable]
        return $this->c->wrap($this->generateToken($specificData));
    }

    public function loginUser($loginData)
    {
        if (!$this->ensureExists(['username', 'password'], $loginData)) die($this->c->wrapResponse(422, 'Not all required data was provided'));

        //@ Run the filter [for good measure and to minimize the possibility of SQL injection]
        $processed_values = $this->processFieldNamesAndValues($loginData);
        $matchingUsers = $this->c->printQueryResults("SELECT id,name,email,username,user_active,user_last_seen,created_at,password FROM users WHERE email='" . $processed_values['username'] . "' OR username='" . $processed_values['username'] . "';");


        if (!is_array($matchingUsers)) die(($this->c->wrapResponse(404, 'No matching account was found', $processed_values)));
        $matchingUsers = $matchingUsers[0];

        //@ Otherwise ensure that the passwords match
        if (!password_verify($loginData["password"], $matchingUsers["password"])) die($this->c->wrapResponse(401, "Invalid access credentials, try again."));
        //@ Remember to remove the password before issuing the JWT
        unset($matchingUsers["password"]);

        //@ Update the "last seen" field
        $this->c->query("Update users set user_last_seen=getdate() WHERE id=" . $matchingUsers["id"] . ";");

        //@ Continue with JWT token creation here
        return $this->c->wrap($this->generateToken($matchingUsers));
    }

    public function updateUser($userData)
    {
        if ($userData['id']) {

            $update_id = $userData['id'];
            unset($userData['id']);

            //@ Update the updated at field
            $userData['updated_at'] = 'getdate()';

            //@ Encrypt the provided password [if one is defined]
            $userData['password'] = $userData['password'] ? password_hash($this->sanitize($userData['password']), PASSWORD_DEFAULT) : null;

            $processed_values = $this->getFieldNamesAndValues($userData, true);

            //@ Perform the actual update
            $this->c->aQuery("UPDATE users SET {$processed_values['update_string']} WHERE id='{$this->sanitizeThoroughly($update_id)}'", true, " User updated.", "User update Failed!");

            //@ Fetch the newly updated user data [inefficient ...yeah, yeah; as I said was previously using an ORM with laravel and this [complete assignment redo]  is nothing short of outright annoying and unnecessary]
            $specificData = $this->c->printQueryResults("SELECT id,name,email,username,user_active,user_last_seen,created_at FROM users WHERE id='{$this->sanitizeThoroughly($userData['id'])}';");
            if (is_array($specificData)) $specificData = $specificData[0];

            //@ Update the "last seen" field
            $this->c->query("Update users set user_last_seen=getdate() WHERE id=" . $specificData["id"] . ";");

            //@ return the JWT token [where applicable]
            return $this->c->wrap($this->generateToken($specificData));
        } else {
            //@ Ask the users to improve themselves
            return $this->c->wrapResponse(401, "No such user exists!");
        }
    }

    //=============================================================================
    //# ASSIGNMENTS
    //=============================================================================
    public function getAssignment($assignmentId)
    {

        $routesData = json_decode($this->getRoute($assignmentId, true), true);

        $assignmentQuery = "SELECT 
        assignment_id, assignment_name, assignment_owner, assignment_created, 
        assignment_due, assignment_summary, assignment_last_modified, assignment_notes,
        users.name as assignment_owner_name, users.email as assignment_owner_email
        FROM assignments 
            LEFT JOIN users
                ON 
                    assignments.assignment_owner = users.id
        WHERE 
        assignments.assignment_id = {$this->sanitizeThoroughly($assignmentId)};";

        $assignment_array = $this->c->printQueryResults($assignmentQuery, true, false);
        if (count($assignment_array) > 0) {
            $assignment_array[0]["routes"] =  $routesData['data']['message'];
        }
        return  $this->c->wrapResponse(200, $assignment_array);
    }


    public function getAssignments()
    {
        $assignmentsQuery = "SELECT 
        assignment_id, assignment_name, assignment_owner, assignment_created, 
        assignment_due, assignment_summary, assignment_last_modified, assignment_notes,
        users.name as assignment_owner_name, users.email as assignment_owner_email        
            FROM assignments 
        LEFT JOIN users
            ON 
                assignments.assignment_owner = users.id;";
        $assignment_list = $this->c->printQueryResults($assignmentsQuery, true, true);
        return  is_array($assignment_list) == true ? $this->c->wrap(200, $assignment_list) : $assignment_list;
    }

    public function addAssignment($assignmentData)
    {
        //@ Capture the processed values
        $processed_values = $this->getFieldNamesAndValues($assignmentData);

        //@ Kill if not enough info
        if (@$processed_values["raw_keys"][0] == null) die($this->c->wrapResponse(412, "Not enough assignment data."));

        return ($this->c->aQuery("INSERT INTO assignments {$processed_values['keys']} VALUES {$processed_values['values']};", true, " Assignment Added.", "Assignment Addition Failed!"));
    }

    public function updateAssignment($updateData)
    {
        if ($updateData['assignment_id']) {

            $update_id = $updateData['assignment_id'];
            unset($updateData['assignment_id']);

            //@ Update the updated at field
            $updateData['assignment_last_modified'] = 'getdate()';


            //@ Encrypt the provided password [if one is defined]
            if ($updateData['password']) $updateData['password'] =  password_hash($this->sanitize($updateData['password']), PASSWORD_DEFAULT);

            $processed_values = $this->getFieldNamesAndValues($updateData, true);

            //@ Perform the actual update
            return $this->c->aQuery("UPDATE assignments SET {$processed_values['update_string']} WHERE assignment_id='{$this->sanitizeThoroughly($update_id)}'", true, " Assignment updated.", "Assignment update Failed!");
        } else {
            //@ Ask the requesting user[s] to improve themselves
            return $this->c->wrapResponse(401, "No such assignment exists!");
        }
    }


    //=============================================================================
    //# RULES
    //=============================================================================

    private function filterByParameter($paramName,$paramValue, $matches = false)
    { 
        return function($rule) use ($paramName,$paramValue, $matches){           
           return $matches ? $rule[$paramName] != $paramValue : $rule[$paramName] == $paramValue ;
        };
    }

    private function addParameterToArray($haystack,$paramName="",$paramValue){
        for ($i=0; $i < count($haystack); $i++) { 
           $haystack[$i][$paramName] = $paramValue;            
        }
        return $haystack;
    }

    private function getParameterValuesAsArray($haystack=[], $paramName="", $parseInt = false){
        $result_array = [];
        for ($i=0; $i < count($haystack); $i++) {
            array_push($result_array,$parseInt ? (int)$haystack[$i][$paramName] :  $haystack[$i][$paramName]);            
        }
        return $result_array;
    }

    private function unwrap($obj){        
        return json_decode($obj,true)["data"]["message"];
    }

    public function getRoute($identifier, $IDisAssignment = false, $grouped = false)
    {
        $IDisAssignment = $IDisAssignment ? 'rule_assignment' : 'rule_id';

        $routesQuery = "
        SELECT 
        rule_id, rule_method, rule_path, rule_name, rule_description, rule_assignment , 
            assignments.assignment_name,
            assignments.assignment_owner,
            id as assignment_owner_id, 
            name as assignment_owner_name, 
            email as rule_assignment_owner_email,
            rule_expected_status_code, rule_expected_data_type, rule_expected_data, rule_headers, rule_parameters, rule_grading,
            routes.created_at, routes.updated_at
        FROM routes
            LEFT JOIN assignments
                ON assignments.assignment_id = routes.rule_assignment
            JOIN users 
                ON assignments.assignment_owner = users.id
        WHERE {$IDisAssignment}={$this->sanitizeThoroughly($identifier)};
        ";

        $rules_list = ($this->c->printQueryResults($routesQuery, true, true));
        if(!$grouped) return $rules_list;
        $filterForForeignRules = $this->filterByParameter($IDisAssignment,$identifier,true);
        $foreign_rules = $this->getRoutes();
        $foreign_rules = $this->addParameterToArray($this->unwrap($foreign_rules), "parent_rules", []);
        $rules_list    = $this->addParameterToArray($this->unwrap($rules_list), "parent_rules", []);
        $all_rule_ids = $this->getParameterValuesAsArray( $foreign_rules, "rule_id",true);
        $foreign_rules = array_filter($foreign_rules, $filterForForeignRules);
        return $this->c->wrapResponse(200,["owned" => $rules_list, "public"=> $foreign_rules, "ids" => $all_rule_ids ]);

        // return  is_array($rules_list) ? $this->c->wrapResponse(200, $rules_list) : $rules_list;
    }



    public function getRoutes()
    {

        $routesQuery = "
        SELECT 
        rule_id, rule_method, rule_path, rule_name, rule_description, rule_assignment , 
            assignments.assignment_name,
            assignments.assignment_owner,
            id as assignment_owner_id, 
            name as assignment_owner_name, 
            email as rule_assignment_owner_email,
            rule_expected_status_code, rule_expected_data_type, rule_expected_data, rule_headers, rule_parameters, rule_grading,
            routes.created_at, routes.updated_at
        FROM routes
            LEFT JOIN assignments
                ON assignments.assignment_id = routes.rule_assignment
            JOIN users 
                ON assignments.assignment_owner = users.id
        ";

        $rules_list = ($this->c->printQueryResults($routesQuery, true, true));
        return  is_array($rules_list) ? $this->c->wrap(200, $rules_list) : $rules_list;
    }

    public function addRoute($routeData)
    {

        $processed_values = $this->getFieldNamesAndValues($routeData);

        //@ Kill if not enough info
        if (@$processed_values["raw_keys"][0] == null) die($this->c->wrapResponse(412, "Not enough grading rule data."));

        return ($this->c->aQuery("INSERT INTO routes {$processed_values['keys']} VALUES {$processed_values['values']}", true, "Assignment Rule registered.", "Failed to register the assignment rule!"));
    }

    public function updateRoute($updateData)
    {
        if ($updateData['rule_id']) {

            $update_id = $updateData['rule_id'];
            unset($updateData['rule_id']);

            //@ Update the updated at field
            $updateData['updated_at'] = 'getdate()';


            //@ Encrypt the provided password [if one is defined]
            if ($updateData['password']) $updateData['password'] =  password_hash($this->sanitize($updateData['password']), PASSWORD_DEFAULT);

            $processed_values = $this->getFieldNamesAndValues($updateData, true);

            //@ Perform the actual update
            return $this->c->aQuery("UPDATE routes SET {$processed_values['update_string']} WHERE rule_id='{$this->sanitizeThoroughly($update_id)}'", true, " Route updated.", "Route update Failed!");
        } else {
            //@ Ask the requesting user[s] to improve themselves
            return $this->c->wrapResponse(401, "No such route rule exists!");
        }
    }


    //=============================================================================
    //# CHAINING
    //=============================================================================
    public function getChainings()
    {
        return ($this->c->printQueryResults("SELECT * FROM chainings;"));
    }


    private function toJSON( $dta ){
        try {
            $converted = json_encode($dta);
            return $converted;
        } catch (Throwable $th) {
            return dta;
        }
    }

    public function addChaining($chainingData)
    {
       

        //@ validate the chaining settings        
        $chaining_validation = new GradingWorker( $chainingData["chaining_rules"] , [], $this->c, true);
        $validation_result = $chaining_validation->validateRules();

        print_r($validation_result);
        exit;

        //@ Convert to JSON for convenient storage;
        // $grading_rules =  $this->toJSON($chainingData["chaining_rules"]);
        
        // print_r($chaining);
        // exit;
        // $chaining_validation = new GradingWorker( $prepared , [], $this->c, true);
        // return $chaining_validation;
        // // if($chaining_validation) return $chaining_validation;

        $processed_values = $this->getFieldNamesAndValues($chainingData);
        return ($this->c->aQuery("INSERT INTO chainings {$processed_values['keys']} VALUES {$processed_values['values']}", true, "Assignment Chaining Added.", "Failed to records assignment chaining!"));
    }

    public function updateChaining($updateData)
    {
        if ($updateData['chaining_id']) {

            $update_id = $updateData['chaining_id'];
            unset($updateData['chaining_id']);

            //@ Update the updated at field
            $updateData['updated_at'] = 'getdate()';


            //@ Encrypt the provided password [if one is defined]
            if ($updateData['password']) $updateData['password'] =  password_hash($this->sanitize($updateData['password']), PASSWORD_DEFAULT);

            $processed_values = $this->getFieldNamesAndValues($updateData, true);

            //@ Perform the actual update
            return $this->c->aQuery("UPDATE chainings SET {$processed_values['update_string']} WHERE chaining_id='{$this->sanitizeThoroughly($update_id)}'", true, " Assignment chaining updated.", "Assignment chaining update Failed!");
        } else {
            //@ Ask the requesting user[s] to improve themselves
            return $this->c->wrapResponse(401, "No such assignment chaining exists!");
        }
    }

    //=============================================================================
    //# ATTEMPTS
    //=============================================================================
    public function getAttempts()
    {
        return ($this->c->printQueryResults("SELECT * FROM attempts;", true, true));
    }

    public function addAttempt($attemptData)
    {
        $processed_values = $this->getFieldNamesAndValues($attemptData);
        return ($this->c->aQuery("INSERT INTO attempts {$processed_values['keys']} VALUES {$processed_values['values']}", true, "Attempt registered.", "Failed to record assignment attempt!"));
    }


    public function updateAttempt($updateData)
    {
        if ($updateData['attempt_id']) {

            $update_id = $updateData['attempt_id'];
            unset($updateData['attempt_id']);

            //@ Update the updated at field
            $updateData['updated_at'] = 'getdate()';

            //@ Encrypt the provided password [if one is defined]
            if ($updateData['password']) $updateData['password'] =  password_hash($this->sanitize($updateData['password']), PASSWORD_DEFAULT);

            $processed_values = $this->getFieldNamesAndValues($updateData, true);

            //@ Perform the actual update
            return $this->c->aQuery("UPDATE attempts SET {$processed_values['update_string']} WHERE attempt_id='{$this->sanitizeThoroughly($update_id)}'", true, " Assignment attempt updated.", "Assignment attempt update Failed!");
        } else {
            //@ Ask the requesting user[s] to improve themselves
            return $this->c->wrapResponse(401, "No such assignment attempt exists!");
        }
    }


    //=============================================================================
    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@ GENERAL @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    //=============================================================================

    public function sanitize($val)
    {
        $val = (preg_replace('/\;/i', ',', $val));
        $val = preg_replace("/'/i", "&apos;", $val);
        return $val;
        // return $this->sanitizeThoroughly($val);
    }

    public function sanitizeThoroughly($val)
    {
        return  htmlspecialchars(preg_replace('/\;/i', ',', $val), ENT_QUOTES);
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
        return $this->c->aQuery($query, true,  " record added", "Failed");
    }

    //@ CUSTOM COUNTER
    public function countFunc($countData)
    {

        $table      = $countData["table"];
        $extras     = @$countData["extras"];
        $specifics    = (@$countData["specifics"] != NULL) ? $countData["specifics"] : "*";

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
