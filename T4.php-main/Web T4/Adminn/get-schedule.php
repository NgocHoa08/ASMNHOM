<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

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

// Truy xuất lịch học
try {
    $stmt = $pdo->query("SELECT id, time_slot AS timeSlot, day, course, title, room, instructor 
                         FROM schedules 
                         ORDER BY time_slot, day");
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Sắp xếp thành lưới 5x6 (5 khung giờ, 6 ngày)
    $scheduleGrid = array_fill(0, 5, array_fill(0, 6, null));
    foreach ($schedules as $slot) {
        $scheduleGrid[$slot['timeSlot']][$slot['day']] = [
            'id' => $slot['id'],
            'course' => $slot['course'],
            'title' => $slot['title'],
            'room' => $slot['room'],
            'instructor' => $slot['instructor']
        ];
    }

    echo json_encode([
        'success' => true,
        'timeSlots' => ["7:00 - 8:30", "8:45 - 10:15", "10:30 - 12:00", "13:30 - 15:00", "15:00 - 17:00"],
        'days' => ["Thứ 2", "Thứ 3", "Thứ 4", "Thứ 5", "Thứ 6", "Thứ 7"],
        'schedule' => $scheduleGrid
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể truy xuất lịch học: ' . $e->getMessage()]);
}
?>