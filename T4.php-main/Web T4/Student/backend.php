<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Database configuration
$host = "localhost";
$dbname = "lms_uni";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Kết nối cơ sở dữ liệu thất bại: " . $e->getMessage()]);
    exit();
}

// Kiểm tra vai trò người dùng (giả sử thông qua session)
session_start();
$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'student'; // Mặc định là học sinh

// Handle API requests
$method = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];

// Chỉ cho phép học sinh sử dụng GET và POST cho nộp bài
if ($userRole === 'student' && !($method === 'GET' || ($method === 'POST' && strpos($requestUri, '/submit') !== false))) {
    http_response_code(403);
    echo json_encode(["error" => "Học sinh chỉ có quyền xem và nộp bài"]);
    exit();
}

switch ($method) {
    case 'GET':
        // Fetch all assignments or a specific assignment
        if (isset($_GET['id'])) {
            $query = "SELECT * FROM assignments WHERE id = :id";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':id' => $_GET['id']]);
            $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($assignment) {
                echo json_encode($assignment);
            } else {
                http_response_code(404);
                echo json_encode(["error" => "Không tìm thấy bài tập"]);
            }
        } else {
            $course = isset($_GET['course']) ? $_GET['course'] : 'all';
            $type = isset($_GET['type']) ? $_GET['type'] : 'all';

            $query = "SELECT * FROM assignments";
            $conditions = [];
            $params = [];

            if ($course !== 'all') {
                $conditions[] = "course = :course";
                $params[':course'] = $course;
            }
            if ($type !== 'all') {
                $conditions[] = "type = :type";
                $params[':type'] = $type;
            }

            if (!empty($conditions)) {
                $query .= " WHERE " . implode(" AND ", $conditions);
            }

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($assignments);
        }
        break;

    case 'POST':
        // Handle submission (cho học sinh)
        if (strpos($requestUri, '/submit') !== false) {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!isset($data['id'])) {
                http_response_code(400);
                echo json_encode(["error" => "Thiếu id bài tập"]);
                break;
            }

            // Kiểm tra xem bài tập đã được nộp chưa
            $query = "SELECT status FROM assignments WHERE id = :id";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':id' => $data['id']]);
            $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$assignment) {
                http_response_code(404);
                echo json_encode(["error" => "Không tìm thấy bài tập"]);
                break;
            }

            if ($assignment['status'] === 'completed') {
                http_response_code(400);
                echo json_encode(["error" => "Bài tập đã được nộp"]);
                break;
            }

            // Cập nhật trạng thái bài tập thành completed
            $query = "UPDATE assignments SET status = 'completed' WHERE id = :id";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':id' => $data['id']]);

            echo json_encode(["message" => "Nộp bài thành công"]);
            break;
        }

        // Add new assignment (chỉ cho giáo viên/admin)
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data['title'], $data['course'], $data['type'], $data['dueDate'], $data['desc'])) {
            http_response_code(400);
            echo json_encode(["error" => "Thiếu các trường bắt buộc"]);
            break;
        }

        $query = "INSERT INTO assignments (title, course, type, due_date, status, description, points) 
                  VALUES (:title, :course, :type, :dueDate, 'pending', :description, 0)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':title' => $data['title'],
            ':course' => $data['course'],
            ':type' => $data['type'],
            ':dueDate' => $data['dueDate'],
            ':description' => $data['desc']
        ]);

        echo json_encode(["id" => $pdo->lastInsertId(), "message" => "Thêm bài tập thành công"]);
        break;

    case 'PUT':
        // Update single or multiple assignments (chỉ cho giáo viên/admin)
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (isset($data['ids']) && is_array($data['ids'])) {
            // Batch update
            $query = "UPDATE assignments 
                      SET due_date = :dueDate, status = :status 
                      WHERE id IN (" . implode(',', array_fill(0, count($data['ids']), '?')) . ")";
            $stmt = $pdo->prepare($query);
            $params = array_merge($data['ids'], [':dueDate' => $data['dueDate'], ':status' => $data['status']]);
            $stmt->execute($params);
            echo json_encode(["message" => "Cập nhật hàng loạt bài tập thành công"]);
        } else if (isset($data['id'])) {
            // Single update
            if (!isset($data['id'], $data['title'], $data['course'], $data['type'], $data['dueDate'], $data['status'], $data['desc'], $data['points'])) {
                http_response_code(400);
                echo json_encode(["error" => "Thiếu các trường bắt buộc"]);
                break;
            }
            $query = "UPDATE assignments 
                      SET title = :title, course = :course, type = :type, due_date = :dueDate, 
                          status = :status, description = :description, points = :points 
                      WHERE id = :id";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                ':id' => $data['id'],
                ':title' => $data['title'],
                ':course' => $data['course'],
                ':type' => $data['type'],
                ':dueDate' => $data['dueDate'],
                ':status' => $data['status'],
                ':description' => $data['desc'],
                ':points' => $data['points']
            ]);
            echo json_encode(["message" => "Cập nhật bài tập thành công"]);
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Thiếu id hoặc danh sách id"]);
        }
        break;

    case 'DELETE':
        // Delete assignment (chỉ cho giáo viên/admin)
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(["error" => "Thiếu id"]);
            break;
        }

        $query = "DELETE FROM assignments WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':id' => $data['id']]);

        echo json_encode(["message" => "Xóa bài tập thành công"]);
        break;

    default:
        http_response_code(405);
        echo json_encode(["error" => "Phương thức không được phép"]);
        break;
}
?>