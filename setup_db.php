<?php
require_once 'db.php';

$tables = [];

// Users table (extended with role)
$tables[] = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(120) NOT NULL UNIQUE,
    grade VARCHAR(30) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student','teacher','admin') NOT NULL DEFAULT 'student',
    subject VARCHAR(60) DEFAULT NULL,
    total_score INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// Classes table (created by teachers)
$tables[] = "CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    class_name VARCHAR(60) NOT NULL,
    grade_level VARCHAR(20) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    invite_code VARCHAR(20) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// Class members
$tables[] = "CREATE TABLE IF NOT EXISTS class_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    student_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_member (class_id, student_id),
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// Assignments (tasks given by teacher to a class)
$tables[] = "CREATE TABLE IF NOT EXISTS assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    teacher_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    topic VARCHAR(100) NOT NULL,
    grade_level VARCHAR(20) DEFAULT NULL,
    variant_seed INT NOT NULL DEFAULT 1,
    due_date DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// Assignment results (student submissions)
$tables[] = "CREATE TABLE IF NOT EXISTS assignment_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    score INT NOT NULL DEFAULT 0,
    max_score INT NOT NULL DEFAULT 100,
    answers TEXT DEFAULT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_submission (assignment_id, student_id),
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// Scores table (for quizzes/games)
$tables[] = "CREATE TABLE IF NOT EXISTS scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    username VARCHAR(120) NOT NULL,
    grade VARCHAR(30) DEFAULT NULL,
    topic VARCHAR(100) DEFAULT NULL,
    score INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// Site settings
$tables[] = "CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(60) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// Default settings
$defaults = [
    ['teacher_code', 'Chem2026!']
];

foreach ($tables as $sql) {
    if (!$conn->query($sql)) {
        echo "Error creating table: " . $conn->error . "\n";
    }
}

// Insert default settings
foreach ($defaults as $d) {
    $stmt = $conn->prepare("INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
    $stmt->bind_param('ss', $d[0], $d[1]);
    $stmt->execute();
    $stmt->close();
}

echo "DB setup complete!\n";

// Add role column to users if not exists (migration)
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS role ENUM('student','teacher','admin') NOT NULL DEFAULT 'student'");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS subject VARCHAR(60) DEFAULT NULL");

$conn->close();
