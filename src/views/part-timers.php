<?php
/**
 * ========================================
 * View: Danh sach Part-time
 * ========================================
 */

$partTimers = $partTimers ?? [];
$message = $message ?? '';
$errors = $errors ?? [];
$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $partTimers = array_filter($partTimers, fn($pt) =>
        stripos($pt['full_name'] ?? '', $search) !== false ||
        stripos($pt['employee_code'] ?? '', $search) !== false ||
        stripos($pt['department'] ?? '', $search) !== false ||
        stripos($pt['position'] ?? '', $search) !== false ||
        stripos($pt['status'] ?? '', $search) !== false
    );
}
$partTimers = array_values($partTimers);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Part-time - HR Management</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f1f5f9; color: #1e293b; }
        .container { max-width: 1250px; margin: 0 auto; padding: 24px; }
        .header { background: linear-gradient(135deg, #1e3a5f, #2563eb); color: #fff; padding: 26px 30px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 4px 16px rgba(37,99,235,.22); }
        .header h1 { font-size: 24px; margin-bottom: 6px; }
        .header p { color: #bfdbfe; font-size: 14px; }
        .nav { background: #fff; padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; box-shadow: 0 1px 3px rgba(0,0,0,.08); display: flex; gap: 8px; flex-wrap: wrap; }
        .nav a { color: #2563eb; text-decoration: none; padding: 8px 12px; border-radius: 6px; font-size: 14px; font-weight: 600; }
        .nav a:hover { background: #eff6ff; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 18px; flex-wrap: wrap; }
        .toolbar h2 { font-size: 18px; }
        .btn { border: none; border-radius: 7px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; padding: 9px 14px; font-size: 13px; font-weight: 700; transition: .2s; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-soft { background: #e0f2fe; color: #075985; }
        .btn-edit { background: #fef3c7; color: #92400e; }
        .btn-danger { background: #fee2e2; color: #991b1b; }
        .message { padding: 13px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; font-weight: 600; }
        .message.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .message.error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .table-wrap { background: #fff; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,.08); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        thead { background: #0f172a; color: #fff; }
        th, td { padding: 13px 14px; text-align: left; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
        tbody tr:hover { background: #f8fafc; }
        tbody tr:last-child td { border-bottom: none; }
        .muted { color: #64748b; font-size: 12px; margin-top: 3px; }
        .money { font-weight: 800; color: #2563eb; white-space: nowrap; }
        .status { display: inline-flex; padding: 4px 9px; border-radius: 999px; font-size: 12px; font-weight: 800; }
        .status.active { background: #dcfce7; color: #166534; }
        .status.inactive { background: #f1f5f9; color: #475569; }
        .actions { display: flex; gap: 7px; flex-wrap: wrap; }
        .actions form { display: inline; }
        .empty { padding: 42px 18px; text-align: center; color: #64748b; }
        @media (max-width: 900px) {
            .container { padding: 14px; }
            table, thead, tbody, th, td, tr { display: block; }
            thead { display: none; }
            tbody tr { border-bottom: 1px solid #e2e8f0; padding: 10px 0; }
            td { border: none; padding: 8px 14px; }
            td::before { content: attr(data-label); display: block; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 3px; }
        }
    </style>
<link rel="stylesheet" href="css/modern-ui.css">
<link rel="stylesheet" href="css/app-unified.css">
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Quan ly Part-time</h1>
        <p>Theo doi nhan vien part-time, hop dong va gio cong.</p>
    </div>

    <div class="nav">
        <a href="?page=dashboard">Dashboard</a>
        <a href="?page=employees">Nhan vien</a>
        <a href="?page=part-timers">Part-time</a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="message success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="message error">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="toolbar">
        <div class="toolbar-l"><h2>Danh sach part-timer</h2><span class="badge-count"><?= count($partTimers) ?></span></div>
        <form method="GET" class="toolbar-search">
            <input type="hidden" name="page" value="part-timers">
            <div class="search-wrap"><span class="s-icon icon-search" aria-hidden="true"></span><input type="text" name="search" placeholder="Tim ten, ma NV, phong ban..." value="<?= htmlspecialchars($search) ?>"></div>
            <button type="submit" class="btn btn-primary">Tim</button>
            <?php if ($search): ?><a href="?page=part-timers" class="btn btn-clear" aria-label="Xoa tim kiem">x</a><?php endif; ?>
        </form>
        <div class="toolbar-actions"><a href="?page=part-timers&action=add" class="btn btn-primary">+ Them part-timer</a></div>
    </div>

    <div class="table-wrap">
        <?php if (!empty($partTimers)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Ma NV</th>
                        <th>Ho ten</th>
                        <th>Phong ban / Vi tri</th>
                        <th>Luong gio</th>
                        <th>Toi da</th>
                        <th>Hop dong</th>
                        <th>Trang thai</th>
                        <th>Thao tac</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($partTimers as $pt): ?>
                        <tr>
                            <td data-label="Ma NV">
                                <strong><?= htmlspecialchars($pt['employee_code'] ?? '') ?></strong>
                            </td>
                            <td data-label="Ho ten">
                                <strong><?= htmlspecialchars($pt['full_name'] ?? '') ?></strong>
                                <div class="muted">Bat dau: <?= !empty($pt['start_date']) ? date('d/m/Y', strtotime($pt['start_date'])) : 'N/A' ?></div>
                            </td>
                            <td data-label="Phong ban / Vi tri">
                                <?= htmlspecialchars($pt['department'] ?? 'N/A') ?>
                                <div class="muted"><?= htmlspecialchars($pt['position'] ?? 'N/A') ?></div>
                            </td>
                            <td data-label="Luong gio" class="money">
                                <?= number_format((float)($pt['hourly_rate'] ?? 0), 0, ',', '.') ?> d
                            </td>
                            <td data-label="Toi da">
                                <?= htmlspecialchars($pt['max_hours_per_week'] ?? 0) ?> gio/tuan
                            </td>
                            <td data-label="Hop dong">
                                <?= htmlspecialchars($pt['contract_type'] ?? 'Part-time') ?>
                                <?php if (!empty($pt['end_date'])): ?>
                                    <div class="muted">Ket thuc: <?= date('d/m/Y', strtotime($pt['end_date'])) ?></div>
                                <?php endif; ?>
                            </td>
                            <td data-label="Trang thai">
                                <?php $statusClass = (($pt['status'] ?? '') === 'Active') ? 'active' : 'inactive'; ?>
                                <span class="status <?= $statusClass ?>"><?= htmlspecialchars($pt['status'] ?? '') ?></span>
                            </td>
                            <td data-label="Thao tac">
                                <div class="actions">
                                    <a class="btn btn-soft" href="?page=part-timers&action=hours&id=<?= (int)$pt['id'] ?>">Gio cong</a>
                                    <a class="btn btn-edit" href="?page=part-timers&action=edit&id=<?= (int)$pt['id'] ?>">Sua</a>
                                    <form method="POST" action="?page=part-timers" onsubmit="return confirm('Xoa part-timer nay?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$pt['id'] ?>">
                                        <button type="submit" class="btn btn-danger">Xoa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty">
                <h3><?= $search ? 'Khong tim thay part-timer' : 'Chua co part-timer' ?></h3>
                <p><?= $search ? 'Khong co part-timer phu hop voi tu khoa hien tai.' : 'Hay them nhan vien part-time dau tien de bat dau theo doi gio cong.' ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>


