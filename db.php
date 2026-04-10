
<?php
$host = getenv('MYSQLHOST');
$port = getenv('MYSQLPORT') ?: '3306';
$user = getenv('MYSQLUSER');
$pass = getenv('MYSQLPASSWORD');
// Railway иногда называет по-разному
$db   = getenv('MYSQLDATABASE') 
     ?: getenv('MYSQL_DATABASE')
     ?: getenv('MYSQL_DB')
     ?: 'railway';  // Railway по умолчанию создаёт базу с именем "railway"

$conn = new mysqli($host, $user, $pass, $db, (int)$port);
if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(['error' => 'DB failed: ' . $conn->connect_error, 'db_used' => $db]));
}
$conn->set_charset('utf8mb4');
?>
