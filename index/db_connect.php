<?php

if (defined('DB_CONNECTED')) return;
define('DB_CONNECTED', true);

$servername = getenv('MYSQLHOST')     ?: 'localhost';
$username   = getenv('MYSQLUSER')     ?: 'root';
$password   = getenv('MYSQLPASSWORD') ?: '';
$dbname     = getenv('MYSQLDATABASE') ?: 'tipeedsystem';
$port       = (int)(getenv('MYSQLPORT') ?: 3306);

$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

class MySQLSessionHandler implements SessionHandlerInterface {
    private $conn;
    public function __construct($conn) { $this->conn = $conn; }
    public function open($path, $name): bool { return true; }
    public function close(): bool { return true; }
    public function read($id): string {
        $stmt = $this->conn->prepare("SELECT data FROM sessions WHERE id=? AND expires > NOW()");
        if (!$stmt) return '';
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) return $row['data'];
        return '';
    }
    public function write($id, $data): bool {
        $stmt = $this->conn->prepare("REPLACE INTO sessions (id, data, expires) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 2 HOUR))");
        if (!$stmt) return false;
        $stmt->bind_param("ss", $id, $data);
        return $stmt->execute();
    }
    public function destroy($id): bool {
        $stmt = $this->conn->prepare("DELETE FROM sessions WHERE id=?");
        if (!$stmt) return false;
        $stmt->bind_param("s", $id);
        return $stmt->execute();
    }
    public function gc($max_lifetime): int|false { return 0; }
}

$handler = new MySQLSessionHandler($conn);
session_set_save_handler($handler, true);