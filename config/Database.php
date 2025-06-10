<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'autosalon';
    private $username = 'root';
    private $password = 'root';
    public $conn;

    public function connect() {
        $this->conn = null;
        $this->conn = mysqli_connect($this->host, $this->username, $this->password, $this->db_name);

        if (!$this->conn) {
            die("Connection failed: " . mysqli_connect_error());
        }

        mysqli_set_charset($this->conn, "utf8");
        return $this->conn;
    }

    public function close() {
        if ($this->conn) {
            mysqli_close($this->conn);
        }
    }
}
?>