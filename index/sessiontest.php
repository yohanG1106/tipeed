<?php
include "db_connect.php";
session_start();
$_SESSION["test"] = "hello123";
session_write_close();
echo "Session ID: " . session_id() . "<br>";
echo "Saved!";
?>