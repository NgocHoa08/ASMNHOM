<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");
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

// Handle API requests
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Fetch messages for a specific teacher or all messages
        $teacherId = isset($_GET['teacherId']) ? $_GET['teacherId'] : null;
        $query = "SELECT m.*, t.name as teacher_name FROM messages m 
                  JOIN teachers t ON m.teacher_id = t.id";
        $params = [];
        if ($teacherId) {
            $query .= " WHERE m.teacher_id = :teacherId";
            $params[':teacherId'] = $teacherId;
        }
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($messages);
        break;

    case 'POST':
        // Add new message
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data['teacherId'], $data['content'], $data['userId'])) {
            http_response_code(400);
            echo json_encode(["error" => "Thiếu các trường bắt buộc"]);
            break;
        }

        $query = "INSERT INTO messages (teacher_id, user_id, content, created_at) 
                  VALUES (:teacherId, :userId, :content, NOW())";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':teacherId' => $data['teacherId'],
            ':userId' => $data['userId'],
            ':content' => $data['content']
        ]);

        echo json_encode(["id" => $pdo->lastInsertId(), "message" => "Tin nhắn đã được gửi"]);
        break;

    default:
        http_response_code(405);
        echo json_encode(["error" => "Phương thức không được phép"]);
        break;
}
?>