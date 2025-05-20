<?php
$allowed = ['http://localhost:3000','http://localhost:5173'];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed)) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header("Access-Control-Allow-Credentials: true");
}
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD']==='OPTIONS') exit;
header("Content-Type: application/json; charset=UTF-8");

session_start();
require __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['success'=>false,'error'=>'Not logged in']);
  exit;
}
if ($_SERVER['REQUEST_METHOD']!=='POST') {
  http_response_code(405);
  echo json_encode(['success'=>false,'error'=>'Method not allowed']);
  exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$book_id = intval($data['book_id'] ?? 0);
if ($book_id <= 0) {
  http_response_code(400);
  echo json_encode(['success'=>false,'error'=>'Invalid book_id']);
  exit;
}

$uid  = $_SESSION['user_id'];
$role = $_SESSION['role']; // "Student" or "Faculty"

$limit = $role === 'Faculty' ? 10 : 5;
$stmt = $conn->prepare("
  SELECT COUNT(*) 
    FROM checkouts 
   WHERE user_id = ? AND returned_date IS NULL
");
$stmt->bind_param('i',$uid);
$stmt->execute();
$current = $stmt->get_result()->fetch_row()[0];
if ($current >= $limit) {
  echo json_encode(['success'=>false,'error'=>"You may only have {$limit} books checked out at once."]);
  exit;
}

$stmt = $conn->prepare("
  SELECT available_copies 
    FROM books 
   WHERE book_id = ?
");
$stmt->bind_param('i',$book_id);
$stmt->execute();
$avail = $stmt->get_result()->fetch_row()[0] ?? 0;
if ($avail < 1) {
  echo json_encode(['success'=>false,'error'=>'No copies available']);
  exit;
}

$borrowed = date('Y-m-d');
$due      = date('Y-m-d', strtotime('+14 days')); // 2-week 
$stmt = $conn->prepare("
  INSERT INTO checkouts (user_id, book_id, borrowed_date, due_date)
       VALUES (?, ?, ?, ?)
");
$stmt->bind_param('iiss',$uid,$book_id,$borrowed,$due);
if (!$stmt->execute()) {
  http_response_code(500);
  echo json_encode(['success'=>false,'error'=>$stmt->error]);
  exit;
}

// decrement available_copies
$stmt = $conn->prepare("
  UPDATE books
     SET available_copies = available_copies - 1
   WHERE book_id = ?
");
$stmt->bind_param('i',$book_id);
$stmt->execute();

echo json_encode(['success'=>true,'checkout_id'=>$stmt->insert_id]);
