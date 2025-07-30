<?php
header("Content-Type: application/json");
$host = "localhost";
$db = "lms_uni";
$user = "root";
$pass = "";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Kết nối thất bại"]);
    exit;
}

$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $result = $conn->query("SELECT * FROM schedule");
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data);
}

if ($method === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    $day = $data["day"];
    $slot = $data["time_slot"];
    $course = $conn->real_escape_string($data["course_code"]);
    $title = $conn->real_escape_string($data["course_title"]);
    $room = $conn->real_escape_string($data["room"]);
    $instructor = $conn->real_escape_string($data["instructor"]);

    $conn->query("INSERT INTO schedule (day, time_slot, course_code, course_title, room, instructor)
                  VALUES ($day, $slot, '$course', '$title', '$room', '$instructor')");
    echo json_encode(["message" => "Đã thêm"]);
}

if ($method === "PUT") {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = $data["id"];
    $course = $conn->real_escape_string($data["course_code"]);
    $title = $conn->real_escape_string($data["course_title"]);
    $room = $conn->real_escape_string($data["room"]);
    $instructor = $conn->real_escape_string($data["instructor"]);

    $conn->query("UPDATE schedule SET 
        course_code='$course',
        course_title='$title',
        room='$room',
        instructor='$instructor'
        WHERE id=$id");
    echo json_encode(["message" => "Đã cập nhật"]);
}

if ($method === "DELETE") {
    $id = intval($_GET["id"]);
    $conn->query("DELETE FROM schedule WHERE id=$id");
    echo json_encode(["message" => "Đã xóa"]);
}

$conn->close();
