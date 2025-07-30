document.addEventListener('DOMContentLoaded', function() {
    let courses = [];
    let allCourses = []; // Lưu trữ tất cả khóa học để tìm kiếm

    function fetchCourses() {
        fetch('./api.php')
            .then(response => {
                if (!response.ok) throw new Error('Lỗi kết nối server: ' + response.statusText);
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    courses = data.data;
                    allCourses = [...courses]; // Sao chép dữ liệu ban đầu
                    console.log('Danh sách khóa học:', courses); // Debug
                    renderCourses(courses);
                } else {
                    showNotification(data.message || 'Lỗi khi tải danh sách khóa học!', 'error');
                    console.error('API error:', data.message);
                }
            })
            .catch(error => {
                showNotification('Lỗi: ' + error.message, 'error');
                console.error('Fetch error:', error);
            });
    }

    function renderCourses(courses) {
        const coursesGrid = document.getElementById('coursesGrid');
        coursesGrid.innerHTML = courses.map(course => `
            <div class="course-card" data-id="${course.id}">
                <div class="course-actions">
                    <button class="edit-course-btn" title="Sửa khóa học" onclick="editCourse(${course.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="details-btn" title="Chi tiết khóa học" onclick="showCourseDetails(${course.id})">
                        <i class="fas fa-info-circle"></i>
                    </button>
                    <button class="delete-btn" title="Xóa khóa học" onclick="confirmDeleteCourse(${course.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <img class="course-img" src="${course.image || 'images/default.jpg'}" alt="${course.title}" onerror="this.src='images/default.jpg'">
                <div class="course-title"><span class="course-code">${course.code}</span> - ${course.title}</div>
                <div class="course-desc">${course.description || 'Chưa có mô tả'}</div>
                <div class="course-meta">Giảng viên: ${course.instructor}</div>
            </div>
        `).join('');
    }

    function searchCourses() {
        const searchTerm = document.getElementById('courseSearch').value.trim().toLowerCase();
        if (!searchTerm) {
            courses = [...allCourses];
            renderCourses(courses);
            return;
        }

        courses = allCourses.filter(course => 
            course.title.toLowerCase().includes(searchTerm) ||
            course.code.toLowerCase().includes(searchTerm) ||
            course.instructor.toLowerCase().includes(searchTerm) ||
            (course.description && course.description.toLowerCase().includes(searchTerm))
        );
        
        renderCourses(courses);
        showNotification(`Đã tìm thấy ${courses.length} khóa học phù hợp`, 'info');
    }

    function openCourseModal() {
        document.getElementById('courseModalTitle').textContent = 'Thêm khóa học mới';
        document.getElementById('courseId').value = '';
        document.getElementById('courseForm').reset();
        document.getElementById('courseModal').style.display = 'flex';
    }

    function editCourse(id) {
        console.log('Tìm khóa học với ID:', id); // Debug
        console.log('Danh sách hiện tại:', courses); // Debug
        const course = courses.find(c => c.id === id);
        if (course) {
            document.getElementById('courseModalTitle').textContent = 'Sửa khóa học';
            document.getElementById('courseId').value = course.id;
            document.getElementById('courseCode').value = course.code || '';
            document.getElementById('courseTitle').value = course.title || '';
            document.getElementById('courseDesc').value = course.description || '';
            document.getElementById('courseInstructor').value = course.instructor || '';
            document.getElementById('courseImage').value = course.image || '';
            document.getElementById('courseModal').style.display = 'flex';
        } else {
            showNotification('Không tìm thấy khóa học!', 'error');
            console.error('Không tìm thấy khóa học với ID:', id);
        }
    }

    function showCourseDetails(id) {
        const course = courses.find(c => c.id === id);
        if (course) {
            showCustomConfirm(
                `<strong>Chi tiết khóa học:</strong><br>
                Mã: ${course.code}<br>
                Tên: ${course.title}<br>
                Mô tả: ${course.description || 'Chưa có mô tả'}<br>
                Giảng viên: ${course.instructor}<br>
                Hình ảnh: ${course.image || 'Không có'}<br><br>
                <button style="margin-top: 10px; padding: 5px 10px; background: #0074D9; color: white; border: none; border-radius: 5px;" onclick="editCourse(${course.id}); this.closest('.custom-confirm-overlay').remove();">Sửa</button>
                <button style="margin-top: 10px; padding: 5px 10px; background: #ff6b6b; color: white; border: none; border-radius: 5px; margin-left: 5px;" onclick="confirmDeleteCourse(${course.id}); this.closest('.custom-confirm-overlay').remove();">Xóa</button>`,
                () => {}
            );
        } else {
            showNotification('Không tìm thấy khóa học!', 'error');
        }
    }

    function confirmDeleteCourse(id) {
        showCustomConfirm(
            `Bạn có chắc chắn muốn xóa khóa học này?`,
            () => deleteCourse(id)
        );
    }

    function deleteCourse(id) {
        console.log('Thực hiện xóa với ID:', id); // Debug
        fetch('./api.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        })
        .then(response => {
            if (!response.ok) throw new Error('Lỗi xóa: ' + response.statusText);
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                courses = courses.filter(c => c.id !== id);
                allCourses = allCourses.filter(c => c.id !== id);
                renderCourses(courses);
                showNotification('Đã xóa khóa học thành công!', 'success');
            } else {
                showNotification(data.message || 'Lỗi khi xóa khóa học!', 'error');
            }
        })
        .catch(error => {
            showNotification('Lỗi: ' + error.message, 'error');
            console.error('Delete error:', error);
        });
    }

    function saveCourse(event) {
        event.preventDefault();
        
        const id = document.getElementById('courseId').value;
        const courseData = {
            id: id ? parseInt(id) : null,
            code: document.getElementById('courseCode').value.trim(),
            title: document.getElementById('courseTitle').value.trim(),
            description: document.getElementById('courseDesc').value.trim(),
            instructor: document.getElementById('courseInstructor').value.trim(),
            image: document.getElementById('courseImage').value.trim() || ''
        };

        // Kiểm tra dữ liệu đầu vào
        if (!courseData.code || !courseData.title || !courseData.instructor) {
            showNotification('Vui lòng điền đầy đủ mã khóa học, tên khóa học và giảng viên!', 'error');
            return;
        }

        // Kiểm tra mã khóa học duy nhất
        if (!id && allCourses.some(c => c.code === courseData.code)) {
            showNotification('Mã khóa học đã tồn tại!', 'error');
            return;
        }

        // Kiểm tra định dạng URL hình ảnh (nếu có)
        if (courseData.image && !isValidUrl(courseData.image)) {
            showNotification('URL hình ảnh không hợp lệ!', 'error');
            return;
        }

        const method = id ? 'PUT' : 'POST';
        fetch('./api.php', {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(courseData)
        })
        .then(response => {
            if (!response.ok) throw new Error('Lỗi lưu: ' + response.statusText);
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                fetchCourses(); // Cập nhật danh sách khóa học
                showNotification(id ? 'Đã cập nhật khóa học thành công!' : 'Đã thêm khóa học mới thành công!', 'success');
                closeCourseModal();
            } else {
                showNotification(data.message || 'Lỗi khi lưu khóa học!', 'error');
            }
        })
        .catch(error => {
            showNotification('Lỗi: ' + error.message, 'error');
            console.error('Save error:', error);
        });
    }

    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }

    function closeCourseModal() {
        document.getElementById('courseModal').style.display = 'none';
    }

    function showCustomConfirm(message, onConfirm) {
        const confirmDialog = document.createElement('div');
        confirmDialog.className = 'custom-confirm-overlay';
        confirmDialog.innerHTML = `
            <div class="custom-confirm-dialog">
                <div class="confirm-header">
                    <i class="fas fa-info-circle"></i>
                    <h3>${message.includes('Chi tiết') ? 'Chi tiết' : 'Xác nhận'}</h3>
                </div>
                <div class="confirm-content">
                    <p>${message}</p>
                </div>
                <div class="confirm-actions">
                    <button class="btn-cancel-confirm" onclick="this.closest('.custom-confirm-overlay').remove()">
                        <i class="fas fa-times"></i> Đóng
                    </button>
                    ${message.includes('Chi tiết') ? '' : `
                    <button class="btn-confirm-delete" onclick="executeConfirmCallback(); this.closest('.custom-confirm-overlay').remove()">
                        <i class="fas fa-trash"></i> Xóa
                    </button>
                    `}
                </div>
            </div>
        `;
        
        document.body.appendChild(confirmDialog);
        window.executeConfirmCallback = onConfirm;
        
        setTimeout(() => {
            confirmDialog.style.opacity = '1';
            confirmDialog.querySelector('.custom-confirm-dialog').style.transform = 'scale(1)';
        }, 10);
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

    function setupEventListeners() {
        document.getElementById('courseModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCourseModal();
            }
        });
        
        // Thêm sự kiện cho input tìm kiếm
        document.getElementById('courseSearch').addEventListener('input', searchCourses);
        document.getElementById('courseSearch').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchCourses();
            }
        });
    }

    fetchCourses();
    setupEventListeners();

    window.openCourseModal = openCourseModal;
    window.editCourse = editCourse;
    window.deleteCourse = deleteCourse;
    window.closeCourseModal = closeCourseModal;
    window.saveCourse = saveCourse;
    window.showCourseDetails = showCourseDetails;
    window.searchCourses = searchCourses;
    window.confirmDeleteCourse = confirmDeleteCourse;
});