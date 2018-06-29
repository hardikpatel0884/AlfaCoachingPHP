<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Hardik Patel
 * @link URL Tutorial link
 */
class DbHandler {

    private $conn;

    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    /* ------------- `users` table method ------------------ */

    /**
     * Creating new tutor
     * @param String $name User full name
     * @param String $email User login email id
     * @param String $password User login password
     */
    public function createTutor($phone, $password,$name,$gender,$qualification,$experience,$imagename) {
        require_once 'PassHash.php';

        // First check if user already existed in db
        if (!$this->isUserExists($phone)) {
            // Generating password hash
            $password_hash = PassHash::hash($password);

            // Generating API key
            $api_key = $this->generateApiKey();

            //$image=$phone."jpg";

            // insert query
            $stmt = $this->conn->prepare("INSERT INTO user (phone_number,password,name,gender,qualification,experience,api_key,image,is_tutor) values(?, ?, ?, ?, ?,?,?,?,1)");
            $stmt->bind_param("ssssssss", $phone, $password_hash, $name,$gender,$qualification,$experience, $api_key,$imagename);

            $result = $stmt->execute();

            $stmt->close();

            // Check for successful insertion
            if ($result) {
                // User successfully inserted
                return USER_CREATED_SUCCESSFULLY;
            } else {
                // Failed to create user
                return USER_CREATE_FAILED;
            }
        } else {
            // User with same email already existed in the db
            return USER_ALREADY_EXISTED;
        }
    }

    /**
     * Creating new parent
     * @param String $name User full name
     * @param String $email User login email id
     * @param String $password User login password
     */
    public function createParent($phone, $password,$name,$gender) {
        require_once 'PassHash.php';

        // First check if user already existed in db
        if (!$this->isUserExists($phone)) {
            // Generating password hash
            $password_hash = PassHash::hash($password);

            // Generating API key
            $api_key = $this->generateApiKey();

            // insert query
            $stmt = $this->conn->prepare("INSERT INTO user (phone_number,password,name,gender,api_key,is_tutor) values(?, ?, ?, ?, ?,0)");
            $stmt->bind_param("sssss", $phone, $password_hash, $name,$gender, $api_key);

            $result = $stmt->execute();

            $stmt->close();

            // Check for successful insertion
            if ($result) {
                // User successfully inserted
                return USER_CREATED_SUCCESSFULLY;
            } else {
                // Failed to create user
                return USER_CREATE_FAILED;
            }
        } else {
            // User with same email already existed in the db
            return USER_ALREADY_EXISTED;
        }
    }

     /**
     * Creating new student
     * @param String $name User full name
     * @param String $email User login email id
     * @param String $password User login password
     */
    public function createStudent($student,$parent, $password,$name,$gender,$standerd,$school) {
        require_once 'PassHash.php';

        // First check if user already existed in db
        if (!$this->isUserExists($student)) {
            // Generating password hash
            $password_hash = PassHash::hash($password);

            // Generating API key
            $api_key = $this->generateApiKey();

            $image=$student.".jpg";

            // insert query
            $stmt = $this->conn->prepare("INSERT INTO student (student_id,parents,password,name,gender,school,api_key,image,standard) values(?,?, ?, ?, ?, ?,?,?,?)");
            // die($this->conn->error);
            $stmt->bind_param("ssssssssi", $student,$parent, $password_hash, $name,$gender,$school, $api_key,$image,$standerd);

            $result = $stmt->execute();

            $stmt->close();

            // Check for successful insertion
            if ($result) {
                // User successfully inserted
                return USER_CREATED_SUCCESSFULLY;
            } else {
                // Failed to create user
                return USER_CREATE_FAILED;
            }
        } else {
            // User with same email already existed in the db
            return USER_ALREADY_EXISTED;
        }
    }

    /**
     * Creating new standerd
     * @param String $name User full name
     */
    public function createStandard($standard) {
        // insert query
        $stmt = $this->conn->prepare("INSERT INTO standard (standard) values(?)");
        // die($this->conn->error);
        $stmt->bind_param("s",$standard);

        $result = $stmt->execute();

        $stmt->close();

        // Check for successful insertion
        if ($result) {
            // User successfully inserted
            return USER_CREATED_SUCCESSFULLY;
        } else {
            // Failed to create user
            return USER_CREATE_FAILED;
        }
    }

    /**
     * Creating new subject
     * @param String $name User full name
     */
    public function createSubject($standard,$subject_name,$tutor,$fees) {
        // insert query
        $stmt = $this->conn->prepare("INSERT INTO subject (subject_name,teach_by,fees_amount,standard) values(?, ?, ?, ?)");
        // die($this->conn->error);
        $stmt->bind_param("sssi",$subject_name,$tutor,$fees,$standard);
        $result = $stmt->execute();
        $stmt->close();

        // Check for successful insertion
        if ($result) {
            // User successfully inserted
            return USER_CREATED_SUCCESSFULLY;
        } else {
            // Failed to create user
            return USER_CREATE_FAILED;
        }
    }

    /**
     * Creating new StudentSubject
     * @param String $name User full name
     */
    public function createStudentSubject($student,$subject) {
        // insert query
        $stmt = $this->conn->prepare("INSERT INTO studentSubject (student_id,subject_id) values(?, ?)");
        $stmt->bind_param("si",$student,$subject);
        $result = $stmt->execute();
        $stmt->close();

        // Check for successful insertion
        if ($result) {
            // User successfully inserted
            return USER_CREATED_SUCCESSFULLY;
        } else {
            // Failed to create user
            return USER_CREATE_FAILED;
        }
    }    

    /**
     * Checking user login
     * @param String $email User login email id
     * @param String $password User login password
     * @return boolean User login status success/fail
     */
    public function checkLogin($user_name, $password) {
        // fetching user by email
        $stmt = $this->conn->prepare("SELECT password FROM user WHERE phone_number = ? and is_active = TRUE");
        $stmt->bind_param("s", $user_name);
        $stmt->execute();
        $stmt->bind_result($password_hash);
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            // Found user with the email
            // Now verify the password
            $stmt->fetch();
            $stmt->close();
            if (PassHash::check_password($password_hash, $password)) {
                // User password is correct
                return TRUE;
            } else {
                // user password is incorrect
                return FALSE;
            }
        } else {
            $stmt->close();

            // use not existed try to find student
            $stmt = $this->conn->prepare("SELECT password FROM student WHERE student_id = ? and is_active = TRUE");
            $stmt->bind_param("s", $user_name);
            $stmt->execute();
            $stmt->bind_result($password_hash);
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                // Found user with the email
                // Now verify the password
                $stmt->fetch();
                $stmt->close();
                if (PassHash::check_password($password_hash, $password)) {
                    // User password is correct
                    return TRUE;
                } else {
                    // user password is incorrect
                    return FALSE;
                }
            } else {
                $stmt->close();

                // user not existed with the email
                return FALSE;
            }   
        }
    }

    /**
     * Checking for duplicate user by email address
     * @param String $email email to check in db
     * @return boolean
     */
    private function isUserExists($phone) {
        $stmt = $this->conn->prepare("SELECT phone_number from user WHERE phone_number = ?");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Checking for duplicate user by email address
     * @param String $email email to check in db
     * @return boolean
     */
    public function getStudentId() {
        $query="SELECT student_id from student where student_id like '".date("Y")."%'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows;
    }

    /**
     * Fetching all user
     */
    public function getAllUser($isTutor) {
        $stmt = $this->conn->prepare("SELECT * FROM user where is_tutor = ".$isTutor);
        $stmt->execute();
        $users = $stmt->get_result();
        $stmt->close();
        return $users;
    }

    /**
     * Fetching all students
     */
    public function getAllStudent() {
        $stmt = $this->conn->prepare("SELECT student.*, user.name as parent_name, standard.standard as standerd FROM student INNER JOIN user ON student.parents=user.phone_number INNER JOIN standard ON student.standard=standard.standard_id");
        $stmt->execute();
        $students = $stmt->get_result();
        $stmt->close();
        if($stmt->num_rows > 0){
            return $students;
        }else{return null;}
    }

    /**
     * Fetching all standard
     */
    public function getAllStandard() {
        $stmt = $this->conn->prepare("SELECT * FROM standard where is_deleted=0");
        $stmt->execute();
        $standard = $stmt->get_result();
        $stmt->close();
        return $standard;
    }

    /**
     * Fetching all subject
     */
    public function getAllSubject($standard) {
        if($standard!=null){
            $stmt = $this->conn->prepare("SELECT subject.subject_id, standard.standard as standard, subject.subject_name, user.name as tutor, subject.fees_amount FROM subject INNER JOIN user ON subject.teach_by=user.phone_number INNER JOIN standard ON subject.standard=standard.standard_id WHERE subject.is_deleted=0 and subject.standard=?");
            $stmt->bind_param('i',$standard);
        }else{
            $stmt = $this->conn->prepare("SELECT subject.subject_id, standard.standard as standard, subject.subject_name, user.name as tutor, subject.fees_amount FROM subject INNER JOIN user ON subject.teach_by=user.phone_number INNER JOIN standard ON subject.standard=standard.standard_id WHERE subject.is_deleted=0");
        }
        $stmt->execute();
        $subject = $stmt->get_result();
        $stmt->close();
        return $subject;
    }

    /**
     * Fetching all student by standard
     */
    public function getStudentByStandard($standard) {
        $stmt = $this->conn->prepare("SELECT student.student_id, student.name, student.gender, student.school FROM standard INNER JOIN student ON standard.standard_id=student.standard WHERE standard.standard_id = ?");
        $stmt->bind_param('i',$standard);
        $stmt->execute();
        $student = $stmt->get_result();
        $stmt->close();
        return $student;
    }

    /**
     * Fetching user by email
     * @param String $user_id user/student id
     */
    public function getUserById($user_name) {
        $stmt = $this->conn->prepare("SELECT phone_number, name, gender, qualification, image, experience, api_key, is_tutor FROM user WHERE phone_number = ?");
        $stmt->bind_param("s", $user_name);
        $stmt->execute();
        $stmt->bind_result($phone_number, $name, $gender, $qualification, $image, $experience, $api_key, $is_tutor);
        $stmt->fetch();
        $user = array();
        $user["phone_number"] = $phone_number;
        $user["name"] = $name;
        $user["gender"] = $gender;
        $user["qualification"] = $qualification;
        $user["image"] = $image;
        $user["experience"] = $experience;
        $user["api_key"] = $api_key;
        if($is_tutor==0){
            $user["is_tutor"] = FALSE;    
        }else{
            $user["is_tutor"] = TRUE;
        }
        $stmt->close();
            
        if ($user["phone_number"]!=NULL) {
            // $user = $stmt->get_result()->fetch_assoc();
            return $user;
        } else {
            $stmt = $this->conn->prepare("SELECT student.student_id, user.name as parent_name, standard.standard as standerd, student.name, student.gender, student.image, student.school, student.api_key FROM student INNER JOIN user ON student.parents=user.phone_number INNER JOIN standard ON student.standard=standard.standard_id WHERE student_id = ? ");
            $stmt->bind_param("s", $user_name);
            if ($stmt->execute()) {
                // $user = $stmt->get_result()->fetch_assoc();
                $stmt->bind_result($student_id, $parent_name, $standard, $name, $gender, $image, $school, $api_key);
                $stmt->fetch();
                $user = array();
                $user["student_id"] = $student_id;
                $user["parent_name"] = $parent_name;
                $user["standard"] = $standard;
                $user["name"] = $name;
                $user["gender"] = $gender;
                $user["image"] = $image;
                $user["school"] = $school;
                $user["api_key"] = $api_key;
                $stmt->close();
                return $user;
            } else {
                return NULL;
            }
            return NULL;
        }
    }

    /**
     * Fetching user api key
     * @param String $user_id user id primary key in user table
     */
    public function getApiKeyById($user_id) {
        $stmt = $this->conn->prepare("SELECT api_key FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            // $api_key = $stmt->get_result()->fetch_assoc();
            // TODO
            $stmt->bind_result($api_key);
            $stmt->close();
            return $api_key;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user id by api key
     * @param String $api_key user api key
     */
    public function getUserId($api_key) {
        $stmt = $this->conn->prepare("SELECT phone_number from user WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $stmt->bind_result($user_id);
        $stmt->fetch();
        if($user_id!=NULL){
            return $user_id;
        }else{
            // check for student
            $stmt = $this->conn->prepare("SELECT student_id from student WHERE api_key = ?");
            $stmt->bind_param("s", $api_key);
            $stmt->execute();
            $stmt->bind_result($user_id);
            $stmt->fetch();
            $num_rows = $stmt->num_rows;
            $stmt->close();
            if($user_id!=NULL){
                return $user_id;
            }else{
                return NULL;
            }
        }
    }

    /**
     * Validating user api key
     * If the api key is there in db, it is a valid key
     * @param String $api_key user api key
     * @return boolean
     */
    public function isValidApiKey($api_key) {
        $stmt = $this->conn->prepare("SELECT phone_number from user WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        if($num_rows>0){
            return $num_rows>0;
        }else{
            // check for student
            $stmt = $this->conn->prepare("SELECT student_id from student WHERE api_key = ?");
            $stmt->bind_param("s", $api_key);
            $stmt->execute();
            $stmt->store_result();
            $num_rows = $stmt->num_rows;
            $stmt->close();
            return $num_rows > 0;
        }  
    }

    /**
     * Generating random Unique MD5 String for user Api key
     */
    private function generateApiKey() {
        return md5(uniqid(rand(), true));
    }

    /* ------------- `tasks` table method ------------------ */

    /**
     * Creating new task
     * @param String $user_id user id to whom task belongs to
     * @param String $task task text
     */
    public function createTask($user_id, $task) {
        $stmt = $this->conn->prepare("INSERT INTO tasks(task) VALUES(?)");
        $stmt->bind_param("s", $task);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            // task row created
            // now assign the task to user
            $new_task_id = $this->conn->insert_id;
            $res = $this->createUserTask($user_id, $new_task_id);
            if ($res) {
                // task created successfully
                return $new_task_id;
            } else {
                // task failed to create
                return NULL;
            }
        } else {
            // task failed to create
            return NULL;
        }
    }

    /**
     * Fetching single task
     * @param String $task_id id of the task
     */
    public function getTask($task_id, $user_id) {
        $stmt = $this->conn->prepare("SELECT t.id, t.task, t.status, t.created_at from tasks t, user_tasks ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($id, $task, $status, $created_at);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res["id"] = $id;
            $res["task"] = $task;
            $res["status"] = $status;
            $res["created_at"] = $created_at;
            $stmt->close();
            return $res;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching all user tasks
     * @param String $user_id id of the user
     */
    public function getAllUserTasks($user_id) {
        $stmt = $this->conn->prepare("SELECT t.* FROM tasks t, user_tasks ut WHERE t.id = ut.task_id AND ut.user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $tasks = $stmt->get_result();
        $stmt->close();
        return $tasks;
    }

    /**
     * Updating task
     * @param String $task_id id of the task
     * @param String $task task text
     * @param String $status task status
     */
    public function updateTask($user_id, $task_id, $task, $status) {
        $stmt = $this->conn->prepare("UPDATE tasks t, user_tasks ut set t.task = ?, t.status = ? WHERE t.id = ? AND t.id = ut.task_id AND ut.user_id = ?");
        $stmt->bind_param("siii", $task, $status, $task_id, $user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /**
     * Deleting a task
     * @param String $task_id id of the task to delete
     */
    public function deleteTask($user_id, $task_id) {
        $stmt = $this->conn->prepare("DELETE t FROM tasks t, user_tasks ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /* ------------- `user_tasks` table method ------------------ */

    /**
     * Function to assign a task to user
     * @param String $user_id id of the user
     * @param String $task_id id of the task
     */
    public function createUserTask($user_id, $task_id) {
        $stmt = $this->conn->prepare("INSERT INTO user_tasks(user_id, task_id) values(?, ?)");
        $stmt->bind_param("ii", $user_id, $task_id);
        $result = $stmt->execute();

        if (false === $result) {
            die('execute() failed: ' . htmlspecialchars($stmt->error));
        }
        $stmt->close();
        return $result;
    }

}

?>
