<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once 'db.php';

$action = $_GET['action'] ?? '';

switch ($action) {

    // Save a score
    case 'save':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST only']); exit; }
        $input = json_decode(file_get_contents('php://input'), true);
        $username = trim($input['username'] ?? '');
        $grade    = trim($input['grade']    ?? '');
        $topic    = trim($input['topic']    ?? '');
        $score    = (int)($input['score']   ?? 0);
        if (!$username || $score < 0) { echo json_encode(['error'=>'invalid']); exit; }

        // Find user id
        $uid = null;
        $u = $conn->prepare("SELECT id FROM users WHERE LOWER(fullname)=LOWER(?)");
        $u->bind_param('s', $username); $u->execute();
        $row = $u->get_result()->fetch_assoc();
        if ($row) $uid = $row['id'];

        // Insert score
        $stmt = $conn->prepare("INSERT INTO scores (user_id, username, grade, topic, score) VALUES (?,?,?,?,?)");
        $stmt->bind_param('isssi', $uid, $username, $grade, $topic, $score);
        $stmt->execute();

        // Update total_score on users
        if ($uid) {
            $conn->query("UPDATE users SET total_score = total_score + $score WHERE id = $uid");
        }
        echo json_encode(['success' => true]);
        break;

    // Global leaderboard (top 10)
    case 'leaderboard':
        $res = $conn->query("SELECT username, grade, SUM(score) as total FROM scores GROUP BY username, grade ORDER BY total DESC LIMIT 10");
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        echo json_encode($rows);
        break;

    // Class leaderboard
    case 'leaderboard_class':
        $grade = $_GET['grade'] ?? '';
        if (!$grade) { echo json_encode([]); exit; }
        $stmt = $conn->prepare("SELECT username, grade, SUM(score) as total FROM scores WHERE grade=? GROUP BY username, grade ORDER BY total DESC LIMIT 10");
        $stmt->bind_param('s', $grade); $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode($rows);
        break;

    // User data (scores by topic)
    case 'user_data':
        $user = $_GET['user'] ?? '';
        if (!$user) { echo json_encode(['error'=>'missing user']); exit; }
        $stmt = $conn->prepare("SELECT topic, SUM(score) as total, COUNT(*) as games FROM scores WHERE LOWER(username)=LOWER(?) GROUP BY topic ORDER BY total DESC");
        $stmt->bind_param('s', $user); $stmt->execute();
        $topics = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $total_stmt = $conn->prepare("SELECT SUM(score) as grand_total FROM scores WHERE LOWER(username)=LOWER(?)");
        $total_stmt->bind_param('s', $user); $total_stmt->execute();
        $grand = $total_stmt->get_result()->fetch_assoc();
        echo json_encode(['topics' => $topics, 'total' => $grand['grand_total'] ?? 0]);
        break;

    default:
        echo json_encode(['error' => 'unknown_action']);
}

$conn->close();
