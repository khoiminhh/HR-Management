<?php
class Attendance {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function logTime($employee_id, $date, $hours) {
        // Ép kiểu float cho số giờ để tính toán chính xác
        $hours = (float)$hours;
        $checkIn = '08:00:00';
        $checkOut = date('H:i:s', strtotime($checkIn . ' +' . (int)round($hours * 60) . ' minutes'));

        $sql = "INSERT INTO attendance (employee_id, attendance_date, check_in, check_out, working_hours, status)
                VALUES (?, ?, ?, ?, ?, 'Present')";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$employee_id, $date, $checkIn, $checkOut, $hours]);
    }

    public function getByEmployeeAndDate($employeeId, $date) {
        $stmt = $this->pdo->prepare(
            "SELECT *
             FROM attendance
             WHERE employee_id = ? AND attendance_date = ?
             ORDER BY attendance_id DESC
             LIMIT 1"
        );
        $stmt->execute([$employeeId, $date]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function checkIn($employeeId, $dateTime = null) {
        $dateTime = $dateTime ?: date('Y-m-d H:i:s');
        $date = date('Y-m-d', strtotime($dateTime));
        $time = date('H:i:s', strtotime($dateTime));
        $existing = $this->getByEmployeeAndDate($employeeId, $date);

        if ($existing && !empty($existing['check_in'])) {
            return false;
        }

        if ($existing) {
            $stmt = $this->pdo->prepare(
                "UPDATE attendance
                 SET check_in = ?, status = 'Present'
                 WHERE attendance_id = ?"
            );
            return $stmt->execute([$time, $existing['attendance_id']]);
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO attendance (employee_id, attendance_date, check_in, status, notes)
             VALUES (?, ?, ?, 'Present', 'Web check-in')"
        );
        return $stmt->execute([$employeeId, $date, $time]);
    }

    public function checkOut($employeeId, $dateTime = null) {
        $dateTime = $dateTime ?: date('Y-m-d H:i:s');
        $date = date('Y-m-d', strtotime($dateTime));
        $time = date('H:i:s', strtotime($dateTime));
        $existing = $this->getByEmployeeAndDate($employeeId, $date);

        if (!$existing || empty($existing['check_in']) || !empty($existing['check_out'])) {
            return false;
        }

        $hours = $this->calculateHours($existing['check_in'], $time);
        $stmt = $this->pdo->prepare(
            "UPDATE attendance
             SET check_out = ?, working_hours = ?, status = 'Present'
             WHERE attendance_id = ?"
        );
        return $stmt->execute([$time, $hours, $existing['attendance_id']]);
    }

    public function upsertManual($employeeId, $date, $checkIn, $checkOut, $notes = '') {
        $hours = $this->calculateHours($checkIn, $checkOut);
        $existing = $this->getByEmployeeAndDate($employeeId, $date);

        if ($existing) {
            $stmt = $this->pdo->prepare(
                "UPDATE attendance
                 SET check_in = ?, check_out = ?, working_hours = ?, status = 'Present', notes = ?
                 WHERE attendance_id = ?"
            );
            return $stmt->execute([$checkIn, $checkOut, $hours, $notes, $existing['attendance_id']]);
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO attendance (employee_id, attendance_date, check_in, check_out, working_hours, status, notes)
             VALUES (?, ?, ?, ?, ?, 'Present', ?)"
        );
        return $stmt->execute([$employeeId, $date, $checkIn, $checkOut, $hours, $notes]);
    }

    private function calculateHours($checkIn, $checkOut) {
        $start = strtotime($checkIn);
        $end = strtotime($checkOut);
        if (!$start || !$end) {
            return 0;
        }
        if ($end <= $start) {
            $end = strtotime('+1 day', $end);
        }
        return round(max(0, ($end - $start) / 3600), 2);
    }
}
