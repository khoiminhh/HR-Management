<!-- Form chấm công Part-time -->
<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Nhập giờ công cho nhân viên</h5>
    </div>
    <div class="card-body">
        <form action="index.php?page=part-timers&action=save_attendance" method="POST">
            <!-- ID nhân viên được ẩn đi để gửi ngầm -->
            <input type="hidden" name="employee_id" value="<?= $_GET['id'] ?>">

            <div class="mb-3">
                <label class="form-label">Ngày làm việc</label>
                <input type="date" name="work_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Số giờ làm việc (ví dụ: 4.5)</label>
                <input type="number" step="0.1" name="hours_worked" class="form-control" placeholder="Nhập số giờ..." required>
            </div>

            <div class="d-flex justify-content-end">
                <a href="index.php?page=part-timers" class="btn btn-secondary me-2">Hủy</a>
                <button type="submit" class="btn btn-success">Lưu giờ công</button>
            </div>
        </form>
    </div>
</div>