<?php
include "db_connect.php";
$hash = password_hash("password", PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
$email = "admin@tipeed.com";
$stmt->bind_param("ss", $hash, $email);
$stmt->execute();
echo "Updated! New hash: " . $hash . "<br>";
$result = $conn->query("SELECT password FROM users WHERE email = 0x61646d696e407469706565642e636f6d");
$row = $result->fetch_assoc();
echo "DB verify: " . (password_verify("password", $row["password"]) ? "OK - LOGIN SHOULD WORK!" : "FAIL");
