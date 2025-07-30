<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Kết nối cơ sở dữ liệu
$host = 'localhost';
$dbname = 'lms_uni';
$username = 'root'; // Thay bằng tên người dùng MySQL của bạn
$password = ''; // Thay bằng mật khẩu MySQL của bạn

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kết nối cơ sở dữ liệu thất bại: ' . $e->getMessage()]);
    exit;
}

// Nhận dữ liệu POST
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu JSON không hợp lệ']);
    exit;
}

// Xác thực dữ liệu đầu vào
$required_fields = ['type', 'timeSlot', 'day', 'course', 'title', 'room', 'instructor', 'reason'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty(trim($data[$field]))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Thiếu hoặc trống trường: $field"]);
        exit;
    }
}

$type = $data['type'];
$timeSlot = (int)$data['timeSlot'];
$day = (int)$data['day'];
$course = trim($data['course']);
$title = trim($data['title']);
$room = trim($data['room']);
$instructor = trim($data['instructor']);
$reason = trim($data['reason']);
$id = (int)microtime(true) * 1000;

// Xác thực loại yêu cầu và phạm vi số
if (!in_array($type, ['add', 'edit', 'delete'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Loại yêu cầu không hợp lệ']);
    exit;
}
if ($timeSlot < 0 || $timeSlot > 4 || $day < 0 || $day > 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Khung giờ hoặc ngày không hợp lệ']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Xử lý thay đổi lịch học dựa trên loại yêu cầu
    if ($type === 'add' || $type === 'edit') {
        // Kiểm tra xem khung giờ đã được sử dụng chưa
        $stmt = $pdo->prepare("SELECT id FROM schedules WHERE time_slot = :time_slot AND day = :day");
        $stmt->execute(['time_slot' => $timeSlot, 'day' => $day]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($type === 'add' && $existing) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Khung giờ đã được sử dụng']);
            exit;
        }

        // Thêm hoặc cập nhật lịch học
        $stmt = $pdo->prepare("
            INSERT INTO schedules (time_slot, day, course, title, room, instructor)
            VALUES (:time_slot, :day, :course, :title, :room, :instructor)
            ON DUPLICATE KEY UPDATE
                course = VALUES(course),
                title = VALUES(title),
                room = VALUES(room),
                instructor = VALUES(instructor)
        ");
        $stmt->execute([
            'time_slot' => $timeSlot,
            'day' => $day,
            'course' => $course,
            'title' => $title,
            'room' => $room,
            'instructor' => $instructor
        ]);
    } elseif ($type === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM schedules WHERE time_slot = :time_slot AND day = :day");
        $stmt->execute(['time_slot' => $timeSlot, 'day' => $day]);
        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy lịch học để xóa']);
            exit;
        }
    }

    // Ghi lại yêu cầu
    $stmt = $pdo->prepare("
        INSERT INTO schedule_requests (id, type, time_slot, day, course, title, room, instructor, reason, created_at)
        VALUES (:id, :type, :time_slot, :day, :course, :title, :room, :instructor, :reason, NOW())
    ");
    $stmt->execute([
        'id' => $id,
        'type' => $type,
        'time_slot' => $timeSlot,
        'day' => $day,
        'course' => $course,
        'title' => $title,
        'room' => $room,
        'instructor' => $instructor,
        'reason' => $reason
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Yêu cầu đã được xử lý và lịch học đã được cập nhật!',
        'request' => [
            'id' => $id,
            'type' => $type,
            'timeSlot' => $timeSlot,
            'day' => $day,
            'course' => $course,
            'title' => $title,
            'room' => $room,
            'instructor' => $instructor,
            'reason' => $reason,
            'createdAt' => date('Y-m-d H:i:s')
        ]
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể xử lý yêu cầu: ' . $e->getMessage()]);
}
?>