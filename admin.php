<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type, X-Admin-Token');

require_once 'db.php';

$token = $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? $_GET['token'] ?? '';
$valid_token = getenv('ADMIN_TOKEN') ?: '11082010';

if ($token !== $valid_token) {
    http_response_code(403);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {

    case 'users':
        $role_filter = $_GET['role'] ?? '';
        $search      = $_GET['search'] ?? '';
        $where = []; $params = []; $types = '';
        if ($role_filter) { $where[] = 'role = ?'; $params[] = $role_filter; $types .= 's'; }
        if ($search)      { $where[] = 'fullname LIKE ?'; $params[] = '%'.$search.'%'; $types .= 's'; }
        $sql = "SELECT id, fullname, grade, role, subject, total_score, created_at FROM users";
        if ($where) $sql .= ' WHERE '.implode(' AND ', $where);
        $sql .= ' ORDER BY created_at DESC';
        if ($params) {
            $stmt = $conn->prepare($sql); $stmt->bind_param($types, ...$params);
            $stmt->execute(); $res = $stmt->get_result();
        } else { $res = $conn->query($sql); }
        $users = [];
        while ($r = $res->fetch_assoc()) $users[] = $r;
        echo json_encode(['users' => $users, 'count' => count($users)]);
        break;

    case 'user_detail':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $conn->prepare("SELECT id,fullname,grade,role,subject,total_score,created_at FROM users WHERE id=?");
        $stmt->bind_param('i', $id); $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if (!$user) { echo json_encode(['error'=>'not_found']); break; }
        $sc = $conn->prepare("SELECT topic,score,created_at FROM scores WHERE user_id=? ORDER BY created_at DESC LIMIT 20");
        $sc->bind_param('i', $id); $sc->execute();
        $scores = $sc->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['user'=>$user,'scores'=>$scores]);
        break;

    case 'delete_user':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST only']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['id'] ?? 0);
        if (!$id) { echo json_encode(['error'=>'missing id']); break; }
        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param('i', $id); $stmt->execute();
        echo json_encode(['success'=>true,'affected'=>$stmt->affected_rows]);
        break;

    case 'reset_score':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST only']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['id'] ?? 0);
        if (!$id) { echo json_encode(['error'=>'missing id']); break; }
        $conn->query("UPDATE users SET total_score=0 WHERE id=$id");
        $conn->query("DELETE FROM scores WHERE user_id=$id");
        echo json_encode(['success'=>true]);
        break;

    case 'change_role':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST only']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['id'] ?? 0); $role = $input['role'] ?? '';
        if (!$id || !in_array($role, ['student','teacher','admin'])) { echo json_encode(['error'=>'invalid']); break; }
        $stmt = $conn->prepare("UPDATE users SET role=? WHERE id=?");
        $stmt->bind_param('si', $role, $id); $stmt->execute();
        echo json_encode(['success'=>true]);
        break;

    case 'stats':
        $totals = $conn->query("SELECT COUNT(*) as total_users, SUM(role='student') as students, SUM(role='teacher') as teachers, SUM(role='admin') as admins, SUM(total_score) as total_score_sum FROM users")->fetch_assoc();
        $score_count = $conn->query("SELECT COUNT(*) as c FROM scores")->fetch_assoc()['c'];
        $class_q = $conn->query("SELECT COUNT(*) as c FROM classes");
        $class_count = $class_q ? $class_q->fetch_assoc()['c'] : 0;
        $recent = $conn->query("SELECT fullname,role,grade,created_at FROM users ORDER BY created_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['users'=>$totals,'score_count'=>$score_count,'class_count'=>$class_count,'recent'=>$recent]);
        break;

    case 'update_teacher_code':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST only']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $code = trim($input['code'] ?? '');
        if (strlen($code) < 6) { echo json_encode(['error'=>'too_short']); break; }
        $stmt = $conn->prepare("INSERT INTO site_settings (setting_key,setting_value) VALUES ('teacher_code',?) ON DUPLICATE KEY UPDATE setting_value=?");
        $stmt->bind_param('ss', $code, $code); $stmt->execute();
        echo json_encode(['success'=>true]);
        break;

    case 'classes':
        $res = $conn->query("SELECT c.*,u.fullname as teacher_name,(SELECT COUNT(*) FROM class_members cm WHERE cm.class_id=c.id) as member_count FROM classes c JOIN users u ON u.id=c.teacher_id ORDER BY c.created_at DESC");
        $classes = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        echo json_encode(['classes'=>$classes]);
        break;

    case 'delete_class':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST only']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM classes WHERE id=?");
        $stmt->bind_param('i', $id); $stmt->execute();
        echo json_encode(['success'=>true]);
        break;

    default:
        echo json_encode(['error'=>'unknown_action']);
}

$conn->close();
