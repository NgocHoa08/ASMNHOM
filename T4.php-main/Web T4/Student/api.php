<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

$host = 'localhost';
$dbname = 'lms_uni';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed at " . date('Y-m-d H:i:s') . ": " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Kết nối cơ sở dữ liệu thất bại']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Sử dụng teacher_id cố định vì không cần đăng nhập
$teacherId = 1;

if ($method === 'GET') {
    try {
        $stmt = $pdo->prepare('SELECT c.* FROM courses c JOIN teacher_courses tc ON c.id = tc.course_id WHERE tc.teacher_id = ?');
        $stmt->execute([$teacherId]);
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($courses)) {
            error_log("No courses found for teacher_id: $teacherId at " . date('Y-m-d H:i:s'));
            echo json_encode(['status' => 'success', 'data' => []]);
        } else {
            echo json_encode(['status' => 'success', 'data' => $courses]);
        }
    } catch (PDOException $e) {
        error_log("Query failed at " . date('Y-m-d H:i:s') . ": " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Lỗi truy vấn dữ liệu']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không được hỗ trợ!']);
}
?>