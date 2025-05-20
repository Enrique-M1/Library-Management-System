<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

$allowed = ['http://localhost:3000','http://localhost:5173'];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed)) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header("Access-Control-Allow-Credentials: true");
}
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD']==='OPTIONS') exit;

header('Content-Type: application/json; charset=UTF-8');
session_start();

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    setcookie(session_name(), '', time() - 42000, '/');
}
session_destroy();

echo json_encode(['success' => true]);
exit;
