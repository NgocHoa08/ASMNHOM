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

// Truy xuất tất cả yêu cầu
try {
    $stmt = $pdo->query("SELECT id, type, time_slot AS timeSlot, day, course, title, room, instructor, reason, created_at AS createdAt 
                         FROM schedule_requests 
                         ORDER BY created_at DESC");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Định dạng createdAt
    foreach ($requests as &$request) {
        $request['createdAt'] = date('d/m/Y H:i:s', strtotime($request['createdAt']));
    }

    echo json_encode([
        'success' => true,
        'requests' => $requests
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể truy xuất yêu cầu: ' . $e->getMessage()]);
}
?>