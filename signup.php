<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'POST only']);
    exit;
}

// Получаем JSON
$input = json_decode(file_get_contents('php://input'), true);

$name  = trim($input['fullname'] ?? '');
$grade = trim($input['grade'] ?? '');
$raw   = $input['password'] ?? '';
$role  = $input['role'] ?? 'student';
$teacher_code = $input['teacher_code'] ?? '';
$subject = $input['subject'] ?? null;

// Проверки
if (!$name || !$raw || !$grade) {
    echo json_encode(['error' => 'missing_fields']);
    exit;
}

if (strlen($raw) < 6) {
    echo json_encode(['error' => 'password_short']);
    exit;
}

// Проверка кода учителя
if ($role === 'teacher') {
    $correct_code = getenv('TEACHER_CODE') ?: 'Chem2026!';

    if (strtolower(trim($teacher_code)) !== strtolower(trim($correct_code))) {
        echo json_encode(['error' => 'invalid_teacher_code']);
        exit;
    }
}

// Проверка на существующее имя
$chk = $conn->prepare("SELECT id FROM users WHERE LOWER(fullname)=LOWER(?)");
$chk->bind_param('s', $name);
$chk->execute();
$chk->store_result();

if ($chk->num_rows > 0) {
    echo json_encode(['error' => 'name_taken']);
    exit;
}
$chk->close();

// Хешируем пароль
$hash = password_hash($raw, PASSWORD_DEFAULT);

// Вставка
$stmt = $conn->prepare("
    INSERT INTO users (fullname, grade, password, total_score, role, subject) 
    VALUES (?, ?, ?, 0, ?, ?)
");

if (!$stmt) {
    echo json_encode([
        'error' => 'sql_prepare_error',
        'detail' => $conn->error
    ]);
    exit;
}

$stmt->bind_param('sssss', $name, $grade, $hash, $role, $subject);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'name' => $name,
        'grade' => $grade,
        'role' => $role,
        'id' => $stmt->insert_id
    ]);
} else {
    echo json_encode([
        'error' => 'db_error',
        'detail' => $stmt->error
    ]);
}

$stmt->close();
$conn->close();
