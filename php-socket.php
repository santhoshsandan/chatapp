<?php
define('HOST_NAME', "127.0.0.1");
define('PORT', 8080); // Ensure this port is free or change if necessary

$null = NULL;

// Include necessary files
require_once("DbConnect.php");
require_once("ChatHandler.php");

// Initialize database connection
$dbConnect = new DbConnect();
$conn = $dbConnect->connect(); // This method should return a valid mysqli connection

// Initialize ChatHandler with the database connection
$chatHandler = new ChatHandler($conn);

// Create the server socket
$socketResource = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if (!$socketResource) {
    die("Could not create socket: " . socket_strerror(socket_last_error()) . "\n");
}

// Set socket options
if (!socket_set_option($socketResource, SOL_SOCKET, SO_REUSEADDR, 1)) {
    die("Could not set socket option: " . socket_strerror(socket_last_error()) . "\n");
}

// Bind the socket to the address and port
if (!socket_bind($socketResource, HOST_NAME, PORT)) {
    die("Could not bind to socket: " . socket_strerror(socket_last_error()) . "\n");
}

// Start listening for connections
if (!socket_listen($socketResource)) {
    die("Could not listen on socket: " . socket_strerror(socket_last_error()) . "\n");
}

echo "Server started on " . HOST_NAME . ":" . PORT . "\n";

// Initialize the client socket array with the server socket
$clientSocketArray = array($socketResource);

// Main loop
while (true) {
    $newSocketArray = $clientSocketArray;
    $read = $newSocketArray;
    $write = $null;
    $except = $null;

    if (socket_select($read, $write, $except, 0, 10) === false) {
        echo "Socket select error: " . socket_strerror(socket_last_error()) . "\n";
        continue;
    }

    // Check for new connections
    if (in_array($socketResource, $read)) {
        $newSocket = socket_accept($socketResource);
        if ($newSocket === false) {
            echo "Failed to accept socket: " . socket_strerror(socket_last_error()) . "\n";
            continue;
        }
        $clientSocketArray[] = $newSocket;

        // Perform handshake
        $header = socket_read($newSocket, 1024);
        $chatHandler->doHandshake($header, $newSocket, HOST_NAME, PORT);

        // Notify other clients about the new connection
        socket_getpeername($newSocket, $client_ip_address);
        $connectionACK = $chatHandler->newConnectionACK($client_ip_address);
        $chatHandler->send($connectionACK, $clientSocketArray);

        // Remove the server socket from the array for this iteration
        $newSocketIndex = array_search($socketResource, $newSocketArray);
        unset($newSocketArray[$newSocketIndex]);
    }

    // Check for messages from clients
    foreach ($read as $socket) {
        if ($socket === $socketResource) {
            continue;
        }

        $socketData = @socket_read($socket, 1024, PHP_BINARY_READ);
        if ($socketData === false) {
            continue;
        }

        $socketMessage = $chatHandler->unseal($socketData);
        $messageObj = json_decode($socketMessage);

        if ($messageObj !== null) {
            if (isset($messageObj->chat_user) && isset($messageObj->chat_message)) {
                $userId = $messageObj->chat_user;
                $chat_box_message = $chatHandler->createChatBoxMessage($messageObj->chat_user, $messageObj->chat_message);

                // Save the message to the database
                if (!$chatHandler->saveMessage($userId, $messageObj->chat_message)) {
                    echo "Failed to save message to database.\n";
                }

                // Broadcast the message to other clients
                $chatHandler->send($chat_box_message, $clientSocketArray);
            } else {
                echo "Message object does not contain the expected properties.";
            }
        } else {
            echo "Failed to decode JSON from received message.";
        }
    }
}
socket_close($socketResource);
?>
