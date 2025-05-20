<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

$allowed = ['http://localhost:3000','http://localhost:5173'];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed)) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header("Access-Control-Allow-Credentials: true");
}
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD']==='OPTIONS') {
    exit;
}

header("Content-Type: application/json; charset=UTF-8");
require __DIR__ . '/db.php';
session_start();

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
$book_id      = intval($data['book_id'] ?? 0);
$title         = trim($data['title'] ?? '');
$author        = trim($data['author'] ?? '');
$ISBN          = trim($data['ISBN'] ?? '');
$category      = trim($data['category'] ?? '');
$total_copies  = intval($data['total_copies'] ?? 1);
$image_url     = trim($data['image_url'] ?? '');

if ($book_id < 1 || !$title || !$author || !$ISBN) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'Invalid input']);
    exit;
}

$sel = $conn->prepare("SELECT total_copies, available_copies FROM books WHERE book_id = ?");
$sel->bind_param('i', $book_id);
$sel->execute();
$res = $sel->get_result()->fetch_assoc();
if (!$res) {
    http_response_code(404);
    echo json_encode(['success'=>false,'error'=>'Book not found']);
    exit;
}
$prev_total = intval($res['total_copies']);
$avail      = intval($res['available_copies']);

$diff = $total_copies - $prev_total;
$new_avail = max(0, $avail + $diff);

$stmt = $conn->prepare(
    "UPDATE books SET title=?, author=?, ISBN=?, category=?, image_url=?, total_copies=?, available_copies=?
     WHERE book_id=?"
);
$stmt->bind_param('sssssiii', $title, $author, $ISBN, $category, $image_url, $total_copies, $new_avail, $book_id);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$stmt->error]);
    exit;
}

echo json_encode(['success'=>true]);
exit;
?>
