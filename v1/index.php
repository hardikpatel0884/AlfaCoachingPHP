<?php

require_once '../include/DbHandler.php';
require_once '../include/PassHash.php';
require '.././libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

// User id from db - Global Variable
$user_id = NULL;

/**
 * Adding Middle Layer to authenticate every request
 * Checking if the request has valid api key in the 'Authorization' header
 */
function authenticate(\Slim\Route $route) {
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();

    // Verifying Authorization Header
    if (isset($headers['Authorization'])) {
        $db = new DbHandler();

        // get the api key
        $api_key = $headers['Authorization'];
        // validating api key
        if (!$db->isValidApiKey($api_key)) {
            // api key is not present in users table
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid Api key";
            echoRespnse(200, $response);
            $app->stop();
        } else {
            global $user_id;
            // get user primary key id
            $user_id = $db->getUserId($api_key);
        }
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["message"] = "Api key is misssing";
        echoRespnse(200, $response);
        $app->stop();
    }
}

/**
 * ----------- METHODS WITHOUT AUTHENTICATION ---------------------------------
 */
/**
 * Tutor Registration
 * url - /register
 * method - POST
 * params - name, email, password
 */
$app->post('/tutor', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('phone_number', 'name', 'password','gender','qualification','experience'));

            $response = array();

            // reading post params
            $name = $app->request->post('name');
            $phone_number = $app->request->post('phone_number');
            $password = $app->request->post('password');
            $gender=$app->request->post('gender');
            $qualification=$app->request->post('qualification');
            $experience=$app->request->post('experience');
            $imagename=$phone_number.".jpg";

            // upload image
            if (!isset($_FILES['image'])) {
		        echoRespnse(201, "no file found");
		    }else{
		    	move_uploaded_file($_FILES["image"]["tmp_name"], "../images/tutor/".$imagename);
		    }

		    $db = new DbHandler();
            $res = $db->createTutor($phone_number, $password,$name,$gender,$qualification,$experience,$imagename);

            if ($res == USER_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "You are successfully registered";
            } else if ($res == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registereing";
            } else if ($res == USER_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, this user already existed";
            }
            // echo json response
            echoRespnse(201, $response);
        });

$app->post('/parent', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('phone_number', 'name', 'password','gender'));

            $response = array();

            // reading post params
            $name = $app->request->post('name');
            $phone_number = $app->request->post('phone_number');
            $password = $app->request->post('password');
            $gender=$app->request->post('gender');

            // validating email address
            //validateEmail($email);

            $db = new DbHandler();
            //$res = $db->createUser($name, $email, $password);
            $res = $db->createParent($phone_number, $password,$name,$gender);

            if ($res == USER_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "You are successfully registered";
            } else if ($res == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registereing";
            } else if ($res == USER_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, this user already existed";
            }
            // echo json response
            echoRespnse(201, $response);
        });

$app->post('/student', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('parent', 'name', 'standerd','gender','password','school'));

            $db = new DbHandler();
            $studentID=$db->getStudentId()+1;
            if ($studentID < 9) {
               $studentID = date("Y").'00'.$studentID;
            } else if (Number(result[0].student) < 99) {
                $studentID = date("Y").'0'.$studentID;
            } else {
                $studentID = date("Y").$studentID;
            }
            $response = array();

            // reading post params
            $name = $app->request->post('name');
            $parent = $app->request->post('parent');
            $password = $app->request->post('password');
            $gender=$app->request->post('gender');
            $standerd=$app->request->post('standerd');
            $school=$app->request->post('school');
            $imagename=$studentID.".jpg";

            // upload image
            if (!isset($_FILES['image'])) {
		        echoRespnse(201, "no file found");
		    }else{
		    	move_uploaded_file($_FILES["image"]["tmp_name"], "../images/student/".$imagename);
		    }
		    
            $res = $db->createStudent($studentID,$parent, $password,$name,$gender,$standerd,$school);

            if ($res == USER_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "You are successfully registered";
            } else if ($res == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registereing";
            } else if ($res == USER_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, this user already existed";
            }
            // echo json response
            echoRespnse(201, $response);
        });

/**
 * User Login
 * url - /login
 * method - POST
 * params - email, password
 */
$app->post('/login', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('user_name', 'password'));

            // reading post params
            $user_name = $app->request()->post('user_name');
            $password = $app->request()->post('password');
            $response = array();

            $db = new DbHandler();
            // check for correct email and password
            if ($db->checkLogin($user_name, $password)) {


                // get the user by email
                $user = $db->getUserById($user_name);

                if ($user != NULL) {
                    $response["error"] = false;
                    $response['user'] = $user;
                } else {
                    // unknown error occurred
                    $response['error'] = true;
                    $response['message'] = "An error occurred. Please try again";
                }
            } else {
                // user credentials are wrong
                $response['error'] = true;
                $response['message'] = 'Login failed. Incorrect credentials';
            }

            echoRespnse(200, $response);
        });

/*
 * ------------------------ METHODS WITH AUTHENTICATION ------------------------
 */


$app->get('/users/:userType','authenticate', function($userType) {
            global $user_id;
            $response = array();
            $db = new DbHandler();
            // fetching all user tasks
            $response["error"] = false;
            if($userType=="tutor"){
            	$result = $db->getAllUser(TRUE);
            	while ($users = $result->fetch_assoc()) {
	                $tmp = array();
	                $tmp["phone_number"] = $users["phone_number"];
	                $tmp["name"] = $users["name"];
	                $tmp["gender"] = $users["gender"];
	                $tmp["qualification"] = $users["qualification"];
	                $tmp["image"] = $users["image"];
	                $tmp["experience"] = $users["experience"];
	                if($users["is_active"]==1){
	                	$tmp["is_active"]=TRUE;
	                }else{
	                	$tmp["is_active"]=FALSE;
	                }
	                $response["users"] = array();
	                array_push($response["users"], $tmp);
            	}
            }elseif($userType=="parent"){
            	$result = $db->getAllUser(0);
            	while ($users = $result->fetch_assoc()) {
	                $tmp = array();
	                $tmp["phone_number"] = $users["phone_number"];
	                $tmp["name"] = $users["name"];
	                $tmp["gender"] = $users["gender"];
	                $tmp["qualification"] = $users["qualification"];
	                $tmp["image"] = $users["image"];
	                $tmp["experience"] = $users["experience"];
	                if($users["is_active"]==1){
	                	$tmp["is_active"]=TRUE;
	                }else{
	                	$tmp["is_active"]=FALSE;
	                }
	                $response["users"] = array();
	                array_push($response["users"], $tmp);
            	}
            }elseif($userType=="student"){
            	$result = $db->getAllStudent();
            	while ($student = $result->fetch_assoc()) {
	                $tmp = array();
	                $tmp["student_id"] = $student["student_id"];
	                $tmp["parent_name"] = $student["parent_name"];
	                $tmp["standard"] = $student["standerd"];
	                $tmp["name"] = $student["name"];
	                $tmp["gender"] = $student["gender"];
	                $tmp["image"] = $student["image"];
	                $tmp["school"] = $student["school"];
	                if($student["is_active"]==1){
	                	$tmp["is_active"]=TRUE;
	                }else{
	                	$tmp["is_active"]=FALSE;
	                }
	                $response["users"] = array();
	                array_push($response["users"], $tmp);
            	}
            }else{
            	$response["error"] = true;
            	$response["message"] = "invalid user type";
            }

            echoRespnse(200, $response);
        });

/** Register standerd */
$app->post('/standard','authenticate', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('standard'));
            $response = array();
            // reading post params
            $standard = $app->request->post('standard');
		    $db = new DbHandler();
            $res = $db->createStandard($standard);

            if ($res == USER_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "Standard added successfully";
            } else if ($res == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registereing standard";
            }
            // echo json response
            echoRespnse(201, $response);
        });

/**
 * Listing all standards         
 */
$app->get('/standard', 'authenticate', function() {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            // fetching all user tasks
            $result = $db->getAllStandard();

            $response["error"] = false;
            $response["standard"] = array();

            // looping through result and preparing tasks array
            while ($standard = $result->fetch_assoc()) {
                $tmp = array();
                $tmp["standard_id"] = $standard["standard_id"];
                $tmp["standard"] = $standard["standard"];
                if($standard["is_deleted"]==0){
                	$tmp["is_deleted"]=FALSE;
                }else{
                	$tmp["is_deleted"]=TRUE;
                }
                array_push($response["standard"], $tmp);
            }

            echoRespnse(200, $response);
        });



/** Register Subject */
$app->post('/subject','authenticate', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('standard','subject_name','tutor','fees'));
            $response = array();
            // reading post params
            $standard = $app->request->post('standard');
            $subject_name = $app->request->post('subject_name');
            $tutor = $app->request->post('tutor');
            $fees = $app->request->post('fees');
		    $db = new DbHandler();
            $res = $db->createSubject($standard,$subject_name,$tutor,$fees);

            if ($res == USER_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "Subject added successfully";
            } else if ($res == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registereing subject";
            } 
            // echo json response
            echoRespnse(201, $response);
        });

/** Register student subject */
$app->post('/studentsubject','authenticate', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('student','subject'));
            $response = array();
            // reading post params
            $student = $app->request->post('student');
            $subject = $app->request->post('subject');
		    $db = new DbHandler();
            $res = $db->createStudentSubject($student,$subject);

            if ($res == USER_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "Student Subject added successfully";
            } else if ($res == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registereing subject";
            } 
            // echo json response
            echoRespnse(201, $response);
        });

/**
 * Listing all standards         
 */
$app->get('/subject', 'authenticate', function() {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            // fetching all user tasks
            $result = $db->getAllSubject(NULL);

            $response["error"] = false;
            $response["subject"] = array();

            // looping through result and preparing tasks array
            while ($subject = $result->fetch_assoc()) {
                $tmp = array();
                $tmp["subject_id"] = $subject["subject_id"];
                $tmp["standard"] = $subject["standard"];
                $tmp["subject_name"] = $subject["subject_name"];
                $tmp["tutor"] = $subject["tutor"];
                $tmp["fees_amount"] = $subject["fees_amount"];
                /*if($standard["is_deleted"]==0){
                	$tmp["is_deleted"]=FALSE;
                }else{
                	$tmp["is_deleted"]=TRUE;
                }*/
                array_push($response["subject"], $tmp);
            }

            echoRespnse(200, $response);
        });

/**
 * Listing all subject from standard  
 */
$app->get('/subject/:standard', 'authenticate', function($standard) {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            // fetching all user tasks
            $result = $db->getAllSubject($standard);

            // looping through result and preparing tasks array
            if($result->num_rows>0){
            	$response["error"] = false;
            	$response["subject"] = array();
            	while ($subject = $result->fetch_assoc()) {
	                $tmp = array();
	                $tmp["subject_id"] = $subject["subject_id"];
	                $tmp["standard"] = $subject["standard"];
	                $tmp["subject_name"] = $subject["subject_name"];
	                $tmp["tutor"] = $subject["tutor"];
	                $tmp["fees_amount"] = $subject["fees_amount"];
	                /*if($standard["is_deleted"]==0){
	                	$tmp["is_deleted"]=FALSE;
	                }else{
	                	$tmp["is_deleted"]=TRUE;
	                }*/
	                array_push($response["subject"], $tmp);
	            }
            }else{
            	$response["error"] = true;
            	$response["message"] = "No result found";
            }

            echoRespnse(200, $response);
        });

/**
 * Listing all subject from standard  
 */
$app->get('/student/standard/:standard', 'authenticate', function($standard) {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            // fetching all user tasks
            $result = $db->getStudentByStandard($standard);

            $response["error"] = false;

            // looping through result and preparing tasks array
            if($result->num_rows>0){
            	$response["students"] = array();
	            while ($subject = $result->fetch_assoc()) {
	                $tmp = array();
	                $tmp["student_id"] = $subject["student_id"];
	                $tmp["name"] = $subject["name"];
	                $tmp["school"] = $subject["school"];
	                $tmp["gender"] = $subject["gender"];
	                array_push($response["students"], $tmp);
	            }
	        }else{
	        	$response["error"] = true;
            	$response["message"] = "No result found";
	        }
            echoRespnse(200, $response);
        });

/**
 * Listing all tasks of particual user
 * method GET
 * url /tasks          
 */
$app->get('/tasks', 'authenticate', function() {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            // fetching all user tasks
            $result = $db->getAllUserTasks($user_id);

            $response["error"] = false;
            $response["tasks"] = array();

            // looping through result and preparing tasks array
            while ($task = $result->fetch_assoc()) {
                $tmp = array();
                $tmp["id"] = $task["id"];
                $tmp["task"] = $task["task"];
                $tmp["status"] = $task["status"];
                $tmp["createdAt"] = $task["created_at"];
                array_push($response["tasks"], $tmp);
            }

            echoRespnse(200, $response);
        });

/**
 * Listing single task of particual user
 * method GET
 * url /tasks/:id
 * Will return 404 if the task doesn't belongs to user
 */
$app->get('/tasks/:id', 'authenticate', function($task_id) {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            // fetch task
            $result = $db->getTask($task_id, $user_id);

            if ($result != NULL) {
                $response["error"] = false;
                $response["id"] = $result["id"];
                $response["task"] = $result["task"];
                $response["status"] = $result["status"];
                $response["createdAt"] = $result["created_at"];
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "The requested resource doesn't exists";
                echoRespnse(200, $response);
            }
        });

/**
 * Creating new task in db
 * method POST
 * params - name
 * url - /tasks/
 */
$app->post('/tasks', 'authenticate', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('task'));

            $response = array();
            $task = $app->request->post('task');

            global $user_id;
            $db = new DbHandler();

            // creating new task
            $task_id = $db->createTask($user_id, $task);

            if ($task_id != NULL) {
                $response["error"] = false;
                $response["message"] = "Task created successfully";
                $response["task_id"] = $task_id;
                echoRespnse(201, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to create task. Please try again";
                echoRespnse(200, $response);
            }            
        });

/**
 * Updating existing task
 * method PUT
 * params task, status
 * url - /tasks/:id
 */
$app->put('/tasks/:id', 'authenticate', function($task_id) use($app) {
            // check for required params
            verifyRequiredParams(array('task', 'status'));

            global $user_id;            
            $task = $app->request->put('task');
            $status = $app->request->put('status');

            $db = new DbHandler();
            $response = array();

            // updating task
            $result = $db->updateTask($user_id, $task_id, $task, $status);
            if ($result) {
                // task updated successfully
                $response["error"] = false;
                $response["message"] = "Task updated successfully";
            } else {
                // task failed to update
                $response["error"] = true;
                $response["message"] = "Task failed to update. Please try again!";
            }
            echoRespnse(200, $response);
        });

/**
 * Deleting task. Users can delete only their tasks
 * method DELETE
 * url /tasks
 */
$app->delete('/tasks/:id', 'authenticate', function($task_id) use($app) {
            global $user_id;

            $db = new DbHandler();
            $response = array();
            $result = $db->deleteTask($user_id, $task_id);
            if ($result) {
                // task deleted successfully
                $response["error"] = false;
                $response["message"] = "Task deleted succesfully";
            } else {
                // task failed to delete
                $response["error"] = true;
                $response["message"] = "Task failed to delete. Please try again!";
            }
            echoRespnse(200, $response);
        });

/**
 * Verifying required params posted or not
 */
function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(200, $response);
        $app->stop();
    }
}

/**
 * Validating email address
 */
function validateEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"] = true;
        $response["message"] = 'Email address is not valid';
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoRespnse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType('application/json');

    echo json_encode($response);
}

$app->run();
?>