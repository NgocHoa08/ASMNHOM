document.addEventListener('DOMContentLoaded', function() {
    let currentCourseFilter = 'all';
    let currentTypeFilter = 'all';
    const apiUrl = 'backend.php';

    // Khởi tạo trang
    renderAssignments();
    setupEventListeners();

    // Modal cho Xem chi tiết
    const viewModal = document.getElementById('viewAssignmentModal');
    const closeViewModalBtn = document.getElementById('closeViewModal');
    const viewAssignmentTitle = document.getElementById('viewAssignmentTitle');
    const viewAssignmentCourse = document.getElementById('viewAssignmentCourse');
    const viewAssignmentType = document.getElementById('viewAssignmentType');
    const viewAssignmentDueDate = document.getElementById('viewAssignmentDueDate');
    const viewAssignmentDesc = document.getElementById('viewAssignmentDesc');
    const viewAssignmentPoints = document.getElementById('viewAssignmentPoints');
    const viewAssignmentStatus = document.getElementById('viewAssignmentStatus');

    // Mở modal Xem chi tiết
    function handleView(id) {
        fetch(`${apiUrl}?id=${id}`)
            .then(response => response.json())
            .then(assignment => {
                viewAssignmentTitle.textContent = assignment.title;
                viewAssignmentCourse.textContent = getCourseName(assignment.course);
                viewAssignmentType.textContent = assignment.type === 'assignment' ? 'Bài tập' : 'Bài kiểm tra';
                viewAssignmentDueDate.textContent = formatDate(assignment.due_date);
                viewAssignmentDesc.textContent = assignment.description;
                viewAssignmentPoints.textContent = assignment.points || 0;
                viewAssignmentStatus.textContent = assignment.status === 'completed' ? 'Đã hoàn thành' : 'Chưa hoàn thành';
                viewModal.style.display = 'block';
            })
            .catch(error => console.error('Lỗi khi lấy chi tiết bài tập:', error));
    }

    // Xử lý nộp bài
    function handleSubmit(id) {
        if (confirm('Bạn có chắc muốn nộp bài này?')) {
            fetch(`${apiUrl}/submit`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }
                alert('Nộp bài thành công!');
                renderAssignments(); // Tải lại danh sách để ẩn nút "Nộp bài"
            })
            .catch(error => console.error('Lỗi khi nộp bài:', error));
        }
    }

    closeViewModalBtn.addEventListener('click', function() {
        viewModal.style.display = 'none';
    });

    function setupEventListeners() {
        // Tìm kiếm môn học
        const searchInput = document.getElementById('courseSearch');
        searchInput.addEventListener('input', function() {
            filterCourses(this.value);
        });

        // Lọc theo môn học
        document.querySelectorAll('.filter-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.filter-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');
                currentCourseFilter = this.getAttribute('data-course');
                renderAssignments();
            });
        });

        // Lọc theo loại bài tập
        document.querySelectorAll('.type-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentTypeFilter = this.getAttribute('data-type');
                renderAssignments();
            });
        });

        // Đóng modal khi nhấp ra ngoài
        window.addEventListener('click', function(e) {
            if (e.target === viewModal) viewModal.style.display = 'none';
        });
    }

    function filterCourses(searchTerm) {
        const filterItems = document.querySelectorAll('.filter-item');
        const searchLower = searchTerm.toLowerCase();

        filterItems.forEach(item => {
            const courseText = item.textContent.toLowerCase();
            if (courseText.includes(searchLower)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }

    function renderAssignments() {
        const assignmentList = document.getElementById('assignmentList');
        const query = `?course=${currentCourseFilter}&type=${currentTypeFilter}`;

        fetch(apiUrl + query)
            .then(response => response.json())
            .then(assignments => {
                if (assignments.length === 0) {
                    assignmentList.innerHTML = '<p style="text-align: center; color: #666; padding: 40px;">Không có bài tập nào phù hợp với bộ lọc.</p>';
                    return;
                }

                assignmentList.innerHTML = assignments.map(assignment => `
                    <div class="assignment-item">
                        <div class="assignment-header">
                            <h3 class="assignment-title">${assignment.title}</h3>
                            <span class="assignment-type ${assignment.type}">
                                ${assignment.type === 'assignment' ? 'Bài tập' : 'Bài kiểm tra'}
                            </span>
                        </div>
                        <div class="assignment-meta">
                            <span><i class="fas fa-calendar"></i> Hạn nộp: ${formatDate(assignment.due_date)}</span>
                            <span><i class="fas fa-star"></i> Điểm: ${assignment.points}</span>
                            <span><i class="fas fa-circle ${assignment.status === 'completed' ? 'text-success' : 'text-warning'}"></i> 
                                ${assignment.status === 'completed' ? 'Đã hoàn thành' : 'Chưa hoàn thành'}
                            </span>
                        </div>
                        <div class="assignment-desc">${assignment.description}</div>
                        <div class="assignment-actions">
                            <button class="action-btn btn-secondary view-btn" data-id="${assignment.id}"><i class="fas fa-eye"></i> Xem</button>
                            ${assignment.status === 'pending' ? `<button class="action-btn btn-primary submit-btn" data-id="${assignment.id}"><i class="fas fa-upload"></i> Nộp bài</button>` : ''}
                        </div>
                    </div>
                `).join('');

                // Gán sự kiện cho các nút
                document.querySelectorAll('.view-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        handleView(this.getAttribute('data-id'));
                    });
                });
                document.querySelectorAll('.submit-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        handleSubmit(this.getAttribute('data-id'));
                    });
                });
            })
            .catch(error => console.error('Lỗi khi lấy danh sách bài tập:', error));
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('vi-VN');
    }

    function getCourseName(courseId) {
        const courseMap = {
            '001': 'Lập trình Web cơ bản',
            '002': 'Cơ sở dữ liệu',
            '003': 'Python cho AI',
            '004': 'Kỹ năng mềm',
            '005': 'Toán rời rạc',
            '006': 'Java nâng cao',
            '007': 'Phát triển ứng dụng di động',
            '008': 'Tiếng Anh chuyên ngành'
        };
        return courseMap[courseId] || 'Không xác định';
    }
});