<?php
header('Content-Type: application/json');
require 'db_connect.php';

// Check database connection
if (!$pdo) {
    echo json_encode(['error' => 'Kết nối cơ sở dữ liệu thất bại']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGet($pdo);
        break;
    case 'POST':
        handlePost($pdo);
        break;
    default:
        echo json_encode(['error' => 'Phương thức không được phép']);
        exit;
}

function handleGet($pdo) {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    $course = isset($_GET['course']) ? $_GET['course'] : 'all';
    $type = isset($_GET['type']) ? $_GET['type'] : 'all';
    $id = isset($_GET['id']) ? $_GET['id'] : null;

    try {
        if ($action === 'view' && $id) {
            // Fetch a single assignment
            $stmt = $pdo->prepare('SELECT * FROM assignments WHERE id = ?');
            $stmt->execute([$id]);
            $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($assignment) {
                echo json_encode(['success' => true, 'assignment' => $assignment]);
            } else {
                echo json_encode(['error' => 'Không tìm thấy bài tập với ID: ' . $id]);
            }
        } else {
            // Fetch all assignments with filters
            $query = 'SELECT * FROM assignments WHERE 1=1';
            $params = [];

            if ($course !== 'all') {
                $query .= ' AND course = ?';
                $params[] = $course;
            }
            if ($type !== 'all') {
                $query .= ' AND type = ?';
                $params[] = $type;
            }

            $query .= ' ORDER BY due_date ASC';
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log('Số lượng bài tập trả về: ' . count($assignments));
            if (empty($assignments)) {
                echo json_encode(['message' => 'Không có bài tập nào phù hợp với bộ lọc']);
            } else {
                echo json_encode($assignments);
            }
        }
    } catch (PDOException $e) {
        error_log('Lỗi SQL: ' . $e->getMessage());
        echo json_encode(['error' => 'Lỗi khi lấy danh sách bài tập: ' . $e->getMessage()]);
    }
}

function handlePost($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = isset($data['action']) ? $data['action'] : '';

    if ($action === 'add') {
        $title = isset($data['title']) ? trim($data['title']) : '';
        $course = isset($data['course']) ? trim($data['course']) : '';
        $type = isset($data['type']) ? trim($data['type']) : '';
        $dueDate = isset($data['due_date']) ? trim($data['due_date']) : '';
        $desc = isset($data['description']) ? trim($data['description']) : '';
        $points = isset($data['points']) ? (int)$data['points'] : 0;
        $status = isset($data['status']) ? trim($data['status']) : 'pending';

        if (empty($title) || empty($course) || empty($type) || empty($dueDate) || empty($desc)) {
            echo json_encode(['error' => 'Tất cả các trường đều bắt buộc']);
            exit;
        }

        $validCourses = ['001', '002', '003', '004', '005', '006', '007', '008'];
        if (!in_array($course, $validCourses)) {
            echo json_encode(['error' => 'Mã môn học không hợp lệ']);
            exit;
        }

        if (!in_array($type, ['assignment', 'quiz']) || !in_array($status, ['pending', 'completed'])) {
            echo json_encode(['error' => 'Loại hoặc trạng thái không hợp lệ']);
            exit;
        }

        try {
            $stmt = $pdo->prepare('INSERT INTO assignments (title, course, type, due_date, description, points, status, student_id) VALUES (?, ?, ?, ?, ?, ?, ?, NULL)');
            $stmt->execute([$title, $course, $type, $dueDate, $desc, $points, $status]);
            echo json_encode(['success' => true, 'message' => 'Thêm bài tập thành công']);
        } catch (PDOException $e) {
            error_log('Lỗi khi thêm bài tập: ' . $e->getMessage());
            echo json_encode(['error' => 'Lỗi khi thêm bài tập: ' . $e->getMessage()]);
        }
    } elseif ($action === 'update') {
        $id = isset($data['id']) ? $data['id'] : '';
        $title = isset($data['title']) ? trim($data['title']) : '';
        $course = isset($data['course']) ? trim($data['course']) : '';
        $type = isset($data['type']) ? trim($data['type']) : '';
        $dueDate = isset($data['due_date']) ? trim($data['due_date']) : '';
        $desc = isset($data['description']) ? trim($data['description']) : '';
        $points = isset($data['points']) ? (int)$data['points'] : 0;
        $status = isset($data['status']) ? trim($data['status']) : '';

        if (empty($id) || empty($title) || empty($course) || empty($type) || empty($dueDate) || empty($desc) || empty($status)) {
            echo json_encode(['error' => 'Tất cả các trường đều bắt buộc']);
            exit;
        }

        $validCourses = ['001', '002', '003', '004', '005', '006', '007', '008'];
        if (!in_array($course, $validCourses)) {
            echo json_encode(['error' => 'Mã môn học không hợp lệ']);
            exit;
        }

        if (!in_array($type, ['assignment', 'quiz']) || !in_array($status, ['pending', 'completed'])) {
            echo json_encode(['error' => 'Loại hoặc trạng thái không hợp lệ']);
            exit;
        }

        try {
            $stmt = $pdo->prepare('UPDATE assignments SET title = ?, course = ?, type = ?, due_date = ?, description = ?, points = ?, status = ? WHERE id = ?');
            $stmt->execute([$title, $course, $type, $dueDate, $desc, $points, $status, $id]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Cập nhật bài tập thành công']);
            } else {
                echo json_encode(['error' => 'Không tìm thấy bài tập']);
            }
        } catch (PDOException $e) {
            error_log('Lỗi khi cập nhật bài tập: ' . $e->getMessage());
            echo json_encode(['error' => 'Lỗi khi cập nhật bài tập: ' . $e->getMessage()]);
        }
    } elseif ($action === 'batch_update') {
        $ids = isset($data['ids']) ? $data['ids'] : [];
        $dueDate = isset($data['due_date']) ? trim($data['due_date']) : '';
        $status = isset($data['status']) ? trim($data['status']) : '';

        if (empty($ids) || !is_array($ids) || empty($dueDate) || empty($status)) {
            echo json_encode(['error' => 'Danh sách ID, ngày hết hạn và trạng thái là bắt buộc']);
            exit;
        }

        if (!in_array($status, ['pending', 'completed'])) {
            echo json_encode(['error' => 'Trạng thái không hợp lệ']);
            exit;
        }

        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $params = array_merge($ids, [$dueDate, $status]);
            $stmt = $pdo->prepare("UPDATE assignments SET due_date = ?, status = ? WHERE id IN ($placeholders)");
            $stmt->execute(array_merge([$dueDate, $status], $ids));
            echo json_encode(['success' => true, 'message' => 'Chỉnh sửa hàng loạt thành công']);
        } catch (PDOException $e) {
            error_log('Lỗi khi cập nhật hàng loạt: ' . $e->getMessage());
            echo json_encode(['error' => 'Lỗi khi cập nhật bài tập: ' . $e->getMessage()]);
        }
    } elseif ($action === 'delete') {
        $id = isset($data['id']) ? $data['id'] : '';

        if (empty($id)) {
            echo json_encode(['error' => 'Yêu cầu ID bài tập']);
            exit;
        }

        try {
            $stmt = $pdo->prepare('DELETE FROM assignments WHERE id = ?');
            $stmt->execute([$id]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Xóa bài tập thành công']);
            } else {
                echo json_encode(['error' => 'Không tìm thấy bài tập']);
            }
        } catch (PDOException $e) {
            error_log('Lỗi khi xóa bài tập: ' . $e->getMessage());
            echo json_encode(['error' => 'Lỗi khi xóa bài tập: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['error' => 'Hành động không hợp lệ']);
    }
}
?>