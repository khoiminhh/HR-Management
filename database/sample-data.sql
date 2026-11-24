USE `hr_management`;

SET NAMES utf8mb4;

INSERT INTO `employees`
  (`user_id`, `employee_code`, `full_name`, `gender`, `date_of_birth`, `phone`, `email`, `address`, `hire_date`, `basic_salary`, `identity_card`, `education_level`, `experience_years`, `marital_status`, `department_id`, `position_id`)
VALUES
  (1, 'NV001', 'Administrator', 'Other', '1990-01-15', '0901000001', 'admin@example.com', 'TP. Hồ Chí Minh', '2022-01-01', 30000000, '079090000001', 'Đại học', 10, 'Độc thân', 1, 2),
  (2, 'NV002', 'Nguyễn Thị Mai', 'Female', '1994-05-20', '0901000002', 'mai.hr@example.com', 'Quận 3, TP. Hồ Chí Minh', '2023-03-10', 18000000, '079094000002', 'Đại học', 5, 'Đã kết hôn', 1, 3),
  (3, 'NV003', 'Trần Văn Quân', 'Male', '1989-11-02', '0901000003', 'quan.manager@example.com', 'Quận 7, TP. Hồ Chí Minh', '2021-07-01', 26000000, '079089000003', 'Đại học', 9, 'Đã kết hôn', 3, 2),
  (NULL, 'NV004', 'Lê Hoàng Anh', 'Male', '1996-08-12', '0901000004', 'anh.sales@example.com', 'Thủ Đức, TP. Hồ Chí Minh', '2024-01-15', 14500000, '079096000004', 'Cao đẳng', 3, 'Độc thân', 3, 1),
  (NULL, 'NV005', 'Phạm Thu Hà', 'Female', '1995-02-28', '0901000005', 'ha.accounting@example.com', 'Bình Thạnh, TP. Hồ Chí Minh', '2023-09-01', 17000000, '079095000005', 'Đại học', 4, 'Độc thân', 2, 4),
  (NULL, 'NV006', 'Đỗ Minh Khang', 'Male', '1997-12-09', '0901000006', 'khang.tech@example.com', 'Gò Vấp, TP. Hồ Chí Minh', '2024-04-22', 16000000, '079097000006', 'Đại học', 2, 'Độc thân', 4, 1),
  (NULL, 'PT001', 'Võ Ngọc Linh', 'Female', '2002-06-18', '0902000001', 'linh.parttime@example.com', 'Quận 10, TP. Hồ Chí Minh', '2026-05-01', 0, '079102000001', 'Sinh viên', 1, 'Độc thân', 4, 5),
  (NULL, 'PT002', 'Bùi Quốc Bảo', 'Male', '2001-09-25', '0902000002', 'bao.parttime@example.com', 'Tân Bình, TP. Hồ Chí Minh', '2026-05-15', 0, '079101000002', 'Sinh viên', 1, 'Độc thân', 4, 5)
ON DUPLICATE KEY UPDATE
  `full_name` = VALUES(`full_name`),
  `gender` = VALUES(`gender`),
  `date_of_birth` = VALUES(`date_of_birth`),
  `phone` = VALUES(`phone`),
  `address` = VALUES(`address`),
  `hire_date` = VALUES(`hire_date`),
  `basic_salary` = VALUES(`basic_salary`),
  `identity_card` = VALUES(`identity_card`),
  `education_level` = VALUES(`education_level`),
  `experience_years` = VALUES(`experience_years`),
  `marital_status` = VALUES(`marital_status`),
  `department_id` = VALUES(`department_id`),
  `position_id` = VALUES(`position_id`);

INSERT INTO `contracts` (`employee_id`, `contract_type`, `start_date`, `end_date`, `salary`, `status`)
SELECT e.employee_id, 'Không xác định thời hạn', e.hire_date, NULL, e.basic_salary, 'Active'
FROM employees e
WHERE e.employee_code IN ('NV001', 'NV002', 'NV003', 'NV004', 'NV005', 'NV006')
  AND NOT EXISTS (
    SELECT 1 FROM contracts c
    WHERE c.employee_id = e.employee_id AND c.contract_type = 'Không xác định thời hạn'
  );

INSERT INTO `contracts` (`employee_id`, `contract_type`, `start_date`, `end_date`, `salary`, `status`)
SELECT e.employee_id, 'Part-time', e.hire_date, '2026-12-31', 0, 'Active'
FROM employees e
WHERE e.employee_code IN ('PT001', 'PT002')
  AND NOT EXISTS (
    SELECT 1 FROM contracts c
    WHERE c.employee_id = e.employee_id AND c.contract_type = 'Part-time'
  );

INSERT INTO `part_timers` (`employee_id`, `hourly_rate`, `max_hours_per_week`, `start_date`, `end_date`, `contract_type`, `status`)
SELECT e.employee_id, 35000, 24, '2026-05-01', NULL, 'Part-time', 'Active'
FROM employees e
WHERE e.employee_code = 'PT001'
  AND NOT EXISTS (SELECT 1 FROM part_timers pt WHERE pt.employee_id = e.employee_id);

INSERT INTO `part_timers` (`employee_id`, `hourly_rate`, `max_hours_per_week`, `start_date`, `end_date`, `contract_type`, `status`)
SELECT e.employee_id, 40000, 20, '2026-05-15', NULL, 'Part-time', 'Active'
FROM employees e
WHERE e.employee_code = 'PT002'
  AND NOT EXISTS (SELECT 1 FROM part_timers pt WHERE pt.employee_id = e.employee_id);

INSERT INTO `employee_shifts` (`employee_id`, `shift_id`, `start_date`, `end_date`, `status`)
SELECT e.employee_id, 3, e.hire_date, NULL, 'Active'
FROM employees e
WHERE e.employee_code IN ('NV001', 'NV002', 'NV003', 'NV004', 'NV005', 'NV006')
  AND NOT EXISTS (SELECT 1 FROM employee_shifts es WHERE es.employee_id = e.employee_id AND es.shift_id = 3);

INSERT INTO `employee_shifts` (`employee_id`, `shift_id`, `start_date`, `end_date`, `status`)
SELECT e.employee_id, 1, e.hire_date, NULL, 'Active'
FROM employees e
WHERE e.employee_code IN ('PT001', 'PT002')
  AND NOT EXISTS (SELECT 1 FROM employee_shifts es WHERE es.employee_id = e.employee_id AND es.shift_id = 1);

INSERT INTO `salaries`
  (`employee_id`, `salary_month`, `salary_year`, `allowance`, `deduction`, `insurance`, `total_salary`, `calculated_date`)
SELECT e.employee_id, 6, 2026, 1000000, 0, ROUND(e.basic_salary * 0.105, 0), e.basic_salary + 1000000 - ROUND(e.basic_salary * 0.105, 0), '2026-06-22'
FROM employees e
WHERE e.employee_code IN ('NV001', 'NV002', 'NV003', 'NV004', 'NV005', 'NV006')
ON DUPLICATE KEY UPDATE
  `allowance` = VALUES(`allowance`),
  `deduction` = VALUES(`deduction`),
  `insurance` = VALUES(`insurance`),
  `total_salary` = VALUES(`total_salary`),
  `calculated_date` = VALUES(`calculated_date`);

INSERT INTO `part_time_hours` (`part_timer_id`, `work_date`, `hours`, `hourly_rate`, `amount`, `notes`)
SELECT pt.id, d.work_date, d.hours, pt.hourly_rate, d.hours * pt.hourly_rate, d.notes
FROM part_timers pt
JOIN employees e ON e.employee_id = pt.employee_id
JOIN (
  SELECT 'PT001' AS employee_code, '2026-06-03' AS work_date, 4.0 AS hours, 'Ca sáng' AS notes
  UNION ALL SELECT 'PT001', '2026-06-05', 5.5, 'Hỗ trợ kỹ thuật'
  UNION ALL SELECT 'PT001', '2026-06-10', 4.5, 'Ca sáng'
  UNION ALL SELECT 'PT002', '2026-06-04', 4.0, 'Ca sáng'
  UNION ALL SELECT 'PT002', '2026-06-08', 6.0, 'Hỗ trợ vận hành'
  UNION ALL SELECT 'PT002', '2026-06-12', 4.5, 'Ca sáng'
) d ON d.employee_code = e.employee_code
WHERE NOT EXISTS (
  SELECT 1 FROM part_time_hours pth
  WHERE pth.part_timer_id = pt.id AND pth.work_date = d.work_date
);

INSERT INTO `schedules` (`employee_id`, `schedule_date`, `shift_id`, `status`, `notes`)
SELECT e.employee_id, d.schedule_date, d.shift_id, 'Scheduled', d.notes
FROM employees e
JOIN (
  SELECT 'NV002' AS employee_code, '2026-06-22' AS schedule_date, 3 AS shift_id, 'Ca hành chính' AS notes
  UNION ALL SELECT 'NV003', '2026-06-22', 3, 'Ca hành chính'
  UNION ALL SELECT 'NV004', '2026-06-22', 3, 'Ca hành chính'
  UNION ALL SELECT 'NV005', '2026-06-22', 3, 'Ca hành chính'
  UNION ALL SELECT 'PT001', '2026-06-22', 1, 'Ca part-time'
  UNION ALL SELECT 'PT002', '2026-06-22', 1, 'Ca part-time'
) d ON d.employee_code = e.employee_code
ON DUPLICATE KEY UPDATE
  `shift_id` = VALUES(`shift_id`),
  `status` = VALUES(`status`),
  `notes` = VALUES(`notes`);

INSERT INTO `attendance` (`employee_id`, `attendance_date`, `check_in`, `check_out`, `working_hours`, `status`, `notes`)
SELECT e.employee_id, d.attendance_date, d.check_in, d.check_out, d.working_hours, 'Present', d.notes
FROM employees e
JOIN (
  SELECT 'NV002' AS employee_code, '2026-06-22' AS attendance_date, '08:00:00' AS check_in, '17:00:00' AS check_out, 8.0 AS working_hours, 'Chấm công mẫu' AS notes
  UNION ALL SELECT 'NV003', '2026-06-22', '08:15:00', '17:15:00', 8.0, 'Chấm công mẫu'
  UNION ALL SELECT 'NV004', '2026-06-22', '08:00:00', '17:00:00', 8.0, 'Chấm công mẫu'
  UNION ALL SELECT 'PT001', '2026-06-22', '08:00:00', '12:00:00', 4.0, 'Chấm công part-time'
  UNION ALL SELECT 'PT002', '2026-06-22', '08:00:00', '12:00:00', 4.0, 'Chấm công part-time'
) d ON d.employee_code = e.employee_code
WHERE NOT EXISTS (
  SELECT 1 FROM attendance a
  WHERE a.employee_id = e.employee_id AND a.attendance_date = d.attendance_date
);
