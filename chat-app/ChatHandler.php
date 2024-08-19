<?php

class ChatHandler {
    private $conn; // Database connection

    // Constructor to initialize the database connection
    public function __construct($dbConn) {
        $this->conn = $dbConn;
    }

    // Method to send a message to all connected clients
    public function send($message, $clientSocketArray) {
        $messageLength = strlen($message);
        foreach ($clientSocketArray as $clientSocket) {
            @socket_write($clientSocket, $message, $messageLength);
        }
        return true;
    }

    // Method to unseal (decode) data from a WebSocket frame
    public function unseal($socketData) {
        $length = ord($socketData[1]) & 127;

        if ($length == 126) {
            $masks = substr($socketData, 4, 4);
            $data = substr($socketData, 8);
        } elseif ($length == 127) {
            $masks = substr($socketData, 10, 4);
            $data = substr($socketData, 14);
        } else {
            $masks = substr($socketData, 2, 4);
            $data = substr($socketData, 6);
        }

        $decodedData = '';
        for ($i = 0; $i < strlen($data); ++$i) {
            $decodedData .= $data[$i] ^ $masks[$i % 4];
        }
        return $decodedData;
    }

    // Method to seal (encode) data into a WebSocket frame
    public function seal($socketData) {
        $b1 = 0x80 | 0x1; // Final frame and text frame
        $length = strlen($socketData);

        if ($length <= 125) {
            $header = pack('CC', $b1, $length);
        } elseif ($length <= 65535) {
            $header = pack('CCn', $b1, 126, $length);
        } else {
            $header = pack('CCNN', $b1, 127, $length);
        }
        return $header . $socketData;
    }

    // Method to perform WebSocket handshake
    public function doHandshake($received_header, $client_socket_resource, $host_name, $port) {
        $headers = array();
        $lines = preg_split("/\r\n/", $received_header);
        foreach ($lines as $line) {
            $line = chop($line);
            if (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
                $headers[$matches[1]] = $matches[2];
            }
        }

        $secKey = $headers['Sec-WebSocket-Key'];
        $secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
        $buffer = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "Sec-WebSocket-Accept: $secAccept\r\n" .
            "WebSocket-Origin: $host_name\r\n" .
            "WebSocket-Location: ws://$host_name:$port/\r\n\r\n";
        socket_write($client_socket_resource, $buffer, strlen($buffer));
    }

    // Method to create a new connection acknowledgment message
    public function newConnectionACK($client_ip_address) {
        $message = 'New client ' . $client_ip_address . ' joined';
        $messageArray = array('message' => $message, 'message_type' => 'chat-connection-ack');
        return $this->seal(json_encode($messageArray));
    }

    // Method to create a connection disconnect acknowledgment message
    public function connectionDisconnectACK($client_ip_address) {
        $message = 'Client ' . $client_ip_address . ' disconnected';
        $messageArray = array('message' => $message, 'message_type' => 'chat-connection-ack');
        return $this->seal(json_encode($messageArray));
    }

    // Method to create a chat box message
    public function createChatBoxMessage($username, $messageContent) {
        $messageArray = array('username' => $username, 'message' => $messageContent);
        $chatMessage = $this->seal(json_encode($messageArray));
        return $chatMessage;
    }
    

    // Method to save a chat message to the database
    public function saveMessage($userId, $message) {
        // Ensure the database connection is valid
        if ($this->conn === null) {
            throw new Exception("Database connection is not established.");
        }
    
        // Prepare the SQL statement
        $stmt = $this->conn->prepare("INSERT INTO messages (user_id, message) VALUES (?, ?)");
        if (!$stmt) {
            die("Prepare failed: " . $this->conn->error);
        }
    
        // Bind parameters and execute the statement
        $stmt->bind_param('is', $userId, $message);
        if ($stmt->execute()) {
            return true;
        } else {
            die("Execute failed: " . $stmt->error);
        }
    }
}
?>
