<?php
$allowed = ['http://localhost:3000','http://localhost:5173'];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed)) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header("Access-Control-Allow-Credentials: true");
}
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD']==='OPTIONS') exit;

header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/db.php';
session_start();

$book_id = isset($_GET['book_id']) ? intval($_GET['book_id']) : 0;
if ($book_id < 1) {
  http_response_code(400);
  echo json_encode(['success'=>false,'error'=>'Invalid book_id']);
  exit;
}

$stmt = $conn->prepare("
  SELECT book_id, title, author, category, ISBN, image_url,
         total_copies, available_copies
    FROM books
   WHERE book_id = ?
   LIMIT 1
");
$stmt->bind_param('i',$book_id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
if (!$book) {
  http_response_code(404);
  echo json_encode(['success'=>false,'error'=>'Book not found']);
  exit;
}

$stmt = $conn->prepare("
  SELECT r.review_id, r.rating, r.review_text, r.review_date,
         u.username
    FROM reviews r
    JOIN users   u ON r.user_id = u.user_id
   WHERE r.book_id = ?
   ORDER BY r.review_date DESC
");
$stmt->bind_param('i',$book_id);
$stmt->execute();
$res = $stmt->get_result();

$reviews = [];
while ($row = $res->fetch_assoc()) {
  $reviews[] = $row;
}

echo json_encode([
  'success' => true,
  'data'    => [
    'book'    => $book,
    'reviews' => $reviews
  ]
]);
