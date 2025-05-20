<?php
$allowed = 'http://localhost:3000';
if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN'] === $allowed) {
    header("Access-Control-Allow-Origin: $allowed");
    header("Access-Control-Allow-Credentials: true");
}
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}
header("Content-Type: application/json; charset=UTF-8");
require 'db.php';
$sql = "
  SELECT
    book_id,
    title,
    author,
    ISBN,
    category,
    image_url,
    total_copies,
    available_copies
  FROM books
  ORDER BY title
";
$result = $conn->query($sql);
$books = [];
while ($row = $result->fetch_assoc()) {
    $books[] = $row;
}

echo json_encode($books);
exit;
