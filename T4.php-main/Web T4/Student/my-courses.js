document.addEventListener('DOMContentLoaded', function() {
    let courses = [];

    function fetchCourses() {
        fetch('/amsdl/teacher/api.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status} - ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('API Response:', data); // Debug
            if (data.status === 'success') {
                courses = Array.isArray(data.data) ? data.data : [];
                renderCourses(courses);
            } else {
                showNotification(data.message || 'Lỗi khi tải danh sách khóa học!', 'error');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            showNotification('Lỗi: Không thể kết nối đến máy chủ - ' + error.message, 'error');
        });
    }

    function renderCourses(courses) {
        const coursesGrid = document.getElementById('coursesGrid');
        if (!coursesGrid) {
            console.error('Element #coursesGrid not found');
            showNotification('Lỗi: Phần tử hiển thị khóa học không tồn tại!', 'error');
            return;
        }
        if (courses.length === 0) {
            coursesGrid.innerHTML = '<p style="text-align: center; color: #666; padding: 40px;">Không có khóa học nào.</p>';
            return;
        }
        coursesGrid.innerHTML = courses.map(course => `
            <div class="course-card">
                <img class="course-img" src="${course.image || '/amsdl/teacher/images/default.jpg'}" alt="${course.title || 'Khóa học'}">
                <div class="course-info">
                    <div class="course-title">${course.code || 'N/A'} - ${course.title || 'Chưa có tiêu đề'}</div>
                    <div class="course-desc">${course.description || 'Chưa có mô tả'}</div>
                    <div class="course-meta">Giảng viên: ${course.instructor || 'Chưa có thông tin'}</div>
                </div>
                <button class="course-view-btn" disabled>Xem</button>
            </div>
        `).join('');
    }

    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'times-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        `;
        document.body.appendChild(notification);
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    fetchCourses();
});