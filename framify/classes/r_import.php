<?php

	/*
	
		db		host		user		pass		table
	
	*/

	include("r_connection.php");
	
	$conn = new connection($_REQUEST['db'],$_REQUEST['host'],$_REQUEST['user'],$_REQUEST['pass'],$_REQUEST['callback']);
	
	$con = $conn->query(" TRUNCATE TABLE ". $_REQUEST['table'],false);
	
	if(@$con){
		
	}

?>