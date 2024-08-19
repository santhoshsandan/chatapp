<?php 
require_once 'DbConnect.php';

class user {
    private $id;
    private $name;
    private $email;
    private $loginStatus;
    private $lastLogin;
    public $dbConn;

    // Setters and Getters
    function setId($id) { $this->id = $id; }
    function getId() { return $this->id; }
    function setName($name) { $this->name = $name; }
    function getName() { return $this->name; }
    function setEmail($email) { $this->email = $email; }
    function getEmail() { return $this->email; }
    function setLoginStatus($loginStatus) { $this->loginStatus = $loginStatus; }
    function getLoginStatus() { return $this->loginStatus; }
    
    // Use this method to set lastLogin to current datetime
    function setLastLogin($lastLogin) { 
        // Convert Unix timestamp to datetime format
        $this->lastLogin = date('Y-m-d H:i:s', $lastLogin); 
    }
    function getLastLogin() { return $this->lastLogin; }

    // Constructor
    public function __construct() {
        require_once("DbConnect.php");
        $db = new DbConnect();
        $this->dbConn = $db->connect();
    }

    // Save user data to the database
    public function save() {
        $sql = "INSERT INTO `users`(`name`, `email`, `login_status`, `last_login`) VALUES (?, ?, ?, ?)";
        $stmt = $this->dbConn->prepare($sql);

        if ($stmt === false) {
            die("Prepare failed: " . $this->dbConn->error);
        }

        $stmt->bind_param('ssss', $this->name, $this->email, $this->loginStatus, $this->lastLogin);

        if ($stmt->execute()) {
            return true; // Successfully saved
        } else {
            return false; // Save failed
        }
    }

    // Fetch user by email
    public function getUserByEmail() {
        $stmt = $this->dbConn->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->bind_param('s', $this->email);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc(); // Return user data
    }

    // Fetch user by ID
    public function getUserById() {
        $stmt = $this->dbConn->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc(); // Return user data
    }

    // Update user login status
    public function updateLoginStatus() {
        $stmt = $this->dbConn->prepare('UPDATE users SET login_status = ?, last_login = ? WHERE id = ?');
        if ($stmt === false) {
            die("Prepare failed: " . $this->dbConn->error);
        }

        $stmt->bind_param('ssi', $this->loginStatus, $this->lastLogin, $this->id);

        if ($stmt->execute()) {
            return true; // Successfully updated
        } else {
            return false; // Update failed
        }
    }

    // Fetch all users
    public function getAllUsers() {
        $stmt = $this->dbConn->prepare("SELECT * FROM users");
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC); // Return all users
    }

    // Fetch user ID by email
    public function getUserIdByEmail($email) {
        $stmt = $this->dbConn->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row ? $row['id'] : null; // Return user ID or null if not found
    }
}
?>
