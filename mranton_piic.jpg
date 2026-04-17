<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'POST only']); exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$name  = trim($input['fullname'] ?? '');
$raw   = $input['password']      ?? '';

if (!$name || !$raw) {
    echo json_encode(['error' => 'missing_fields']); exit;
}

$stmt = $conn->prepare("SELECT id, fullname, grade, password, total_score FROM users WHERE LOWER(fullname)=LOWER(?)");
$stmt->bind_param('s', $name);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($raw, $user['password'])) {
    echo json_encode(['error' => 'wrong_credentials']); exit;
}

echo json_encode([
    'success'     => true,
    'name'        => $user['fullname'],
    'grade'       => $user['grade'],
    'total_score' => $user['total_score'],
    'id'          => $user['id']
]);
$conn->close();
