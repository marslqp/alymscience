<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, X-User-Id, X-User-Role');

require_once 'db.php';

$teacher_id   = (int)($_SERVER['HTTP_X_USER_ID']   ?? 0);
$user_role    = $_SERVER['HTTP_X_USER_ROLE'] ?? 'student';
$action       = $_GET['action'] ?? '';

// All endpoints require authentication
if (!$teacher_id) {
    http_response_code(401);
    echo json_encode(['error' => 'not_authenticated']); exit;
}

// Most endpoints require teacher role
$teacher_actions = ['create_class','add_student','remove_student','create_assignment','class_stats','my_classes','delete_class'];
if (in_array($action, $teacher_actions) && $user_role !== 'teacher' && $user_role !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'teacher_only']); exit;
}

switch ($action) {

    // ─── CREATE CLASS ────────────────────────────────────────────────────
    case 'create_class':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST only']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $name  = trim($input['class_name'] ?? '');
        $grade = trim($input['grade_level'] ?? '');
        $desc  = trim($input['description'] ?? '');
        if (!$name) { echo json_encode(['error'=>'missing_name']); break; }
        // Generate unique invite code
        do {
            $code = strtoupper(substr(md5(uniqid()), 0, 7));
            $exists = $conn->query("SELECT id FROM classes WHERE invite_code='$code'")->num_rows;
        } while ($exists);
        $stmt = $conn->prepare("INSERT INTO classes (teacher_id, class_name, grade_level, description, invite_code) VALUES (?,?,?,?,?)");
        $stmt->bind_param('issss', $teacher_id, $name, $grade, $desc, $code);
        if ($stmt->execute()) {
            echo json_encode(['success'=>true,'class_id'=>$stmt->insert_id,'invite_code'=>$code]);
        } else {
            echo json_encode(['error'=>'db_error','detail'=>$conn->error]);
        }
        break;

    // ─── MY CLASSES (teacher sees their classes) ─────────────────────────
    case 'my_classes':
        $res = $conn->prepare("SELECT c.*, (SELECT COUNT(*) FROM class_members m WHERE m.class_id=c.id) as member_count FROM classes c WHERE c.teacher_id=? ORDER BY c.created_at DESC");
        $res->bind_param('i', $teacher_id); $res->execute();
        $classes = $res->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['classes'=>$classes]);
        break;

    // ─── CLASS DETAIL + STUDENTS ─────────────────────────────────────────
    case 'class_detail':
        $class_id = (int)($_GET['class_id'] ?? 0);
        $stmt = $conn->prepare("SELECT c.*,u.fullname as teacher_name FROM classes c JOIN users u ON u.id=c.teacher_id WHERE c.id=?");
        $stmt->bind_param('i', $class_id); $stmt->execute();
        $class = $stmt->get_result()->fetch_assoc();
        if (!$class) { echo json_encode(['error'=>'not_found']); break; }
        // Check access: teacher owns it OR student is member
        if ($user_role !== 'admin') {
            if ($user_role === 'teacher' && $class['teacher_id'] != $teacher_id) {
                echo json_encode(['error'=>'access_denied']); break;
            }
        }
        $members = $conn->prepare("SELECT u.id,u.fullname,u.grade,u.total_score FROM class_members m JOIN users u ON u.id=m.student_id WHERE m.class_id=? ORDER BY u.total_score DESC");
        $members->bind_param('i', $class_id); $members->execute();
        $students = $members->get_result()->fetch_all(MYSQLI_ASSOC);
        // Assignments
        $asgn = $conn->prepare("SELECT a.*, (SELECT COUNT(*) FROM assignment_results ar WHERE ar.assignment_id=a.id) as submissions FROM assignments a WHERE a.class_id=? ORDER BY a.created_at DESC");
        $asgn->bind_param('i', $class_id); $asgn->execute();
        $assignments = $asgn->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['class'=>$class,'students'=>$students,'assignments'=>$assignments]);
        break;

    // ─── ADD STUDENT BY ID OR NAME ───────────────────────────────────────
    case 'add_student':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST only']); break; }
        $input    = json_decode(file_get_contents('php://input'), true);
        $class_id = (int)($input['class_id'] ?? 0);
        $sid      = (int)($input['student_id'] ?? 0);
        $sname    = trim($input['student_name'] ?? '');
        // Verify teacher owns class
        $owns = $conn->prepare("SELECT id FROM classes WHERE id=? AND teacher_id=?");
        $owns->bind_param('ii', $class_id, $teacher_id); $owns->execute(); $owns->store_result();
        if ($owns->num_rows === 0) { echo json_encode(['error'=>'not_your_class']); break; }
        // Find student
        if (!$sid && $sname) {
            $find = $conn->prepare("SELECT id,fullname,grade FROM users WHERE LOWER(fullname)=LOWER(?) AND role='student'");
            $find->bind_param('s', $sname); $find->execute();
            $found = $find->get_result()->fetch_assoc();
            if (!$found) { echo json_encode(['error'=>'student_not_found']); break; }
            $sid = $found['id'];
        }
        if (!$sid) { echo json_encode(['error'=>'missing_student']); break; }
        // Check student exists
        $check = $conn->prepare("SELECT id,fullname,grade FROM users WHERE id=? AND role='student'");
        $check->bind_param('i', $sid); $check->execute();
        $student = $check->get_result()->fetch_assoc();
        if (!$student) { echo json_encode(['error'=>'student_not_found']); break; }
        // Add
        $ins = $conn->prepare("INSERT IGNORE INTO class_members (class_id,student_id) VALUES (?,?)");
        $ins->bind_param('ii', $class_id, $sid); $ins->execute();
        echo json_encode(['success'=>true,'student'=>$student,'was_already_member'=>$ins->affected_rows===0]);
        break;

    // ─── JOIN CLASS BY INVITE CODE (student action) ───────────────────────
    case 'join_class':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST only']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $code  = strtoupper(trim($input['invite_code'] ?? ''));
        // Find class
        $stmt = $conn->prepare("SELECT id,class_name FROM classes WHERE invite_code=?");
        $stmt->bind_param('s', $code); $stmt->execute();
        $class = $stmt->get_result()->fetch_assoc();
        if (!$class) { echo json_encode(['error'=>'invalid_code']); break; }
        $cid = $class['id'];
        $ins = $conn->prepare("INSERT IGNORE INTO class_members (class_id,student_id) VALUES (?,?)");
        $ins->bind_param('ii', $cid, $teacher_id); // teacher_id = current user
        $ins->execute();
        echo json_encode(['success'=>true,'class_name'=>$class['class_name'],'already_member'=>$ins->affected_rows===0]);
        break;

    // ─── REMOVE STUDENT ──────────────────────────────────────────────────
    case 'remove_student':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST only']); break; }
        $input    = json_decode(file_get_contents('php://input'), true);
        $class_id = (int)($input['class_id'] ?? 0);
        $sid      = (int)($input['student_id'] ?? 0);
        $owns = $conn->prepare("SELECT id FROM classes WHERE id=? AND teacher_id=?");
        $owns->bind_param('ii', $class_id, $teacher_id); $owns->execute(); $owns->store_result();
        if ($owns->num_rows === 0) { echo json_encode(['error'=>'not_your_class']); break; }
        $del = $conn->prepare("DELETE FROM class_members WHERE class_id=? AND student_id=?");
        $del->bind_param('ii', $class_id, $sid); $del->execute();
        echo json_encode(['success'=>true]);
        break;

    // ─── CREATE ASSIGNMENT ───────────────────────────────────────────────
    case 'create_assignment':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST only']); break; }
        $input    = json_decode(file_get_contents('php://input'), true);
        $class_id = (int)($input['class_id'] ?? 0);
        $title    = trim($input['title'] ?? '');
        $desc     = trim($input['description'] ?? '');
        $topic    = trim($input['topic'] ?? '');
        $grade    = trim($input['grade_level'] ?? '');
        $due      = $input['due_date'] ?? null;
        $seed     = rand(1, 99999); // unique seed = different variant per assignment
        if (!$class_id || !$title || !$topic) { echo json_encode(['error'=>'missing_fields']); break; }
        $owns = $conn->prepare("SELECT id FROM classes WHERE id=? AND teacher_id=?");
        $owns->bind_param('ii', $class_id, $teacher_id); $owns->execute(); $owns->store_result();
        if ($owns->num_rows === 0) { echo json_encode(['error'=>'not_your_class']); break; }
        $stmt = $conn->prepare("INSERT INTO assignments (class_id,teacher_id,title,description,topic,grade_level,variant_seed,due_date) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param('iissssjs', $class_id, $teacher_id, $title, $desc, $topic, $grade, $seed, $due);
        // fix types
        $stmt = $conn->prepare("INSERT INTO assignments (class_id,teacher_id,title,description,topic,grade_level,variant_seed,due_date) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param('iissssis', $class_id, $teacher_id, $title, $desc, $topic, $grade, $seed, $due);
        if ($stmt->execute()) {
            echo json_encode(['success'=>true,'assignment_id'=>$stmt->insert_id,'variant_seed'=>$seed]);
        } else {
            echo json_encode(['error'=>'db_error','detail'=>$conn->error]);
        }
        break;

    // ─── SUBMIT ASSIGNMENT RESULT ────────────────────────────────────────
    case 'submit_result':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST only']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $asgn_id  = (int)($input['assignment_id'] ?? 0);
        $score    = (int)($input['score'] ?? 0);
        $max      = (int)($input['max_score'] ?? 100);
        $answers  = json_encode($input['answers'] ?? []);
        $stmt = $conn->prepare("INSERT INTO assignment_results (assignment_id,student_id,score,max_score,answers) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE score=VALUES(score),answers=VALUES(answers),submitted_at=NOW()");
        $stmt->bind_param('iiiis', $asgn_id, $teacher_id, $score, $max, $answers);
        if ($stmt->execute()) {
            // Update user total_score
            $conn->query("UPDATE users SET total_score=total_score+$score WHERE id=$teacher_id");
            echo json_encode(['success'=>true]);
        } else {
            echo json_encode(['error'=>'db_error']);
        }
        break;

    // ─── ASSIGNMENT RESULTS (teacher view) ───────────────────────────────
    case 'assignment_results':
        $asgn_id = (int)($_GET['assignment_id'] ?? 0);
        $stmt = $conn->prepare("SELECT a.*,c.teacher_id FROM assignments a JOIN classes c ON c.id=a.class_id WHERE a.id=?");
        $stmt->bind_param('i', $asgn_id); $stmt->execute();
        $asgn = $stmt->get_result()->fetch_assoc();
        if (!$asgn) { echo json_encode(['error'=>'not_found']); break; }
        if ($asgn['teacher_id'] != $teacher_id && $user_role !== 'admin') { echo json_encode(['error'=>'access_denied']); break; }
        $res = $conn->prepare("SELECT ar.*,u.fullname,u.grade FROM assignment_results ar JOIN users u ON u.id=ar.student_id WHERE ar.assignment_id=? ORDER BY ar.score DESC");
        $res->bind_param('i', $asgn_id); $res->execute();
        $results = $res->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['assignment'=>$asgn,'results'=>$results]);
        break;

    // ─── MY STUDENT CLASSES ──────────────────────────────────────────────
    case 'my_student_classes':
        // Student sees their enrolled classes
        $res = $conn->prepare("SELECT c.*,u.fullname as teacher_name FROM class_members m JOIN classes c ON c.id=m.class_id JOIN users u ON u.id=c.teacher_id WHERE m.student_id=? ORDER BY m.joined_at DESC");
        $res->bind_param('i', $teacher_id); $res->execute();
        $classes = $res->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['classes'=>$classes]);
        break;

    // ─── SEARCH STUDENTS ─────────────────────────────────────────────────
    case 'search_students':
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) { echo json_encode(['students'=>[]]); break; }
        $like = '%'.$q.'%';
        $stmt = $conn->prepare("SELECT id,fullname,grade FROM users WHERE role='student' AND (fullname LIKE ? OR CAST(id AS CHAR) LIKE ?) LIMIT 15");
        $stmt->bind_param('ss', $like, $like); $stmt->execute();
        $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['students'=>$students]);
        break;

    // ─── DELETE CLASS ────────────────────────────────────────────────────
    case 'delete_class':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST only']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $class_id = (int)($input['class_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM classes WHERE id=? AND teacher_id=?");
        $stmt->bind_param('ii', $class_id, $teacher_id); $stmt->execute();
        echo json_encode(['success'=>true,'affected'=>$stmt->affected_rows]);
        break;

    default:
        echo json_encode(['error'=>'unknown_action']);
}

$conn->close();
