<?php
require_once 'DbConnect.php';

class Message {
    private $id;
    private $userId;
    private $message;
    private $timestamp;
    public $dbConn;

    // Setters and Getters
    public function setId($id) { $this->id = $id; }
    public function getId() { return $this->id; }
    public function setUserId($userId) { $this->userId = $userId; }
    public function getUserId() { return $this->userId; }
    public function setMessage($message) { $this->message = $message; }
    public function getMessage() { return $this->message; }
    public function setTimestamp($timestamp) { $this->timestamp = $timestamp; }
    public function getTimestamp() { return $this->timestamp; }

    // Constructor
    public function __construct() {
        $db = new DbConnect();
        $this->dbConn = $db->connect();
    }

    // Save message to the database
    public function save() {
        $sql = "INSERT INTO messages (user_id, message, timestamp) VALUES (?, ?, ?)";
        $stmt = $this->dbConn->prepare($sql);

        // Bind parameters and execute
        $this->timestamp = date('Y-m-d H:i:s'); // Set the timestamp to current datetime
        $stmt->bind_param("iss", $this->userId, $this->message, $this->timestamp);

        if ($stmt->execute()) {
            return true; // Successfully saved
        } else {
            echo "Error: " . $this->dbConn->error; // Error handling
            return false; // Save failed
        }
    }

    // Fetch message by ID
    public function getMessageById() {
        $sql = 'SELECT * FROM messages WHERE id = ?';
        $stmt = $this->dbConn->prepare($sql);
        $stmt->bind_param("i", $this->id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc(); // Return message data
    }

    // Fetch all messages for a user
    public function getMessagesByUserId() {
        $sql = 'SELECT * FROM messages WHERE user_id = ? ORDER BY timestamp DESC';
        $stmt = $this->dbConn->prepare($sql);
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC); // Return all messages for user
    }

    // Fetch all messages
    public function getAllMessages() {
        $sql = "SELECT * FROM messages ORDER BY timestamp DESC";
        $result = $this->dbConn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC); // Return all messages
    }
}
?>
