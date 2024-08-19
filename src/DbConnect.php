<?php
class DbConnect {
    private $host = 'localhost';
    private $db_name = 'chatapp';
    private $username = 'root';
    private $password = 'Admin123$'; // your password here
    public $conn;

    public function connect() {
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }

        return $this->conn;
    }
}
?>

 ?>