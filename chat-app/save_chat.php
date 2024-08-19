<?php
// Include necessary files for database connection and class definitions
require_once 'DbConnect.php'; // Ensure this file sets up the database connection
require_once 'users.php';     // Ensure this file defines the User class
require_once 'messages.php';  // Ensure this file defines the Message class

// Check if POST data is set
if (!isset($_POST['chat_user']) || !isset($_POST['chat_email']) || !isset($_POST['chat_message'])) {
    echo "Invalid input. POST data missing or incorrect.";
    echo "<pre>";
    print_r($_POST); // Print POST data for debugging
    echo "</pre>";
    exit;
}

// Retrieve and sanitize POST data
$username = filter_input(INPUT_POST, 'chat_user', FILTER_SANITIZE_STRING);
$email = filter_input(INPUT_POST, 'chat_email', FILTER_SANITIZE_EMAIL);
$messageText = filter_input(INPUT_POST, 'chat_message', FILTER_SANITIZE_STRING);

// Create a new DbConnect instance and get the connection
$db = new DbConnect();
$conn = $db->connect();

// Check if the connection was successful
if ($conn === null) {
    die("Database connection failed.");
}

// Create a new User object and set properties
$user = new User();
$user->dbConn = $conn; // Assign the connection to the User object
$user->setName($username);
$user->setEmail($email);
$user->setLoginStatus(1); // Assuming the user is logged in
$user->setLastLogin(time()); // Set the current timestamp

// Check if user exists
$userId = $user->getUserIdByEmail($email);
if (!$userId) {
    // Save the user if not already exists
    if ($user->save()) {
        $userId = $user->getUserIdByEmail($email); // Fetch the newly created user's ID
    } else {
        echo "Failed to save user data.";
        $conn->close();
        exit;
    }
}

// Create a new Message object and set properties
$messageObj = new Message();
$messageObj->dbConn = $conn; // Assign the connection to the Message object
$messageObj->setUserId($userId);
$messageObj->setMessage($messageText);

// Save the message
if ($messageObj->save()) {
    echo "Message saved successfully!";

    // Fetch all messages for the user
    $allMessages = $messageObj->getMessagesByUserId();

    // Convert messages to JSON format
    $jsonData = json_encode($allMessages, JSON_PRETTY_PRINT);

    // Define the path to the JSON file based on user ID
    if (!is_dir('data')) {
        mkdir('data', 0777, true); // Create directory if it doesn't exist
    }
    $jsonFilePath = 'data/user_messages_' . $userId . '.json'; // Use user ID to create a unique file name

    // Save JSON data to the file
    if (file_put_contents($jsonFilePath, $jsonData) !== false) {
        echo "Messages saved to JSON file successfully!";
    } else {
        echo "Failed to save messages to JSON file.";
    }
} else {
    echo "Failed to save message.";
}

// Close the database connection
$conn->close();
?>
