<?php
class DatabaseHelper {
    private $host = 'localhost';
    private $username = 'root'; // Ubah jika berbeda
    private $password = '';     // Ubah jika berbeda
    private $database = 'ukt_mahasiswa';
    private $conn;

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);

        if ($this->conn->connect_error) {
            die("Koneksi ke database gagal: " . $this->conn->connect_error);
        }
    }

    public function query($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);
        if ($params) {
            $types = str_repeat('s', count($params)); // Semua parameter dianggap string
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result;
    }

    public function execute($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);
        if ($params) {
            $types = str_repeat('s', count($params)); // Semua parameter dianggap string
            $stmt->bind_param($types, ...$params);
        }
        return $stmt->execute();
    }

    public function close() {
        $this->conn->close();
    }
}
?>