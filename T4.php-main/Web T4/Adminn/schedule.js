document.addEventListener('DOMContentLoaded', function () {
    const scheduleData = {
        timeSlots: [
            "7:00 - 8:30",
            "8:45 - 10:15",
            "10:30 - 12:00",
            "13:30 - 15:00",
            "15:00 - 17:00"
        ],
        schedule: Array.from({ length: 5 }, () => Array(6).fill(null))
    };

    loadSchedule();

    async function loadSchedule() {
        try {
            const res = await fetch('schedule-api.php');
            const data = await res.json();
            data.forEach(item => {
                const row = parseInt(item.time_slot);
                const col = parseInt(item.day);
                scheduleData.schedule[row][col] = {
                    id: item.id,
                    course: item.course_code,
                    title: item.course_title,
                    room: item.room,
                    instructor: item.instructor
                };
            });
            renderSchedule();
        } catch (error) {
            console.error(error);
            showNotification('Không thể tải dữ liệu!', 'error');
        }
    }

    function openRequestModal(type, timeSlot, day, courseData = null) {
        document.getElementById('requestModal').style.display = 'flex';
        document.getElementById('requestType').value = type;
        document.getElementById('requestTimeSlot').value = timeSlot;
        document.getElementById('requestDay').value = day;
        document.getElementById('requestCourse').value = courseData?.course || '';
        document.getElementById('requestTitle').value = courseData?.title || '';
        document.getElementById('requestRoom').value = courseData?.room || '';
        document.getElementById('requestInstructor').value = courseData?.instructor || '';
        document.getElementById('reasonGroup').style.display = type === 'add' ? 'block' : 'none';
    }

    function closeRequestModal() {
        document.getElementById('requestModal').style.display = 'none';
    }

    async function submitRequest(e) {
        e.preventDefault();
        const type = document.getElementById('requestType').value;
        const row = parseInt(document.getElementById('requestTimeSlot').value);
        const col = parseInt(document.getElementById('requestDay').value);
        const course_code = document.getElementById('requestCourse').value;
        const course_title = document.getElementById('requestTitle').value;
        const room = document.getElementById('requestRoom').value;
        const instructor = document.getElementById('requestInstructor').value;
        const slotData = scheduleData.schedule[row][col];

        try {
            if (type === 'add') {
                if (slotData) return showNotification('Ô đã có lịch!', 'error');
                const res = await fetch('schedule-api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ day: col, time_slot: row, course_code, course_title, room, instructor })
                });
                const result = await res.json();
                if (res.ok) {
                    showNotification('Đã thêm lịch học!', 'success');
                    closeRequestModal();
                    loadSchedule();
                } else {
                    showNotification(result.message || 'Lỗi khi thêm!', 'error');
                }
            }

            if (type === 'edit') {
                if (!slotData) return showNotification('Không có dữ liệu để sửa!', 'error');
                const res = await fetch('schedule-api.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: slotData.id, course_code, course_title, room, instructor })
                });
                const result = await res.json();
                if (res.ok) {
                    showNotification('Đã cập nhật lịch học!', 'success');
                    closeRequestModal();
                    loadSchedule();
                } else {
                    showNotification(result.message || 'Lỗi khi sửa!', 'error');
                }
            }

            if (type === 'delete') {
                if (!slotData) return showNotification('Không có dữ liệu để xóa!', 'error');
                if (!confirm('Bạn chắc chắn muốn xóa?')) return;
                const res = await fetch(`schedule-api.php?id=${slotData.id}`, {
                    method: 'DELETE'
                });
                const result = await res.json();
                if (res.ok) {
                    showNotification('Đã xóa lịch học!', 'success');
                    closeRequestModal();
                    loadSchedule();
                } else {
                    showNotification(result.message || 'Lỗi khi xóa!', 'error');
                }
            }
        } catch (err) {
            console.error(err);
            showNotification('Lỗi kết nối máy chủ!', 'error');
        }
    }

    function renderSchedule() {
        const body = document.querySelector('.schedule-body');
        body.innerHTML = '';
        scheduleData.schedule.forEach((row, rowIndex) => {
            const rowDiv = document.createElement('div');
            rowDiv.className = 'schedule-row';
            rowDiv.innerHTML = `<div class="time-slot">${scheduleData.timeSlots[rowIndex]}</div>`;
            row.forEach((cell, colIndex) => {
                const cellDiv = document.createElement('div');
                cellDiv.className = 'class-slot';
                if (!cell) {
                    cellDiv.classList.add('empty');
                    cellDiv.innerHTML = `
                        <button class="add-btn" onclick="openRequestModal('add', ${rowIndex}, ${colIndex})">
                            <i class="fas fa-plus"></i>
                        </button>`;
                } else {
                    cellDiv.innerHTML = `
                        <div class="schedule-content">
                            ${cell.course} - ${cell.title}<br>
                            <small>Phòng ${cell.room} | GV: ${cell.instructor}</small>
                            <div class="slot-actions">
                                <button class="edit-btn" onclick='window.openRequestModal("edit", ${rowIndex}, ${colIndex}, ${JSON.stringify(cell).replace(/'/g, "&apos;")})'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="delete-btn" onclick="window.openRequestModal('delete', ${rowIndex}, ${colIndex})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>`;
                }
                rowDiv.appendChild(cellDiv);
            });
            body.appendChild(rowDiv);
        });
    }

    function showNotification(msg, type) {
        const el = document.createElement('div');
        el.className = `notification notification-${type}`;
        el.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'times-circle' : 'info-circle'}"></i> ${msg}`;
        document.body.appendChild(el);
        setTimeout(() => {
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 500);
        }, 3000);
    }

    window.openRequestModal = openRequestModal;
    window.closeRequestModal = closeRequestModal;
    window.submitRequest = submitRequest;
});
