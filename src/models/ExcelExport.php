<?php
/**
 * ========================================
 * Model: ExcelExport (Không dùng thư viện)
 * ========================================
 */

class ExcelExport {
    
    /**
     * Xuất danh sách nhân viên
     */
    public static function exportEmployees($employees) {
        $filename = 'Danh_sach_nhan_vien_' . date('Y-m-d_H-i-s') . '.csv';
        
        // Set header
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // BOM cho UTF-8
        echo "\xEF\xBB\xBF";
        
        // Header columns
        $headers = ['Mã NV', 'Họ và tên', 'Email', 'Điện thoại', 'Phòng ban', 'Chức vụ', 'Trạng thái', 'Ngày vào'];
        echo implode(',', self::escapeCSV($headers)) . "\n";
        
        // Data rows
        foreach ($employees as $emp) {
            $row = [
                $emp['employee_code'],
                $emp['full_name'],
                $emp['email'] ?? '',
                $emp['phone'] ?? '',
                $emp['department'] ?? '',
                $emp['position'] ?? '',
                $emp['status'],
                date('d/m/Y', strtotime($emp['hire_date']))
            ];
            echo implode(',', self::escapeCSV($row)) . "\n";
        }
        
        exit;
    }
    
    /**
     * Xuất danh sách hợp đồng
     */
    public static function exportContracts($contracts) {
        $filename = 'Danh_sach_hop_dong_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo "\xEF\xBB\xBF";
        
        $headers = ['Số hợp đồng', 'Nhân viên', 'Mã NV', 'Loại', 'Ngày bắt đầu', 'Ngày kết thúc', 'Lương', 'Trạng thái'];
        echo implode(',', self::escapeCSV($headers)) . "\n";
        
        foreach ($contracts as $contract) {
            $row = [
                $contract['contract_number'],
                $contract['full_name'],
                $contract['employee_code'],
                $contract['contract_type'],
                date('d/m/Y', strtotime($contract['start_date'])),
                $contract['end_date'] ? date('d/m/Y', strtotime($contract['end_date'])) : 'Không xác định',
                $contract['salary'] ?? '',
                $contract['status']
            ];
            echo implode(',', self::escapeCSV($row)) . "\n";
        }
        
        exit;
    }
    
    /**
     * Xuất bảng lương
     */
    public static function exportSalaries($salaries, $month, $year) {
        $filename = 'Bang_luong_' . str_pad($month, 2, '0', STR_PAD_LEFT) . '_' . $year . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo "\xEF\xBB\xBF";
        
        // Title
        echo "BÁO CÁO LƯƠNG THÁNG " . str_pad($month, 2, '0', STR_PAD_LEFT) . "/" . $year . "\n\n";
        
        // Headers
        $headers = ['Nhân viên', 'Mã NV', 'Lương cơ bản', 'Phụ cấp', 'Khấu trừ', 'Bảo hiểm', 'Lương ròng'];
        echo implode(',', self::escapeCSV($headers)) . "\n";
        
        // Data
        $totalBase = 0;
        $totalNet = 0;
        
        foreach ($salaries as $salary) {
            $row = [
                $salary['full_name'],
                $salary['employee_code'],
                number_format($salary['base_salary'], 0, '.', ''),
                number_format($salary['allowances'], 0, '.', ''),
                number_format($salary['deductions'], 0, '.', ''),
                number_format($salary['insurance'], 0, '.', ''),
                number_format($salary['net_salary'], 0, '.', '')
            ];
            echo implode(',', self::escapeCSV($row)) . "\n";
            
            $totalBase += $salary['base_salary'];
            $totalNet += $salary['net_salary'];
        }
        
        // Total
        echo "\n";
        $totalRow = [
            'TỔNG CỘNG',
            '',
            number_format($totalBase, 0, '.', ''),
            '',
            '',
            '',
            number_format($totalNet, 0, '.', '')
        ];
        echo implode(',', self::escapeCSV($totalRow)) . "\n";
        
        exit;
    }
    
    /**
     * Escape CSV data
     */
    private static function escapeCSV($fields) {
        $escaped = [];
        foreach ($fields as $field) {
            $field = str_replace('"', '""', $field);
            $escaped[] = '"' . $field . '"';
        }
        return $escaped;
    }
}

?>