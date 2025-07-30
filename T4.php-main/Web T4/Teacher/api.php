<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
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
} elseif ($method === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare('INSERT INTO courses (code, title, description, instructor, image) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$data['code'], $data['title'], $data['description'], $data['instructor'], $data['image']]);
        $courseId = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare('INSERT INTO teacher_courses (teacher_id, course_id) VALUES (?, ?)');
        $stmt->execute([$teacherId, $courseId]);
        
        echo json_encode(['status' => 'success', 'message' => 'Khóa học đã được thêm thành công!', 'id' => $courseId]);
    } catch (PDOException $e) {
        error_log("Insert failed at " . date('Y-m-d H:i:s') . ": " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Lỗi khi thêm khóa học: ' . $e->getMessage()]);
    }
} elseif ($method === 'PUT') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data['id']) {
            throw new PDOException('ID khóa học không được cung cấp');
        }
        $stmt = $pdo->prepare('UPDATE courses SET code = ?, title = ?, description = ?, instructor = ?, image = ? WHERE id = ?');
        $stmt->execute([$data['code'], $data['title'], $data['description'], $data['instructor'], $data['image'], $data['id']]);
        if ($stmt->rowCount() === 0) {
            throw new PDOException('Không tìm thấy khóa học để cập nhật');
        }
        echo json_encode(['status' => 'success', 'message' => 'Khóa học đã được cập nhật thành công!']);
    } catch (PDOException $e) {
        error_log("Update failed at " . date('Y-m-d H:i:s') . ": " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Lỗi khi cập nhật khóa học: ' . $e->getMessage()]);
    }
} elseif ($method === 'DELETE') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare('DELETE FROM teacher_courses WHERE course_id = ? AND teacher_id = ?');
        $stmt->execute([$data['id'], $teacherId]);
        $stmt = $pdo->prepare('DELETE FROM courses WHERE id = ?');
        $stmt->execute([$data['id']]);
        echo json_encode(['status' => 'success', 'message' => 'Khóa học đã được xóa thành công!']);
    } catch (PDOException $e) {
        error_log("Delete failed at " . date('Y-m-d H:i:s') . ": " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Lỗi khi xóa khóa học: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không được hỗ trợ!']);
}
?>