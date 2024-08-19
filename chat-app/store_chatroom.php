<?php
require_once("Chatroom.php");

// Check if form data is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $chatroom = new Chatroom();

    // Set chatroom properties from the form data
    $chatroom->setName($_POST['name']);
    $chatroom->setDescription($_POST['description']);

    // Save the chatroom
    if ($chatroom->save()) {
        echo "Chatroom created successfully!";
    } else {
        echo "Failed to create chatroom.";
    }
} else {
    echo "Invalid request method.";
}
