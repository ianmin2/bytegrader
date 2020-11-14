<?php

include __DIR__."/workers/grading/index.php";


class Sampler
{

  public $c;

  public $submisison_instance;
  //  = '{
  //   "attempt_id": 2,
  //   "attempt_name": "Ian Kamau",
  //   "attempt_student_identifier": "skamia1321",
  //   "attempt_main_path": "https://pi1.bixbyte.io",
  //   "attempt_submission_time": "Oct 23 2020  9:26PM",
  //   "attempt_grading_time": null,
  //   "attempt_grade_breakdown": null,
  //   "attempt_grade_complete": "0",
  //   "attempt_assignment": "10004",
  //   "created_at": "2020-10-23T21:26:39.583",
  //   "updated_at": null
  // }';
  
  public $grading_rules;
  //  = '{
  //   "chaining_id": 6,
  //   "chaining_assignment": 10004,
  //   "chaining_depends_on": null,
  //   "chaining_parent": null,
  //   "chaining_type": "explicit",
  //   "chaining_rules": "[{\"rule_id\":\"20006\",\"rule_method\":\"POST\",\"rule_path\":\"\\/users\\/register\",\"rule_name\":\"Allows User registration\",\"rule_description\":\"Allows a person to register as a user \",\"rule_assignment\":\"10004\",\"assignment_name\":\"A sample real world Assignment\",\"assignment_owner\":\"1\",\"assignment_owner_id\":\"1\",\"assignment_owner_name\":\"Ian Kamau\",\"rule_assignment_owner_email\":\"ianmin2@live.com\",\"rule_expected_status_code\":\"200\",\"rule_expected_data_type\":\"application\\/json\",\"rule_expected_data\":null,\"rule_headers\":\"[{\\\"key\\\":\\\"Content-Type\\\",\\\"value\\\":\\\"application\\\\\\/json\\\"},{\\\"key\\\":\\\"Content-Type\\\",\\\"value\\\":\\\"application\\\\\\/json\\\"},{\\\"key\\\":\\\"Content-Type\\\",\\\"value\\\":\\\"application\\\\\\/json\\\"}]\",\"rule_parameters\":\"[{\\\"key\\\":\\\"username\\\",\\\"value\\\":\\\"ianmin22\\\"},{\\\"key\\\":\\\"password\\\",\\\"value\\\":\\\"ianmin22\\\"},{\\\"key\\\":\\\"name\\\",\\\"value\\\":\\\"Ian Kamau\\\"},{\\\"key\\\":\\\"username\\\",\\\"value\\\":\\\"ianmin22\\\"},{\\\"key\\\":\\\"password\\\",\\\"value\\\":\\\"ianmin22\\\"},{\\\"key\\\":\\\"name\\\",\\\"value\\\":\\\"Ian Kamau\\\"},{\\\"key\\\":\\\"username\\\",\\\"value\\\":\\\"ianmin22\\\"},{\\\"key\\\":\\\"password\\\",\\\"value\\\":\\\"ianmin22\\\"},{\\\"key\\\":\\\"name\\\",\\\"value\\\":\\\"Ian Kamau\\\"}]\",\"rule_grading\":\"{\\\"verb\\\":{\\\"weight\\\":100,\\\"match\\\":100,\\\"no_match\\\":0,\\\"matches\\\":[]},\\\"path\\\":{\\\"weight\\\":100,\\\"match\\\":100,\\\"no_match\\\":0,\\\"matches\\\":[]},\\\"status_code\\\":{\\\"weight\\\":100,\\\"match\\\":100,\\\"no_match\\\":0,\\\"matches\\\":[]},\\\"mime_type\\\":{\\\"weight\\\":100,\\\"match\\\":100,\\\"no_match\\\":0,\\\"matches\\\":[]}}\",\"created_at\":\"2020-10-23 20:50:30.5166667 +00:00\",\"updated_at\":\"2020-10-23 21:24:27.1100000 +00:00\",\"parent_rules\":[]},{\"rule_id\":\"20007\",\"rule_method\":\"POST\",\"rule_path\":\"\\/users\\/login\",\"rule_name\":\"Allow user login\",\"rule_description\":\"Allow a user to login with a username and password\",\"rule_assignment\":\"10004\",\"assignment_name\":\"A sample real world Assignment\",\"assignment_owner\":\"1\",\"assignment_owner_id\":\"1\",\"assignment_owner_name\":\"Ian Kamau\",\"rule_assignment_owner_email\":\"ianmin2@live.com\",\"rule_expected_status_code\":\"200\",\"rule_expected_data_type\":\"application\\/json\",\"rule_expected_data\":\"{token}\",\"rule_headers\":\"[{\\\"key\\\":\\\"Content-Type\\\",\\\"value\\\":\\\"application\\\\\\/json\\\"}]\",\"rule_parameters\":\"[{\\\"key\\\":\\\"username\\\",\\\"value\\\":\\\"ianmin22\\\"},{\\\"key\\\":\\\"password\\\",\\\"value\\\":\\\"ianmin22\\\"}]\",\"rule_grading\":\"{\\\"verb\\\":{\\\"weight\\\":100,\\\"match\\\":100,\\\"no_match\\\":0,\\\"matches\\\":[]},\\\"path\\\":{\\\"weight\\\":100,\\\"match\\\":100,\\\"no_match\\\":0,\\\"matches\\\":[]},\\\"status_code\\\":{\\\"weight\\\":100,\\\"match\\\":100,\\\"no_match\\\":0,\\\"matches\\\":[]},\\\"mime_type\\\":{\\\"weight\\\":100,\\\"match\\\":100,\\\"no_match\\\":0,\\\"matches\\\":[]}}\",\"created_at\":\"2020-10-23 20:55:21.6666667 +00:00\",\"updated_at\":\"2020-10-23 20:57:39.2333333 +00:00\",\"parent_rules\":[]},{\"rule_id\":\"20008\",\"rule_method\":\"GET\",\"rule_path\":\"\\/services\",\"rule_name\":\"Allows fetching of services\",\"rule_description\":\"Facilitates the fetching of available services from a protected route\",\"rule_assignment\":\"10004\",\"assignment_name\":\"A sample real world Assignment\",\"assignment_owner\":\"1\",\"assignment_owner_id\":\"1\",\"assignment_owner_name\":\"Ian Kamau\",\"rule_assignment_owner_email\":\"ianmin2@live.com\",\"rule_expected_status_code\":\"200\",\"rule_expected_data_type\":\"application\\/json\",\"rule_expected_data\":\"[{service_id,service_name,service_fee,service_code,service_added,service_active}]\",\"rule_headers\":null,\"rule_parameters\":null,\"rule_grading\":\"{\\\"verb\\\":{\\\"weight\\\":100,\\\"match\\\":100,\\\"no_match\\\":0,\\\"matches\\\":[]},\\\"path\\\":{\\\"weight\\\":100,\\\"match\\\":100,\\\"no_match\\\":0,\\\"matches\\\":[]},\\\"status_code\\\":{\\\"weight\\\":100,\\\"match\\\":100,\\\"no_match\\\":0,\\\"matches\\\":[]},\\\"mime_type\\\":{\\\"weight\\\":100,\\\"match\\\":100,\\\"no_match\\\":0,\\\"matches\\\":[]}}\",\"created_at\":\"2020-10-23 21:02:25.5100000 +00:00\",\"updated_at\":null,\"parent_rules\":[20007]},{\"rule_id\":\"20009\",\"rule_method\":\"POST\",\"rule_path\":\"\\/services\",\"rule_name\":\"Allows creation of a service record\",\"rule_description\":\"Facilitates the creation of a service record\",\"rule_assignment\":\"10004\",\"assignment_name\":\"A sample real world Assignment\",\"assignment_owner\":\"1\",\"assignment_owner_id\":\"1\",\"assignment_owner_name\":\"Ian Kamau\",\"rule_assignment_owner_email\":\"ianmin2@live.com\",\"rule_expected_status_code\":\"200\",\"rule_expected_data_type\":\"text\\/html\",\"rule_expected_data\":null,\"rule_headers\":null,\"rule_parameters\":\"[{\\\"key\\\":\\\"service_name\\\",\\\"value\\\":\\\"realService\\\"},{\\\"key\\\":\\\"service_fee\\\",\\\"value\\\":\\\"100\\\"},{\\\"key\\\":\\\"service_code\\\",\\\"value\\\":\\\"real one\\\"},{\\\"key\\\":\\\"service_active\\\",\\\"value\\\":\\\"1\\\"}]\",\"rule_grading\":\"{\\\"verb\\\":{\\\"weight\\\":100,\\\"match\\\":100,\\\"no_match\\\":0,\\\"matches\\\":[]},\\\"path\\\":{\\\"weight\\\":100,\\\"match\\\":100,\\\"no_match\\\":0,\\\"matches\\\":[]},\\\"status_code\\\":{\\\"weight\\\":100,\\\"match\\\":100,\\\"no_match\\\":0,\\\"matches\\\":[]},\\\"mime_type\\\":{\\\"weight\\\":100,\\\"match\\\":100,\\\"no_match\\\":0,\\\"matches\\\":[]}}\",\"created_at\":\"2020-10-23 21:05:45.0466667 +00:00\",\"updated_at\":null,\"parent_rules\":[20007]},{\"rule_id\":\"20011\",\"rule_method\":\"PUT\",\"rule_path\":\"\\/services\\/{service_id}\",\"rule_name\":\"Allows updating of a service record\",\"rule_description\":\"Facilitates the editing of a specific service record\",\"rule_assignment\":\"10004\",\"assignment_name\":\"A sample real world Assignment\",\"assignment_owner\":\"1\",\"assignment_owner_id\":\"1\",\"assignment_owner_name\":\"Ian Kamau\",\"rule_assignment_owner_email\":\"ianmin2@live.com\",\"rule_expected_status_code\":\"200\",\"rule_expected_data_type\":\"json\",\"rule_expected_data\":null,\"rule_headers\":\"[{\\\"key\\\":\\\"Content-Type\\\",\\\"value\\\":\\\"application\\\\\\/json\\\"}]\",\"rule_parameters\":\"[{\\\"key\\\":\\\"service_name\\\",\\\"value\\\":\\\"realService\\\"},{\\\"key\\\":\\\"service_fee\\\",\\\"value\\\":\\\"100\\\"},{\\\"key\\\":\\\"service_code\\\",\\\"value\\\":\\\"realOne\\\"},{\\\"key\\\":\\\"service_active\\\",\\\"value\\\":\\\"1\\\"}]\",\"rule_grading\":\"{\\\"verb\\\":{\\\"weight\\\":100,\\\"match\\\":100,\\\"no_match\\\":0,\\\"matches\\\":[]},\\\"path\\\":{\\\"weight\\\":100,\\\"match\\\":100,\\\"no_match\\\":0,\\\"matches\\\":[]},\\\"status_code\\\":{\\\"weight\\\":100,\\\"match\\\":100,\\\"no_match\\\":0,\\\"matches\\\":[]},\\\"mime_type\\\":{\\\"weight\\\":100,\\\"match\\\":100,\\\"no_match\\\":100,\\\"matches\\\":[]}}\",\"created_at\":\"2020-10-23 21:17:41.2466667 +00:00\",\"updated_at\":null,\"parent_rules\":[20007]},{\"rule_id\":\"20010\",\"rule_method\":\"GET\",\"rule_path\":\"\\/services\\/{service_id}\",\"rule_name\":\"Facilitates Single service fetching\",\"rule_description\":\"Facilitates the fetching of a specific service by its service_id\",\"rule_assignment\":\"10004\",\"assignment_name\":\"A sample real world Assignment\",\"assignment_owner\":\"1\",\"assignment_owner_id\":\"1\",\"assignment_owner_name\":\"Ian Kamau\",\"rule_assignment_owner_email\":\"ianmin2@live.com\",\"rule_expected_status_code\":\"200\",\"rule_expected_data_type\":\"application\\/json\",\"rule_expected_data\":\"{service_id,service_name,service_fee,service_code,service_added,service_active}\",\"rule_headers\":null,\"rule_parameters\":null,\"rule_grading\":\"{\\\"verb\\\":{\\\"weight\\\":100,\\\"match\\\":100,\\\"no_match\\\":0,\\\"matches\\\":[]},\\\"path\\\":{\\\"weight\\\":100,\\\"match\\\":100,\\\"no_match\\\":0,\\\"matches\\\":[]},\\\"status_code\\\":{\\\"weight\\\":100,\\\"match\\\":100,\\\"no_match\\\":0,\\\"matches\\\":[]},\\\"mime_type\\\":{\\\"weight\\\":100,\\\"match\\\":100,\\\"no_match\\\":0,\\\"matches\\\":[]}}\",\"created_at\":\"2020-10-23 21:09:37.7000000 +00:00\",\"updated_at\":null,\"parent_rules\":[20007]},{\"rule_id\":\"20012\",\"rule_method\":\"DELETE\",\"rule_path\":\"\\/services\\/{service_id}\",\"rule_name\":\"Allow deletion of a specific service\",\"rule_description\":\"Facilitates the deletion of a specific service record\",\"rule_assignment\":\"10004\",\"assignment_name\":\"A sample real world Assignment\",\"assignment_owner\":\"1\",\"assignment_owner_id\":\"1\",\"assignment_owner_name\":\"Ian Kamau\",\"rule_assignment_owner_email\":\"ianmin2@live.com\",\"rule_expected_status_code\":\"200\",\"rule_expected_data_type\":\"application\\/json\",\"rule_expected_data\":null,\"rule_headers\":null,\"rule_parameters\":null,\"rule_grading\":\"{\\\"verb\\\":{\\\"weight\\\":100,\\\"match\\\":100,\\\"no_match\\\":0,\\\"matches\\\":[]},\\\"path\\\":{\\\"weight\\\":100,\\\"match\\\":100,\\\"no_match\\\":0,\\\"matches\\\":[]},\\\"status_code\\\":{\\\"weight\\\":100,\\\"match\\\":100,\\\"no_match\\\":0,\\\"matches\\\":[]},\\\"mime_type\\\":{\\\"weight\\\":100,\\\"match\\\":100,\\\"no_match\\\":100,\\\"matches\\\":[]}}\",\"created_at\":\"2020-10-23 21:20:07.6000000 +00:00\",\"updated_at\":null,\"parent_rules\":[20007]}]",
  //   "created_at": "2020-11-07T10:11:16.247",
  //   "updated_at": null
  // }';
  
  public function __construct($connection) {
   $this->c = $connection;

    $this->submisison_instance = $this->c->printQueryResults("SELECT * FROM attempts WHERE attempt_id='2';")[0];
    $this->grading_rules = $this->c->printQueryResults("SELECT * FROM chainings WHERE chaining_id='6';")[0];

  }

  public function mockGrading()
  {
    new GradingWorker( $this->grading_rules["chaining_rules"], $this->submisison_instance, [1]);
  } 

}
