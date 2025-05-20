<?php
$allowed = ['http://localhost:3000','http://localhost:5173'];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed)) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header("Access-Control-Allow-Credentials: true");
}
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'error'=>'Not logged in']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'error'=>'Method not allowed']);
    exit;
}

$data        = json_decode(file_get_contents('php://input'), true);
$book_id     = intval($data['book_id'] ?? 0);
$rating      = intval($data['rating']  ?? 0);
$review_text = trim($data['review_text'] ?? '');

if ($book_id < 1 || $rating < 1 || $rating > 5 || !$review_text) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'Invalid input']);
    exit;
}

$uid = $_SESSION['user_id'];
$stmt = $conn->prepare("
    INSERT INTO reviews (user_id, book_id, rating, review_text)
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param('iiis', $uid, $book_id, $rating, $review_text);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$stmt->error]);
    exit;
}

echo json_encode(['success'=>true,'review_id'=>$stmt->insert_id]);
