<?php
/**
 * ========================================
 * View: Chi tiết giờ công Part-time
 * Nhận từ PartTimerController->hours():
 *   $partTimer, $workHours, $summary
 *   $month, $year, $message, $errors
 * ========================================
 */
require_once '../src/models/PartTimer.php';

// Phân tích trạng thái lương & BHXH
$partTimerModel = new PartTimer($GLOBALS['pdo'] ?? $pdo);
$analysis = $partTimerModel->analyzeSalaryStatus(
    (int)$partTimer['id'], (int)$month, (int)$year, (float)$partTimer['hourly_rate']
);

$canAddHours = ($analysis['status'] !== 'exceeded');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Giờ công: <?= htmlspecialchars($partTimer['full_name']??'') ?></title>
<style>
:root{--primary:#2563eb;--primary-d:#1d4ed8;--success:#16a34a;--danger:#dc2626;--warning:#d97706;--bg:#f1f5f9;--text:#1e293b;--muted:#64748b;--border:#e2e8f0;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',system-ui,sans-serif;background:var(--bg);color:var(--text);}
.wrap{max-width:1200px;margin:0 auto;padding:28px 24px;}

/* Header */
.page-hd{background:linear-gradient(135deg,#1e3a5f,#2563eb);border-radius:14px;padding:26px 32px;margin-bottom:22px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;box-shadow:0 4px 20px rgba(37,99,235,.25);}
.page-hd h1{color:#fff;font-size:22px;font-weight:800;}.page-hd p{color:#93c5fd;font-size:13px;margin-top:5px;}

/* Breadnav */
.breadnav{display:flex;align-items:center;gap:6px;background:#fff;padding:11px 18px;border-radius:10px;margin-bottom:18px;box-shadow:0 1px 3px rgba(0,0,0,.07);flex-wrap:wrap;}
.breadnav a{color:var(--muted);text-decoration:none;font-size:13px;padding:4px 10px;border-radius:6px;transition:all .2s;}
.breadnav a:hover{background:var(--bg);color:var(--primary);}.breadnav .sep{color:#cbd5e1;font-size:11px;}

/* Alert boxes */
.alert{padding:14px 18px;border-radius:10px;margin-bottom:16px;font-size:14px;font-weight:500;display:flex;align-items:flex-start;gap:10px;}
.alert-success{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;}
.alert-error  {background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;}
.alert-warning{background:#fffbeb;color:#92400e;border:1px solid #fde68a;}
.alert-danger {background:#fef2f2;color:#991b1b;border:2px solid #fca5a5;}
.alert-bhxh-required{background:#fff1f2;color:#881337;border:2px solid #fda4af;}
.alert-bhxh-info{background:#fffbeb;color:#78350f;border:1px solid #fde68a;}

/* Progress bar */
.progress-wrap{background:#fff;border-radius:12px;padding:20px;margin-bottom:18px;box-shadow:0 1px 3px rgba(0,0,0,.07);}
.progress-label{display:flex;justify-content:space-between;margin-bottom:8px;font-size:13px;font-weight:600;}
.progress-bar-bg{height:14px;background:#e2e8f0;border-radius:8px;overflow:hidden;}
.progress-bar-fill{height:100%;border-radius:8px;transition:width .4s;background:var(--success);}
.progress-bar-fill.warn{background:var(--warning);}
.progress-bar-fill.danger{background:var(--danger);}
.progress-info{display:flex;justify-content:space-between;margin-top:6px;font-size:12px;color:var(--muted);}

/* Summary cards */
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:20px;}
.card{background:#fff;border-radius:12px;padding:18px;box-shadow:0 1px 3px rgba(0,0,0,.07);border-left:4px solid #ccc;text-align:center;}
.card.blue{border-left-color:var(--primary);}.card.green{border-left-color:var(--success);}.card.orange{border-left-color:var(--warning);}.card.red{border-left-color:var(--danger);}
.card .val{font-size:20px;font-weight:800;color:var(--text);}.card .lbl{font-size:12px;color:var(--muted);margin-top:4px;}

/* Filter & Toolbar */
.filter-bar{background:#fff;padding:14px 20px;border-radius:12px;margin-bottom:18px;box-shadow:0 1px 3px rgba(0,0,0,.07);display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.filter-bar select,.filter-bar input{padding:8px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;}
.toolbar{display:flex;justify-content:space-between;align-items:center;background:#fff;padding:14px 20px;border-radius:12px;margin-bottom:18px;box-shadow:0 1px 3px rgba(0,0,0,.07);gap:10px;flex-wrap:wrap;}

/* Buttons */
.btn{padding:9px 16px;border:none;border-radius:8px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;transition:all .2s;}
.btn-primary{background:var(--primary);color:#fff;}.btn-primary:hover{background:var(--primary-d);}
.btn-back{background:#f1f5f9;color:var(--muted);}.btn-back:hover{background:#e2e8f0;}
.btn-disabled{background:#e2e8f0;color:#94a3b8;cursor:not-allowed;opacity:.7;}

/* Add hours form */
.add-form{background:#fff;border-radius:12px;padding:22px;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,.07);border-top:3px solid var(--primary);display:none;}
.add-form.show{display:block;}
.add-form h3{font-size:15px;font-weight:700;margin-bottom:16px;}
.form-row{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:14px;}
.fg label{display:block;margin-bottom:6px;font-weight:600;font-size:13px;}
.fg input{width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:8px;font-size:14px;}
.fg input:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(37,99,235,.1);}
.break-note{background:#eff6ff;border-radius:8px;padding:10px 14px;font-size:12px;color:#1e40af;margin-bottom:14px;}
.preview-amount{background:#f0fdf4;border-radius:8px;padding:10px 14px;font-size:13px;font-weight:600;color:#15803d;margin-bottom:14px;}

/* Table */
.tbl-wrap{background:#fff;border-radius:14px;box-shadow:0 1px 3px rgba(0,0,0,.07);overflow:hidden;}
table{width:100%;border-collapse:collapse;font-size:14px;}
thead{background:linear-gradient(135deg,#1e3a5f,#2563eb);}
th{padding:13px 15px;text-align:left;font-weight:600;color:#fff;white-space:nowrap;}
td{padding:12px 15px;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
tbody tr:hover{background:#f8faff;}tbody tr:last-child td{border-bottom:none;}
tfoot td{background:#f0f4ff;font-weight:700;padding:12px 15px;}
.money{text-align:right;font-weight:700;color:var(--primary);}
.tbl-footer{padding:13px 20px;background:#f8faff;border-top:1px solid var(--border);font-size:12px;color:var(--muted);}
.empty{text-align:center;padding:50px 20px;color:var(--muted);}
</style>
<link rel="stylesheet" href="css/modern-ui.css">
<link rel="stylesheet" href="css/app-unified.css">
</head>
<body>
<div class="wrap">

<!-- Header -->
<div class="page-hd">
    <div>
        <h1>⏱️ Giờ công: <?= htmlspecialchars($partTimer['full_name']??'N/A') ?></h1>
        <p>
            Mã NV: <?= htmlspecialchars($partTimer['employee_code']??'') ?> &nbsp;|&nbsp;
            Lương/giờ: <?= number_format($partTimer['hourly_rate']??0,0,',','.') ?> đ &nbsp;|&nbsp;
            Tối đa/tuần: <?= $partTimer['max_hours_per_week']??0 ?> giờ &nbsp;|&nbsp;
            Nghỉ giữa ca: 30 phút/ca (tự động trừ)
        </p>
    </div>
    <a href="?page=part-timers" class="btn btn-back">← Quay lại</a>
</div>

<!-- Breadnav -->
<div class="breadnav">
    <a href="?page=dashboard"><i class="ui-icon icon-dashboard" aria-hidden="true"></i> Dashboard</a><span class="sep">›</span>
    <a href="?page=part-timers"><i class="ui-icon icon-part-time" aria-hidden="true"></i> Part-time</a><span class="sep">›</span>
    <a href="#">⏱️ <?= htmlspecialchars($partTimer['full_name']??'') ?></a>
</div>

<!-- ── CẢNH BÁO LƯƠNG ── -->
<?php if ($analysis['status'] === 'exceeded'): ?>
<div class="alert alert-danger">
    <span style="font-size:20px;"></span>
    <div>
        <strong>Đã đạt giới hạn lương tháng!</strong><br>
        Tổng lương tháng <?= $month ?>/<?= $year ?> đã đạt <strong><?= number_format($analysis['total_amount'],0,',','.') ?> đ</strong>
        (giới hạn: <?= number_format(PartTimer::SALARY_MAX,0,',','.') ?> đ). Không thể thêm giờ công.
    </div>
</div>
<?php elseif ($analysis['status'] === 'warning'): ?>
<div class="alert alert-warning">
    <span style="font-size:20px;"><i class="ui-icon icon-warning" aria-hidden="true"></i></span>
    <div>
        <strong>Sắp đạt giới hạn lương!</strong><br>
        Đã làm <strong><?= number_format($analysis['total_amount'],0,',','.') ?> đ</strong> /
        <?= number_format(PartTimer::SALARY_MAX,0,',','.') ?> đ &nbsp;—&nbsp;
        Còn được làm tối đa <strong><?= number_format($analysis['remaining_amount'],0,',','.') ?> đ
        (<?= $analysis['remaining_hours'] ?> giờ)</strong>.
    </div>
</div>
<?php endif; ?>

<!-- ── CẢNH BÁO BHXH ── -->
<?php if ($analysis['bhxh_required']): ?>
<div class="alert alert-bhxh-required">
    <span style="font-size:20px;"></span>
    <div>
        <strong>Thuộc diện bắt buộc đóng Bảo hiểm Xã hội (BHXH)!</strong><br>
        Thu nhập tháng <?= $month ?>/<?= $year ?> đạt
        <strong><?= number_format($analysis['total_amount'],0,',','.') ?> đ</strong>
        ≥ ngưỡng <?= number_format(PartTimer::BHXH_THRESHOLD,0,',','.') ?> đ/tháng.
        Vui lòng làm thủ tục đóng BHXH cho nhân viên này.
    </div>
</div>
<?php elseif ($analysis['total_amount'] > 0): ?>
<div class="alert alert-bhxh-info">
    <span style="font-size:18px;"></span>
    <div>
        <strong>Chưa đủ điều kiện đóng BHXH.</strong><br>
        <?= $analysis['bhxh_message'] ?>
    </div>
</div>
<?php endif; ?>

<!-- ── THÔNG BÁO FORM ── -->
<?php if (!empty($message)): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if (!empty($errors)): ?><div class="alert alert-error"><?= htmlspecialchars(implode('<br>', $errors)) ?></div><?php endif; ?>

<!-- ── PROGRESS BAR LƯƠNG ── -->
<div class="progress-wrap">
    <div class="progress-label">
        <span><i class="ui-icon icon-dashboard" aria-hidden="true"></i> Lương tháng <?= $month ?>/<?= $year ?></span>
        <span><?= $analysis['percent'] ?>% — <?= number_format($analysis['total_amount'],0,',','.') ?> / <?= number_format(PartTimer::SALARY_MAX,0,',','.') ?> đ</span>
    </div>
    <div class="progress-bar-bg">
        <div class="progress-bar-fill <?= $analysis['status']==='warning'?'warn':($analysis['status']==='exceeded'?'danger':'') ?>"
             style="width:<?= min(100,$analysis['percent']) ?>%"></div>
    </div>
    <div class="progress-info">
        <span>Ngưỡng cảnh báo: <?= number_format(PartTimer::SALARY_WARN,0,',','.') ?> đ</span>
        <span>Giới hạn: <?= number_format(PartTimer::SALARY_MAX,0,',','.') ?> đ</span>
    </div>
</div>

<!-- ── SUMMARY CARDS ── -->
<div class="cards">
    <div class="card blue">
        <div class="val"><?= number_format($analysis['total_hours'],1) ?> giờ</div>
        <div class="lbl">Tổng giờ (đã trừ nghỉ)</div>
    </div>
    <div class="card green">
        <div class="val" style="font-size:16px;"><?= number_format($analysis['total_amount'],0,',','.') ?>đ</div>
        <div class="lbl">Tổng lương tháng</div>
    </div>
    <div class="card orange">
        <div class="val" style="font-size:16px;"><?= number_format($analysis['remaining_amount'],0,',','.') ?>đ</div>
        <div class="lbl">Còn được làm</div>
    </div>
    <div class="card <?= $analysis['bhxh_required']?'red':'blue' ?>">
        <div class="val"><?= $analysis['bhxh_required']?'Có':'Chưa' ?></div>
        <div class="lbl">Bắt buộc BHXH</div>
    </div>
    <div class="card blue">
        <div class="val"><?= count($workHours) ?></div>
        <div class="lbl">Số ngày làm</div>
    </div>
</div>

<!-- ── FILTER ── -->
<form method="GET" class="filter-bar">
    <input type="hidden" name="page" value="part-timers">
    <input type="hidden" name="action" value="hours">
    <input type="hidden" name="id" value="<?= $partTimer['id'] ?>">
    <label style="font-weight:600;font-size:13px;">Tháng:</label>
    <select name="month"><?php for($m=1;$m<=12;$m++): ?><option value="<?= $m ?>" <?= $m==$month?'selected':'' ?>>Tháng <?= $m ?></option><?php endfor; ?></select>
    <label style="font-weight:600;font-size:13px;">Năm:</label>
    <select name="year"><?php for($y=date('Y')-2;$y<=date('Y');$y++): ?><option value="<?= $y ?>" <?= $y==$year?'selected':'' ?>><?= $y ?></option><?php endfor; ?></select>
    <button type="submit" class="btn btn-primary">Lọc</button>
</form>

<!-- ── TOOLBAR ── -->
<div class="toolbar">
    <h2 style="font-size:16px;font-weight:700;">Bảng giờ công tháng <?= $month ?>/<?= $year ?></h2>
    <?php if ($canAddHours): ?>
        <button class="btn btn-primary" onclick="toggleForm()"><i class="ui-icon icon-add" aria-hidden="true"></i> Thêm giờ công</button>
    <?php else: ?>
        <span class="btn btn-disabled">Không thể thêm (đã đạt giới hạn)</span>
    <?php endif; ?>
</div>

<!-- ── FORM THÊM GIỜ CÔNG ── -->
<?php if ($canAddHours): ?>
<div class="add-form" id="addForm">
    <h3><i class="ui-icon icon-add" aria-hidden="true"></i> Thêm giờ công mới</h3>
    <div class="break-note">
        Hệ thống tự động trừ <strong>30 phút nghỉ</strong>/ca khi tính lương.
        VD: nhập 8 giờ → tính lương 7.5 giờ = <?= number_format(7.5 * $partTimer['hourly_rate'],0,',','.') ?> đ.
        <?php if ($analysis['status']==='warning'): ?>
        &nbsp;|&nbsp; Còn được làm tối đa <strong><?= $analysis['remaining_hours'] ?> giờ</strong> tháng này.
        <?php endif; ?>
    </div>
    <form method="POST" action="?page=part-timers&action=hours&id=<?= $partTimer['id'] ?>&month=<?= (int)$month ?>&year=<?= (int)$year ?>">
        <input type="hidden" name="action" value="add_hours">
        <div class="form-row">
            <div class="fg">
                <label>Ngày làm việc *</label>
                <input type="date" name="work_date" id="wd" required value="<?= date('Y-m-d') ?>" onchange="previewAmount()">
            </div>
            <div class="fg">
                <label>Số giờ làm thực tế *</label>
                <input type="number" name="hours" id="hrs" min="0.5" max="12" step="0.5" required placeholder="VD: 8" oninput="previewAmount()">
            </div>
            <div class="fg">
                <label>Ghi chú</label>
                <input type="text" name="notes" placeholder="Tuỳ chọn">
            </div>
        </div>
        <div class="preview-amount" id="previewAmt" style="display:none;"></div>
        <div style="display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary"><i class="ui-icon icon-save" aria-hidden="true"></i> Lưu</button>
            <button type="button" class="btn btn-back" onclick="toggleForm()">Hủy</button>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- ── BẢNG GIỜ CÔNG ── -->
<div class="tbl-wrap">
<?php if (count($workHours)>0): ?>
<table>
<thead><tr>
    <th>#</th><th>Ngày làm</th>
    <th class="money">Giờ đã trừ nghỉ</th>
    <th class="money">Lương/giờ</th>
    <th class="money">Thành tiền</th>
    <th>Ghi chú</th>
</tr></thead>
<tbody>
<?php foreach ($workHours as $i => $row): ?>
<tr>
    <td style="color:#94a3b8;font-size:12px;"><?= $i+1 ?></td>
    <td><strong><?= date('d/m/Y',strtotime($row['work_date'])) ?></strong>
        <div style="font-size:11px;color:var(--muted);"><?= ['Sun'=>'CN','Mon'=>'T2','Tue'=>'T3','Wed'=>'T4','Thu'=>'T5','Fri'=>'T6','Sat'=>'T7'][date('D',strtotime($row['work_date']))]??'' ?></div>
    </td>
    <td class="money"><?= number_format($row['hours'],1) ?> giờ</td>
    <td class="money"><?= number_format($row['hourly_rate'],0,',','.') ?> đ</td>
    <td class="money" style="color:var(--success);"><?= number_format($row['amount'],0,',','.') ?> đ</td>
    <td style="color:var(--muted);font-size:13px;"><?= htmlspecialchars($row['notes']??'') ?></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot>
<tr>
    <td colspan="2">Tổng cộng (<?= count($workHours) ?> ngày)</td>
    <td class="money"><?= number_format($analysis['total_hours'],1) ?> giờ</td>
    <td></td>
    <td class="money" style="color:var(--success);font-size:15px;"><?= number_format($analysis['total_amount'],0,',','.') ?> đ</td>
    <td></td>
</tr>
</tfoot>
</table>
<div class="tbl-footer">
    * Giờ hiển thị đã trừ 30 phút nghỉ giữa ca &nbsp;|&nbsp;
    Ngưỡng BHXH: <?= number_format(PartTimer::BHXH_THRESHOLD,0,',','.') ?> đ/tháng
</div>
<?php else: ?>
<div class="empty">
    <div style="font-size:40px;margin-bottom:10px;"></div>
    <p>Chưa có giờ công tháng <?= $month ?>/<?= $year ?></p>
</div>
<?php endif; ?>
</div>

</div><!-- /wrap -->

<script>
const hourlyRate = <?= (float)$partTimer['hourly_rate'] ?>;
const breakH     = 0.5; // 30 phút
const maxAmount  = <?= PartTimer::SALARY_MAX ?>;
const remaining  = <?= $analysis['remaining_amount'] ?>;

function toggleForm() {
    document.getElementById('addForm').classList.toggle('show');
}

function previewAmount() {
    const raw  = parseFloat(document.getElementById('hrs').value) || 0;
    const bill = Math.max(0, raw - breakH);
    const amt  = bill * hourlyRate;
    const box  = document.getElementById('previewAmt');
    if (raw > 0) {
        let msg = `⏱️ ${raw}h nhập → trừ 30p nghỉ → tính lương <strong>${bill.toFixed(1)} giờ</strong> = <strong>${amt.toLocaleString('vi-VN')} đ</strong>`;
        if (amt > remaining) {
            msg += ` &nbsp;<span style="color:#dc2626;">Vượt giới hạn còn lại ${remaining.toLocaleString('vi-VN')} đ!</span>`;
        }
        box.innerHTML = msg;
        box.style.display = 'block';
    } else {
        box.style.display = 'none';
    }
}
</script>
</body>
</html>

