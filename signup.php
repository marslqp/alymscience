<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'POST only']); exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$name         = trim($input['fullname']     ?? '');
$grade        = trim($input['grade']        ?? '');
$raw          = $input['password']           ?? '';
$role         = $input['role']               ?? 'student';
$teacher_code = $input['teacher_code']       ?? '';

if (!$name || !$raw || !$grade) {
    echo json_encode(['error' => 'missing_fields']); exit;
}
if (strlen($raw) < 6) {
    echo json_encode(['error' => 'password_short']); exit;
}

// Teacher code validation (server-side)
if ($role === 'teacher') {
    // Get code from DB or env
    $env_code = getenv('TEACHER_CODE') ?: 'Chem2026!';
    // Also check DB for custom code
    $code_row = $conn->query("SELECT setting_value FROM site_settings WHERE setting_key='teacher_code' LIMIT 1");
    if ($code_row && $code_row->num_rows > 0) {
        $env_code = $code_row->fetch_assoc()['setting_value'];
    }
    if ($teacher_code !== $env_code) {
        echo json_encode(['error' => 'invalid_teacher_code']); exit;
    }
}

// Check if name taken
$chk = $conn->prepare("SELECT id FROM users WHERE LOWER(fullname)=LOWER(?)");
$chk->bind_param('s', $name);
$chk->execute();
$chk->store_result();
if ($chk->num_rows > 0) {
    echo json_encode(['error' => 'name_taken']); exit;
}
$chk->close();

$hash = password_hash($raw, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (fullname, grade, password, role, total_score) VALUES (?, ?, ?, ?, 0)");
$stmt->bind_param('ssss', $name, $grade, $hash, $role);

if ($stmt->execute()) {
    $new_id = $stmt->insert_id;
    echo json_encode(['success' => true, 'name' => $name, 'grade' => $grade, 'role' => $role, 'id' => $new_id]);
} else {
    echo json_encode(['error' => 'db_error', 'detail' => $conn->error]);
}
$stmt->close();
$conn->close();
