<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
$allowed = ['http://localhost:3000','http://localhost:5173'];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'],$allowed)) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header("Access-Control-Allow-Credentials: true");
}
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD']==='OPTIONS') exit;

header('Content-Type: application/json; charset=UTF-8');
session_start();
require __DIR__ . '/db.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['success'=>false,'error'=>'Forbidden']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'error'=>'Method Not Allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$checkoutId = intval($data['checkout_id'] ?? 0);
$action = $data['action'] ?? '';

if (!$checkoutId || $action !== 'return') {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'Invalid input']);
    exit;
}

$today = date('Y-m-d');
$upd = $conn->prepare("UPDATE checkouts SET returned_date = ? WHERE checkout_id = ?");
$upd->bind_param('si', $today, $checkoutId);
if (!$upd->execute()) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$upd->error]);
    exit;
}

$sel = $conn->prepare("SELECT book_id FROM checkouts WHERE checkout_id = ?");
$sel->bind_param('i', $checkoutId);
$sel->execute();
$bid = intval($sel->get_result()->fetch_assoc()['book_id']);
$inc = $conn->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE book_id = ?");
$inc->bind_param('i', $bid);
$inc->execute();

echo json_encode(['success' => true]);
exit;
?>