<?php
include "db_connect.php";
session_start();
$_SESSION['test'] = 'hello';
echo "Session ID: " . session_id() . "<br>";
echo "Session data: " . print_r($_SESSION, true) . "<br>";
echo "Save path: " . ini_get('session.save_path') . "<br>";
echo "Save handler: " . ini_get('session.save_handler') . "<br>";