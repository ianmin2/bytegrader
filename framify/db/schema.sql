INSERT INTO assignments([assignment_id],[assignment_name],[assignment_owner],[assignment_due],[assignment_summary],[assignment_notes],[assignment_created],[assignment_last_modified]) VALUES
 (1,'Sample 1',1,'2020-10-19 23:00:00.0000000 +00:00','Sample Assignment summary','None really','2020-09-10 10:33:09.1266667 +00:00',NULL)
,(4,'Sample 4',1,'2020-10-18 23:00:00.0000000 +00:00','Sample Assignment summary','None really','2020-09-10 11:10:17.4633333 +00:00',NULL)
,(5,'Assignment Cinq',1,'2020-10-13 23:00:00.0000000 +00:00','An awesome Assignment','Just be ready! For buroken hingrish','2020-09-10 11:12:30.9200000 +00:00',NULL)
,(6,'Assignment Six',1,'2020-10-18 23:00:00.0000000 +00:00','Youd better be ready','Think about it real hard','2020-09-10 11:13:56.0066667 +00:00',NULL)
,(12,'Sample 12',2,'2020-10-27 00:00:00.0000000 +00:00','How could a summary be null?','No notes?','2020-09-12 17:20:15.2066667 +00:00',NULL)
,(10002,'Post Form Validation',2,'2020-10-19 23:00:00.0000000 +00:00','Not your ordinary assignment','No notes','2020-09-12 20:36:47.1933333 +00:00',NULL)
,(10003,'This was previously pure nonesense',2,'2020-10-27 00:00:00.0000000 +00:00','Edited summary','Updated notes','2020-09-13 16:55:56.8833333 +00:00',NULL)
,(10004,'A sample real world Assignment',1,'2020-12-07 00:00:00.0000000 +00:00','A simple assignment aimed at showcasing the functionality of the grade assistant','A sample real world Assignment.

The functionality of the developed grading assistant is tested with a basic configuration matching the below criteria.

Develop an application that :

1. Allows a person to register as a user at the route /users/register using the POST HTTP method accepting application/json input with the parameters {username, password, name} (all of which are required) and returning a 201 status code (in the case that a user is successfully created). A username should be unique.

    A. A 400 status code with a Text.HTML MIME type is returned in the case where not all the required parameters are provided.

    B. An attempt to re-register a username that’s already taken should throw an error as in [1.A] above.
       
2. Allows a user to login at /users/login using the POST HTTP method accepting  application/json input bearing the parameters {username,password} . This should

  a).  (on a successful login attempt) give a 200  HTTP status code response as application/json in the format {token} where ‘token’ is a JWT access Token to be used for authentication and authorization in the subsequent requests.  

 b). on a failed login attempt (e.g in the case of invalid credentials provided or where not all required fields are provided for authentication) return a 401 HTTP status code with a response message of your choice.

3.  Using the access token returned as {token} in (2) above added to the HTTP ‘authorization’ header as a ‘Bearer ’ token facilitates:

    A. Fetching a list of services from the /services  application route using the HTTP GET method that: 
       
        I. Where a user access token is valid, returns a 200 HTTP status code with a response as an array/List  in application/json format bearing the various records as objects bearing the parameters 
               {  
                   service_id as integer, 
                   service_name as string, 
                   service_fee as integer,
                   service_code as string, 
                   service_added as datetime, 
                   service_active as boolean
               }
           
        II. Denies access to requests made using an invalid, missing or expired access token and returns a 401 HTTP status code with a non-[Array/List] body response.
           
    B. The creation of a service record at the /services application route using the HTTP POST  method that: 

        I. Inserts a database record for a service  given the data below as application/json
{           
	service_name as string, 
                   service_fee as integer,
                   service_code as string, 
                   service_active as boolean
           }
           while facilitating the generation of the service_id and service_added fields during storage and returns a 201 HTTP status code.
           
        II. Returns a 400  HTTP status code in case an invalid addition request is attempted (e.g missing parameters or an attempted duplicate record) with a non-[Array/List] body response of choice.

        III. Denies access to requests made using an invalid, missing or expired access token and returns a 401 HTTP status code with a non-[Array/List] body response.

	
           
    C. The editing of a service record at the /services/{service_id} application route using the HTTP PUT method (where the {service_id}  in the route above stands for the service_id  added/generated at [B.I] above and shown in the  service structure/format specification in [A.I] above ) that:

        I. Updates a service object by accepting the same parameters shown in [B.I] above as application/json and returns a 200 status code where an object has been successfully updated
        II. Returns a 404 HTTP status code where a given {service_id} is not found in the given records. The return body should not be an Array/List type.
           
        III. Denies access to requests made using an invalid, missing or expired access token and returns a 401 HTTP status code with a non-[Array/List] body response.
           
    D. The deletion of a service record at the /services/{service_id} application route via the HTTP DELETE method (where {service_id}  is as described in [C] above) that:
       
        I. Removes a service object matching the given {service_id} and returns a 200 HTTP status code upon completion.
        II. Returns a 404 status code where a status code that is not in the database/records is specified as the {service_id} .
        III. Denies access to requests made using an invalid, missing or expired access token and returns a 401 HTTP status code with a non-[Array/List] body response.


   

Note: The non-[Array/List] return specification for the above demonstration (where specific response status codes are required) is given to ensure that the application being tested does not merely alter the status code and then display the data to the user. It is not to be confused as a requirement by the grading tool.','2020-10-23 20:45:31.8333333 +00:00',NULL);

