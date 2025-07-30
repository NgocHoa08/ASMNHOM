<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Khóa học của tôi - LMS Uni</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="my-courses.css">
</head>
<body>
    <header class="header">
        <div class="logo">
            <i class="fas fa-graduation-cap"></i>
            <h1>LMS Uni</h1>
        </div>
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="Tìm kiếm khóa học, tài liệu..." onkeyup="searchCourses()">
            <button onclick="searchCourses()"><i class="fas fa-search"></i></button>
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
            <div class="user-profile user-menu-container">
                <i class="fas fa-user"></i>
                <div class="user-dropdown-menu">
                    <a href="../Login/Login.php">Đăng nhập</a>
                    <a href="#">Đăng ký</a>
                </div>
            </div>
            <button class="settings-btn settings-menu-container">
                <i class="fas fa-cog"></i>
                <div class="settings-dropdown-menu">
                    <a href="#">Thông tin</a>
                    <a href="#">Liên hệ</a>
                </div>
            </button>
        </div>
    </header>

    <nav class="horizontal-nav">
        <div class="nav-container">
            <ul class="nav-menu">
                <li><a href="my-courses.php" class="nav-link active"><i class="fas fa-calendar-alt"></i> Khóa học của tôi</a></li>
            </ul>
        </div>
    </nav>

    <section class="courses-section">
        <div class="courses-header">
            <h2 class="courses-title">Khóa học của tôi</h2>
        </div>
        <div class="courses-grid" id="coursesGrid">
            <?php
            $host = 'localhost';
            $dbname = 'lms_uni';
            $username = 'root';
            $password = '';

            try {
                $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $stmt = $pdo->query("SELECT * FROM courses");
                $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                echo '<p style="text-align: center; color: #dc3545; padding: 40px;">Lỗi: Không thể tải danh sách khóa học - ' . htmlspecialchars($e->getMessage()) . '</p>';
                $courses = [];
            }
            ?>
        </div>
    </section>

    <footer class="footer">
        <div class="footer-content">
            <p>© 2025 Hệ thống Quản lý Học tập - Đại học Công nghệ</p>
            <div class="footer-links">
                <a href="#">Về chúng tôi</a>
                <a href="#">Hỗ trợ</a>
                <a href="#">Chính sách bảo mật</a>
            </div>
        </div>
    </footer>

    <script>
        function searchCourses() {
            let input = document.getElementById('searchInput').value.toLowerCase();
            let courses = <?php echo json_encode($courses); ?>;
            let coursesGrid = document.getElementById('coursesGrid');
            coursesGrid.innerHTML = '';

            let filteredCourses = courses.filter(course =>
                (course.title && course.title.toLowerCase().includes(input)) ||
                (course.code && course.code.toLowerCase().includes(input)) ||
                (course.instructor && course.instructor.toLowerCase().includes(input))
            );

            if (filteredCourses.length > 0) {
                filteredCourses.forEach(course => {
                    coursesGrid.innerHTML += `
                        <div class="course-card">
                            <img class="course-img" src="${course.image || '/amsdl/teacher/images/default.jpg'}" alt="${course.title || 'Khóa học'}">
                            <div class="course-info">
                                <div class="course-title">${course.code || 'N/A'} - ${course.title || 'Chưa có tiêu đề'}</div>
                                <div class="course-desc">${course.description || 'Chưa có mô tả'}</div>
                                <div class="course-meta">Giảng viên: ${course.instructor || 'Chưa có thông tin'}</div>
                            </div>
                            <button class="course-view-btn" onclick="alert('Xem khóa học: ${course.title}')">Xem</button>
                        </div>
                    `;
                });
            } else {
                coursesGrid.innerHTML = '<p style="text-align: center; color: #666; padding: 40px;">Không tìm thấy khóa học nào.</p>';
            }
        }

        // Hiển thị tất cả khóa học khi trang tải
        window.onload = function() {
            searchCourses();
        };
    </script>
</body>
</html>