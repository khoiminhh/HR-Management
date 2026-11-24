<?php
/**
 * ========================================
 * View: Departments
 * ========================================
 */

if (!isset($deptController)) {
    require_once '../src/controllers/DepartmentController.php';
    $deptController = new DepartmentController($pdo);
}

$editId = $_GET['id'] ?? null;
$editingDepartment = $editId ? $deptController->getById($editId) : null;
$editPositionId = $_GET['position_id'] ?? null;
$editingPosition = $editPositionId ? $deptController->getPositionById($editPositionId) : null;
$departments = $deptController->getAllWithEmployeeCounts();
$status = $_GET['status'] ?? '';
$isEdit = !empty($editingDepartment);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phòng ban - HR Management</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --ink: #0f172a;
            --text: #334155;
            --muted: #64748b;
            --bg: #eef2f7;
            --card: #ffffff;
            --border: #e2e8f0;
            --success: #16a34a;
            --danger: #dc2626;
            --warning: #d97706;
            --shadow: 0 16px 40px rgba(15, 23, 42, .08);
            --soft-shadow: 0 1px 3px rgba(15, 23, 42, .08);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(180deg, #f8fafc 0, var(--bg) 360px);
            color: var(--text);
            min-height: 100vh;
        }

        .page {
            width: min(1180px, calc(100% - 48px));
            margin: 0 auto;
            padding: 28px 0 46px;
        }

        .hero {
            background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 100%);
            color: #fff;
            border-radius: 10px;
            padding: 32px 36px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 20px;
        }

        .hero h1 {
            font-size: 30px;
            font-weight: 850;
            margin-bottom: 8px;
        }

        .hero p {
            color: #bfdbfe;
            font-size: 15px;
        }

        .hero-stat {
            min-width: 150px;
            text-align: right;
        }

        .hero-stat strong {
            display: block;
            font-size: 34px;
            line-height: 1;
        }

        .hero-stat span {
            color: #bfdbfe;
            font-size: 13px;
        }

        .nav {
            background: rgba(255,255,255,.97);
            border: 1px solid var(--border);
            border-radius: 10px;
            box-shadow: var(--soft-shadow);
            padding: 12px 16px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .nav a {
            color: #1d4ed8;
            text-decoration: none;
            padding: 10px 14px;
            border-radius: 8px;
            font-weight: 650;
            font-size: 14px;
            transition: .18s ease;
        }

        .nav a:hover {
            background: #eff6ff;
            color: var(--primary-dark);
        }

        .nav a.active {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 10px 20px rgba(37, 99, 235, .22);
        }

        .layout {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 20px;
            align-items: start;
        }

        .panel {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 10px;
            box-shadow: var(--soft-shadow);
        }

        .form-panel {
            padding: 24px;
            position: sticky;
            top: 20px;
        }

        .panel-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 18px;
        }

        .panel-title h2 {
            color: var(--ink);
            font-size: 20px;
            font-weight: 850;
        }

        .chip {
            background: #eff6ff;
            color: #1d4ed8;
            border-radius: 999px;
            padding: 5px 10px;
            font-size: 12px;
            font-weight: 800;
        }

        .field {
            margin-bottom: 16px;
        }

        label {
            display: block;
            color: var(--ink);
            font-weight: 750;
            font-size: 13px;
            margin-bottom: 7px;
        }

        input,
        textarea {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 11px 12px;
            font: inherit;
            color: var(--ink);
            background: #fff;
            transition: .18s ease;
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        input:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 4px;
        }

        .btn {
            border: none;
            border-radius: 8px;
            padding: 11px 15px;
            font-weight: 800;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            transition: .18s ease;
            font-size: 14px;
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-soft {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-soft:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        .btn-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-danger:hover {
            background: #fecaca;
        }

        .alert {
            border-radius: 10px;
            padding: 13px 16px;
            margin-bottom: 18px;
            font-weight: 700;
            border: 1px solid;
        }

        .alert.success {
            background: #dcfce7;
            color: #166534;
            border-color: #bbf7d0;
        }

        .alert.error {
            background: #fee2e2;
            color: #991b1b;
            border-color: #fecaca;
        }

        .list-panel {
            overflow: hidden;
        }

        .list-header {
            padding: 22px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .list-header h2 {
            color: var(--ink);
            font-size: 20px;
            font-weight: 850;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #0f172a;
            color: #fff;
            text-align: left;
            padding: 14px 16px;
            font-size: 13px;
            font-weight: 850;
            white-space: nowrap;
        }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .dept-name {
            color: var(--ink);
            font-weight: 850;
        }

        .dept-desc {
            color: var(--muted);
            font-size: 13px;
            margin-top: 3px;
        }

        .count-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 72px;
            padding: 6px 10px;
            border-radius: 999px;
            background: #ecfdf5;
            color: #166534;
            font-weight: 850;
            font-size: 13px;
        }

        .position-list {
            display: grid;
            gap: 6px;
        }

        .position-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 7px 8px;
            border-radius: 8px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 800;
        }

        .position-name {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .position-actions {
            display: inline-flex;
            gap: 6px;
            flex: 0 0 auto;
        }

        .position-form {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }

        .position-form input {
            padding: 8px 10px;
            font-size: 13px;
        }

        .position-form .btn,
        .position-actions .btn {
            padding: 7px 9px;
            font-size: 12px;
        }

        .muted-note {
            color: var(--muted);
            font-size: 12px;
            margin-top: 6px;
        }

        .row-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .row-actions form {
            display: inline;
        }

        .empty {
            padding: 46px 24px;
            text-align: center;
            color: var(--muted);
        }

        .empty strong {
            display: block;
            color: var(--ink);
            font-size: 18px;
            margin-bottom: 6px;
        }

        @media (max-width: 900px) {
            .page {
                width: min(100% - 24px, 1180px);
                padding-top: 14px;
            }

            .hero {
                padding: 24px;
                align-items: flex-start;
                flex-direction: column;
            }

            .hero h1 {
                font-size: 24px;
            }

            .hero-stat {
                text-align: left;
            }

            .layout {
                grid-template-columns: 1fr;
            }

            .form-panel {
                position: static;
            }

            table, thead, tbody, th, td, tr {
                display: block;
            }

            thead {
                display: none;
            }

            tbody tr {
                border-bottom: 1px solid var(--border);
                padding: 8px 0;
            }

            td {
                border: none;
                padding: 8px 16px;
            }

            td::before {
                content: attr(data-label);
                display: block;
                color: var(--muted);
                font-size: 11px;
                font-weight: 850;
                text-transform: uppercase;
                margin-bottom: 3px;
            }

            .row-actions {
                justify-content: flex-start;
            }
        }
    </style>
<link rel="stylesheet" href="css/modern-ui.css">
<link rel="stylesheet" href="css/app-unified.css">
</head>
<body>
<main class="page">
    <section class="hero">
        <div>
            <h1>Phòng ban</h1>
            <p>Quản lý cơ cấu phòng ban và theo dõi số nhân viên trong từng bộ phận.</p>
        </div>
        <div class="hero-stat">
            <strong><?= count($departments) ?></strong>
            <span>phòng ban</span>
        </div>
    </section>

    <nav class="nav">
        <a href="?page=dashboard">Dashboard</a>
        <a href="?page=employees">Nhân viên</a>
        <a href="?page=contracts">Hợp đồng</a>
        <a href="?page=departments" class="active">Phòng ban</a>
        <a href="?page=reports&type=department">Báo cáo phòng ban</a>
    </nav>

    <?php if ($status === 'saved'): ?>
        <div class="alert success">Đã lưu phòng ban thành công.</div>
    <?php elseif ($status === 'position_saved'): ?>
        <div class="alert success">&#272;&#227; l&#432;u ch&#7913;c v&#7909; th&#224;nh c&#244;ng.</div>
    <?php elseif ($status === 'position_deleted'): ?>
        <div class="alert success">&#272;&#227; x&#243;a ch&#7913;c v&#7909;.</div>
    <?php elseif ($status === 'deleted'): ?>
        <div class="alert success">Đã xóa phòng ban.</div>
    <?php elseif ($status === 'error'): ?>
        <div class="alert error">Không thể xử lý yêu cầu. Vui lòng kiểm tra lại dữ liệu.</div>
    <?php endif; ?>

    <div class="layout">
        <section class="panel form-panel">
            <div class="panel-title">
                <h2><?= $isEdit ? 'Cập nhật phòng ban' : 'Thêm phòng ban' ?></h2>
                <span class="chip"><?= $isEdit ? 'Sửa' : 'Mới' ?></span>
            </div>

            <form action="?page=departments&action=save" method="POST">
                <input type="hidden" name="id" value="<?= htmlspecialchars($editingDepartment['id'] ?? '') ?>">

                <div class="field">
                    <label for="department_name">Tên phòng ban</label>
                    <input
                        type="text"
                        id="department_name"
                        name="department_name"
                        value="<?= htmlspecialchars($editingDepartment['department_name'] ?? '') ?>"
                        placeholder="VD: Nhân sự, Kế toán, Kinh doanh"
                        required
                    >
                </div>

                <div class="field">
                    <label for="description">Mô tả</label>
                    <textarea
                        id="description"
                        name="description"
                        placeholder="Ghi chú ngắn về chức năng của phòng ban"
                    ><?= htmlspecialchars($editingDepartment['description'] ?? '') ?></textarea>
                </div>

                <div class="field">
                    <label for="positions">Chức vụ thuộc phòng ban</label>
                    <textarea
                        id="positions"
                        name="positions"
                        placeholder="VD: Trưởng phòng&#10;Nhân viên&#10;Thực tập sinh"
                    ><?= htmlspecialchars($editingDepartment['positions_text'] ?? '') ?></textarea>
                    <div class="muted-note">Mỗi chức vụ một dòng, hoặc phân tách bằng dấu phẩy.</div>
                </div>

                <div class="actions">
                    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Lưu thay đổi' : 'Thêm phòng ban' ?></button>
                    <?php if ($isEdit): ?>
                        <a href="?page=departments" class="btn btn-soft">Hủy sửa</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <section class="panel list-panel">
            <div class="list-header">
                <h2>Danh sách phòng ban</h2>
                <span class="chip"><?= count($departments) ?> mục</span>
            </div>

            <?php if (!empty($departments)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Phòng ban</th>
                            <th>Chức vụ</th>
                            <th>Nhân viên</th>
                            <th style="text-align:right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departments as $dept): ?>
                            <tr>
                                <td data-label="Phòng ban">
                                    <div class="dept-name"><?= htmlspecialchars($dept['department_name']) ?></div>
                                    <?php if (!empty($dept['description'])): ?>
                                        <div class="dept-desc"><?= htmlspecialchars($dept['description']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Chức vụ">
                                    <div class="position-list">
                                        <?php if (!empty($dept['positions'])): ?>
                                            <?php foreach ($dept['positions'] as $position): ?>
                                                <?php $isEditingThisPosition = $editingPosition && (int)$editingPosition['id'] === (int)$position['id']; ?>
                                                <?php if ($isEditingThisPosition): ?>
                                                    <form class="position-form" method="POST" action="?page=departments&action=save-position">
                                                        <input type="hidden" name="department_id" value="<?= (int)$dept['id'] ?>">
                                                        <input type="hidden" name="position_id" value="<?= (int)$position['id'] ?>">
                                                        <input type="text" name="position_name" value="<?= htmlspecialchars($position['position_name']) ?>" required>
                                                        <button type="submit" class="btn btn-primary">L&#432;u</button>
                                                        <a class="btn btn-soft" href="?page=departments">H&#7911;y</a>
                                                    </form>
                                                <?php else: ?>
                                                    <div class="position-item">
                                                        <span class="position-name"><?= htmlspecialchars($position['position_name']) ?></span>
                                                        <div class="position-actions">
                                                            <a class="btn btn-soft" href="?page=departments&position_id=<?= (int)$position['id'] ?>">S&#7917;a</a>
                                                            <form method="POST" action="?page=departments&action=delete-position" onsubmit="return confirm('X&#243;a ch&#7913;c v&#7909; n&#224;y?');">
                                                                <input type="hidden" name="position_id" value="<?= (int)$position['id'] ?>">
                                                                <button type="submit" class="btn btn-danger">X&#243;a</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="dept-desc">Chưa có chức vụ</span>
                                        <?php endif; ?>
                                    </div>
                                    <form class="position-form" method="POST" action="?page=departments&action=save-position">
                                        <input type="hidden" name="department_id" value="<?= (int)$dept['id'] ?>">
                                        <input type="text" name="position_name" placeholder="Th&#234;m ch&#7913;c v&#7909;" required>
                                        <button type="submit" class="btn btn-primary">Th&#234;m</button>
                                    </form>
                                </td>
                                <td data-label="Nhân viên">
                                    <span class="count-pill"><?= (int)$dept['employee_count'] ?> người</span>
                                </td>
                                <td data-label="Thao tác">
                                    <div class="row-actions">
                                        <a class="btn btn-soft" href="?page=departments&id=<?= (int)$dept['id'] ?>">Sửa</a>
                                        <form method="POST" action="?page=departments&action=delete" onsubmit="return confirm('Xóa phòng ban này?');">
                                            <input type="hidden" name="id" value="<?= (int)$dept['id'] ?>">
                                            <button type="submit" class="btn btn-danger">Xóa</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty">
                    <strong>Chưa có phòng ban</strong>
                    <span>Thêm phòng ban đầu tiên để bắt đầu phân loại nhân viên.</span>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>
</body>
</html>


