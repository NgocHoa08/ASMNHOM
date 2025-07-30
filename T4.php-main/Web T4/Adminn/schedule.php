<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lịch học - LMS Uni</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="schedule.css">
</head>
<body>
  <header class="header">
    <div class="logo">
      <i class="fas fa-graduation-cap"></i>
      <h1>LMS Uni</h1>
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
          <!-- <a href="#">Thông tin</a>
          <a href="#">Liên hệ</a> -->
        </div>
      </button>
    </div>
  </header>

  <nav class="horizontal-nav">
    <div class="nav-container">
      <ul class="nav-menu">
        <li><a href="schedule.php" class="nav-link active"><i class="fas fa-book"></i> Trang Chủ</a></li>
        <li><a href="my-courses.php" class="nav-link"><i class="fas fa-calendar-alt"></i> Khóa học của tôi</a></li>
        <li><a href="assignment.php" class="nav-link"><i class="fas fa-tasks"></i> Bài tập</a></li>
        <li><a href="discussion.php" class="nav-link"><i class="fas fa-comments"></i> Thảo luận</a></li>
      </ul>
    </div>
  </nav>

  <section class="schedule-section">
    <div class="schedule-container">
      <div class="schedule-header-section">
        <h2 class="schedule-title">Lịch học tuần này</h2>
        <button class="request-change-btn" onclick="openRequestModal('add')">
          <i class="fas fa-plus"></i> Thêm lịch học
        </button>
      </div>
      <div class="schedule-table">
        <div class="schedule-header">
          <div class="time-column">Thời gian</div>
          <div class="day-column">Thứ 2</div>
          <div class="day-column">Thứ 3</div>
          <div class="day-column">Thứ 4</div>
          <div class="day-column">Thứ 5</div>
          <div class="day-column">Thứ 6</div>
          <div class="day-column">Thứ 7</div>
        </div>
        <div class="schedule-body"><!-- Nội dung lịch học sẽ được render bằng JavaScript --></div>
      </div>

      <!-- Modal quản lý lịch học -->
      <div id="requestModal" class="modal">
        <div class="modal-content request-modal-content">
          <div class="modal-header">
            <h3 id="modalTitle">Quản lý lịch học</h3>
            <button class="close-modal" onclick="closeRequestModal()"><i class="fas fa-times"></i></button>
          </div>
          <form id="requestForm" onsubmit="submitRequest(event)">
            <input type="hidden" id="requestType">
            <input type="hidden" id="requestTimeSlot">
            <input type="hidden" id="requestDay">

            <div class="form-group">
              <label for="requestCourse">Mã môn học:</label>
              <input type="text" id="requestCourse" required>
            </div>
            <div class="form-group">
              <label for="requestTitle">Tên môn học:</label>
              <input type="text" id="requestTitle" required>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="requestRoom">Phòng học:</label>
                <input type="text" id="requestRoom" required>
              </div>
              <div class="form-group">
                <label for="requestInstructor">Giảng viên:</label>
                <input type="text" id="requestInstructor" required>
              </div>
            </div>

            <div class="form-group" id="reasonGroup">
              <label for="requestReason">Lý do:</label>
              <textarea id="requestReason" rows="3" placeholder="Lý do thay đổi..."></textarea>
            </div>

            <div class="form-actions">
              <button type="button" class="btn-cancel" onclick="closeRequestModal()">Hủy</button>
              <button type="submit" class="btn-save">Lưu</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>

  <footer class="footer">
    <div class="footer-content">
      <p>&copy; 2025 Hệ thống Quản lý Học tập - Đại học Công nghệ</p>
      <div class="footer-links">
        <a href="#">Về chúng tôi</a>
        <a href="#">Hỗ trợ</a>
        <a href="#">Chính sách bảo mật</a>
      </div>
    </div>
  </footer>

  <script src="script.js"></script>
  <script src="schedule.js"></script>
</body>
</html>
