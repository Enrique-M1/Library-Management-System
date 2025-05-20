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
if ($_SERVER['REQUEST_METHOD']==='OPTIONS') exit;

header('Content-Type: application/json; charset=UTF-8');
session_start();
require __DIR__ . '/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role']!=='Admin') {
    http_response_code(403);
    echo json_encode(['success'=>false,'error'=>'Forbidden']);
    exit;
}

$sql = "
  SELECT b.book_id, b.title, b.author, COUNT(c.checkout_id) AS borrow_count
    FROM checkouts c
    JOIN books b ON c.book_id = b.book_id
   GROUP BY c.book_id
   ORDER BY borrow_count DESC
   LIMIT 10";
$result = $conn->query($sql);
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
echo json_encode(['success'=>true,'data'=>$data]);
exit;
?>
