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
  SELECT c.checkout_id, u.username, b.title, c.borrowed_date, c.due_date,
         DATEDIFF(CURDATE(), c.due_date) AS days_overdue
    FROM checkouts c
    JOIN users u ON c.user_id = u.user_id
    JOIN books b ON c.book_id = b.book_id
   WHERE c.returned_date IS NULL
     AND c.due_date < CURDATE()
   ORDER BY c.due_date ASC";
$result = $conn->query($sql);
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
echo json_encode(['success'=>true,'data'=>$data]);
?>