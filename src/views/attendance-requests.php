<?php
require_once '../src/models/AttendanceRequest.php';

Auth::requireRole(['Admin', 'HR']);

$requestModel = new AttendanceRequest($pdo);
$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $reviewAction = $_POST['review_action'] ?? '';
    $note = trim($_POST['admin_note'] ?? '');
    $status = $reviewAction === 'approve' ? 'Approved' : ($reviewAction === 'reject' ? 'Rejected' : '');

    if (!$id || !$status) {
        $errors[] = 'Yêu cầu không hợp lệ.';
    } elseif ($requestModel->review($id, $status, Auth::getCurrentUser()['id'] ?? null, $note)) {
        $message = $status === 'Approved'
            ? 'Đã duyệt yêu cầu và cập nhật chấm công.'
            : 'Đã từ chối yêu cầu.';
    } else {
        $errors[] = 'Không thể xử lý yêu cầu này.';
    }
}

$statusFilter = $_GET['status'] ?? '';
$requests = $requestModel->getAll($statusFilter);

function arDate($date) {
    return !empty($date) ? date('d/m/Y', strtotime($date)) : '-';
}

function arTime($time) {
    return !empty($time) ? substr($time, 0, 5) : '-';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Duyệt yêu cầu chấm công</title>
<style>
:root{--primary:#2563eb;--primary-d:#1d4ed8;--success:#16a34a;--danger:#dc2626;--warning:#d97706;--bg:#f1f5f9;--text:#1e293b;--muted:#64748b;--border:#e2e8f0;}
*{margin:0;padding:0;box-sizing:border-box;}body{font-family:'Segoe UI',system-ui,sans-serif;background:var(--bg);color:var(--text);}
.wrap{max-width:1280px;margin:0 auto;padding:28px 24px;}
.page-hd{background:linear-gradient(135deg,#1e3a5f,#0f766e);border-radius:14px;padding:26px 32px;margin-bottom:18px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;box-shadow:0 4px 20px rgba(37,99,235,.22);}
.page-hd h1{color:#fff;font-size:24px;font-weight:900;}.page-hd p{color:#bfdbfe;font-size:13px;margin-top:4px;}
.btn{padding:9px 15px;border:none;border-radius:8px;text-decoration:none;cursor:pointer;font-size:13px;font-weight:800;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;}
.btn-primary{background:var(--primary);color:#fff;}.btn-primary:hover{background:var(--primary-d);}
.btn-soft{background:#eff6ff;color:var(--primary);border:1px solid #bfdbfe;}
.btn-ok{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;}
.btn-bad{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;}
.alert{padding:13px 16px;border-radius:10px;margin-bottom:18px;font-size:14px;font-weight:700;}
.alert-success{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;}.alert-error{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;}
.filter{background:#fff;border:1px solid var(--border);border-radius:12px;padding:14px 18px;margin-bottom:18px;display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;}
.filter select{padding:9px 12px;border:1px solid var(--border);border-radius:8px;background:#fff;font-weight:700;}
.tbl{background:#fff;border:1px solid var(--border);border-radius:14px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.06);}
table{width:100%;border-collapse:collapse;font-size:14px;}th,td{padding:12px 14px;border-bottom:1px solid #f1f5f9;vertical-align:top;}th{background:#f8fafc;color:#475569;text-align:left;font-size:13px;font-weight:900;}
.chip{display:inline-flex;padding:4px 9px;border-radius:999px;font-size:12px;font-weight:900;}.chip.pending{background:#fffbeb;color:#b45309;border:1px solid #fde68a;}.chip.approved{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;}.chip.rejected{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;}
.time-chip{background:#eff6ff;color:#1d4ed8;padding:4px 9px;border-radius:7px;font-size:12px;font-weight:900;white-space:nowrap;}
.review-form{display:grid;grid-template-columns:1fr auto auto;gap:8px;min-width:320px;}.review-form input{padding:8px 10px;border:1px solid var(--border);border-radius:8px;font-family:inherit;}
.empty{text-align:center;color:var(--muted);padding:50px 16px;}
@media(max-width:800px){.review-form{grid-template-columns:1fr;}th,td{padding:10px;}.wrap{padding:20px 12px;}}
</style>
<link rel="stylesheet" href="css/modern-ui.css">
<link rel="stylesheet" href="css/app-unified.css">
<link rel="stylesheet" href="css/official-ui.css">
</head>
<body>
<div class="wrap">
    <div class="page-hd">
        <div>
            <h1><i class="ui-icon icon-calendar" aria-hidden="true"></i> Duyệt yêu cầu chấm công</h1>
            <p>HR/Admin kiểm tra đơn quên chấm công và duyệt để ghi nhận vào bảng công.</p>
        </div>
        <a class="btn btn-soft" href="?page=dashboard">Dashboard</a>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if (!empty($errors)): ?><div class="alert alert-error"><?= htmlspecialchars(implode(' ', $errors)) ?></div><?php endif; ?>

    <form class="filter" method="GET">
        <input type="hidden" name="page" value="attendance-requests">
        <strong><?= count($requests) ?> yêu cầu</strong>
        <div>
            <select name="status">
                <option value="" <?= $statusFilter===''?'selected':'' ?>>Tất cả trạng thái</option>
                <option value="Pending" <?= $statusFilter==='Pending'?'selected':'' ?>>Chờ duyệt</option>
                <option value="Approved" <?= $statusFilter==='Approved'?'selected':'' ?>>Đã duyệt</option>
                <option value="Rejected" <?= $statusFilter==='Rejected'?'selected':'' ?>>Từ chối</option>
            </select>
            <button class="btn btn-primary" type="submit">Lọc</button>
        </div>
    </form>

    <div class="tbl">
        <?php if (empty($requests)): ?>
            <div class="empty">Chưa có yêu cầu chấm công.</div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Nhân viên</th>
                    <th>Ngày</th>
                    <th>Giờ đề nghị</th>
                    <th>Lý do</th>
                    <th>Trạng thái</th>
                    <th>Phản hồi / xử lý</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($requests as $req): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($req['full_name'] ?? '-') ?></strong><br>
                        <span style="color:var(--muted);font-size:12px;"><?= htmlspecialchars($req['employee_code'] ?? '-') ?> · <?= htmlspecialchars($req['department_name'] ?? '-') ?></span>
                    </td>
                    <td><?= arDate($req['attendance_date'] ?? '') ?></td>
                    <td><span class="time-chip"><?= arTime($req['requested_check_in'] ?? '') ?> → <?= arTime($req['requested_check_out'] ?? '') ?></span></td>
                    <td><?= htmlspecialchars($req['reason'] ?? '-') ?></td>
                    <td><span class="chip <?= strtolower($req['status'] ?? 'pending') ?>"><?= htmlspecialchars($req['status'] ?? '-') ?></span></td>
                    <td>
                        <?php if (($req['status'] ?? '') === 'Pending'): ?>
                            <form class="review-form" method="POST">
                                <input type="hidden" name="id" value="<?= (int)$req['id'] ?>">
                                <input type="text" name="admin_note" placeholder="Ghi chú phản hồi">
                                <button class="btn btn-ok" type="submit" name="review_action" value="approve">Duyệt</button>
                                <button class="btn btn-bad" type="submit" name="review_action" value="reject">Từ chối</button>
                            </form>
                        <?php else: ?>
                            <?= htmlspecialchars($req['admin_note'] ?: '-') ?><br>
                            <span style="color:var(--muted);font-size:12px;">Bởi <?= htmlspecialchars($req['reviewer_username'] ?? '-') ?> lúc <?= htmlspecialchars($req['reviewed_at'] ?? '-') ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
