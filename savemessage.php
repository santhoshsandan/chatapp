public function saveMessage($userId, $messageText) {
    // Ensure the database connection is valid
    if ($this->conn === null) {
        throw new Exception("Database connection is not established.");
    }

    // Check if the user_id exists in the users table
    $checkUserStmt = $this->conn->prepare("SELECT id FROM users WHERE id = ?");
    if (!$checkUserStmt) {
        die("Prepare failed: " . $this->conn->error);
    }
    $checkUserStmt->bind_param('i', $userId); // Use 'i' for integer type
    $checkUserStmt->execute();
    $result = $checkUserStmt->get_result();

    if ($result->num_rows === 0) {
        die("User ID does not exist in the users table.");
    }

    // Prepare the SQL statement to insert the message
    $stmt = $this->conn->prepare("INSERT INTO messages (user_id, message) VALUES (?, ?)");
    if (!$stmt) {
        die("Prepare failed: " . $this->conn->error);
    }

    // Bind parameters and execute the statement
    $stmt->bind_param('is', $userId, $messageText); // 'i' for integer, 's' for string
    if ($stmt->execute()) {
        return true;
    } else {
        die("Execute failed: " . $stmt->error);
    }
}
