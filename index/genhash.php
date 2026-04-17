<?php
$hash = password_hash("password", PASSWORD_DEFAULT);
echo "Hash: " . $hash . "<br>";
echo "Verify: " . (password_verify("password", $hash) ? "OK" : "FAIL") . "<br>";
include "db_connect.php";
$result = $conn->query("SELECT password FROM users WHERE email = 0x61646d696e407469706565642e636f6d");
$row = $result->fetch_assoc();
echo "DB hash: " . $row["password"] . "<br>";
echo "DB verify: " . (password_verify("password", $row["password"]) ? "OK" : "FAIL");
