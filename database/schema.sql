CREATE DATABASE IF NOT EXISTS `hr_management`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `hr_management`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(150) NULL,
  `email` VARCHAR(150) NULL,
  `phone` VARCHAR(30) NULL,
  `role` VARCHAR(30) NOT NULL DEFAULT 'Employee',
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_users_username` (`username`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `departments` (
  `department_id` INT AUTO_INCREMENT PRIMARY KEY,
  `department_name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_departments_name` (`department_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `positions` (
  `position_id` INT AUTO_INCREMENT PRIMARY KEY,
  `position_name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_positions_name` (`position_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `department_positions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `department_id` INT NOT NULL,
  `position_name` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_department_position` (`department_id`, `position_name`),
  CONSTRAINT `fk_department_positions_department`
    FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `employees` (
  `employee_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `employee_code` VARCHAR(50) NOT NULL,
  `full_name` VARCHAR(150) NOT NULL,
  `gender` VARCHAR(20) NULL,
  `date_of_birth` DATE NULL,
  `phone` VARCHAR(30) NULL,
  `email` VARCHAR(150) NULL,
  `address` TEXT NULL,
  `hire_date` DATE NULL,
  `basic_salary` DECIMAL(15,2) NULL DEFAULT 0,
  `identity_card` VARCHAR(50) NULL,
  `education_level` VARCHAR(100) NULL,
  `experience_years` INT NOT NULL DEFAULT 0,
  `marital_status` VARCHAR(50) NULL,
  `department_id` INT NULL,
  `position_id` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_employees_code` (`employee_code`),
  UNIQUE KEY `unique_employees_email` (`email`),
  KEY `idx_employees_user` (`user_id`),
  KEY `idx_employees_department` (`department_id`),
  KEY `idx_employees_position` (`position_id`),
  CONSTRAINT `fk_employees_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
    ON DELETE SET NULL,
  CONSTRAINT `fk_employees_department`
    FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`)
    ON DELETE SET NULL,
  CONSTRAINT `fk_employees_position`
    FOREIGN KEY (`position_id`) REFERENCES `positions` (`position_id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `contracts` (
  `contract_id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `contract_type` VARCHAR(100) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NULL,
  `salary` DECIMAL(15,2) NULL DEFAULT 0,
  `status` VARCHAR(20) NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_contracts_employee` (`employee_id`),
  KEY `idx_contracts_status` (`status`),
  KEY `idx_contracts_end_date` (`end_date`),
  CONSTRAINT `fk_contracts_employee`
    FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `salaries` (
  `salary_id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `salary_month` TINYINT NOT NULL,
  `salary_year` SMALLINT NOT NULL,
  `allowance` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `deduction` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `insurance` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `total_salary` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `calculated_date` DATE NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_salary_period` (`employee_id`, `salary_month`, `salary_year`),
  KEY `idx_salaries_period` (`salary_year`, `salary_month`),
  KEY `idx_salaries_calculated_date` (`calculated_date`),
  CONSTRAINT `fk_salaries_employee`
    FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `shifts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `shift_name` VARCHAR(100) NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `break_time` INT NOT NULL DEFAULT 0,
  `description` TEXT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_shifts_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `schedules` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `schedule_date` DATE NOT NULL,
  `shift_id` INT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'Scheduled',
  `notes` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_employee_schedule_date` (`employee_id`, `schedule_date`),
  KEY `idx_schedules_shift` (`shift_id`),
  KEY `idx_schedules_date` (`schedule_date`),
  CONSTRAINT `fk_schedules_employee`
    FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_schedules_shift`
    FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `employee_shifts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `shift_id` INT NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_employee_shifts_employee` (`employee_id`),
  KEY `idx_employee_shifts_shift` (`shift_id`),
  KEY `idx_employee_shifts_status` (`status`),
  CONSTRAINT `fk_employee_shifts_employee`
    FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_employee_shifts_shift`
    FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `attendance` (
  `attendance_id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `attendance_date` DATE NOT NULL,
  `check_in` TIME NULL,
  `check_out` TIME NULL,
  `working_hours` DECIMAL(5,2) NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL DEFAULT 'Present',
  `notes` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_attendance_employee_date` (`employee_id`, `attendance_date`),
  CONSTRAINT `fk_attendance_employee`
    FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `attendance_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `attendance_date` DATE NOT NULL,
  `requested_check_in` TIME NOT NULL,
  `requested_check_out` TIME NOT NULL,
  `reason` TEXT NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'Pending',
  `admin_note` TEXT NULL,
  `reviewed_by` INT NULL,
  `reviewed_at` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_attendance_requests_employee` (`employee_id`),
  KEY `idx_attendance_requests_status` (`status`),
  CONSTRAINT `fk_attendance_requests_employee`
    FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `part_timers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `hourly_rate` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `max_hours_per_week` DECIMAL(5,2) NOT NULL DEFAULT 30,
  `start_date` DATE NOT NULL,
  `end_date` DATE NULL,
  `contract_type` VARCHAR(50) NOT NULL DEFAULT 'Part-time',
  `status` VARCHAR(20) NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_part_timers_employee` (`employee_id`),
  KEY `idx_part_timers_status` (`status`),
  CONSTRAINT `fk_part_timers_employee`
    FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `part_time_hours` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `part_timer_id` INT NOT NULL,
  `work_date` DATE NOT NULL,
  `hours` DECIMAL(5,2) NOT NULL DEFAULT 0,
  `hourly_rate` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `notes` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_part_time_hours_timer_date` (`part_timer_id`, `work_date`),
  CONSTRAINT `fk_part_time_hours_timer`
    FOREIGN KEY (`part_timer_id`) REFERENCES `part_timers` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rewards_penalties` (
  `rp_id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `type` VARCHAR(30) NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `created_date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_rewards_penalties_employee` (`employee_id`),
  KEY `idx_rewards_penalties_type` (`type`),
  CONSTRAINT `fk_rewards_penalties_employee`
    FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

INSERT IGNORE INTO `departments` (`department_id`, `department_name`, `description`) VALUES
  (1, 'Hành chính - Nhân sự', 'Quản lý nhân sự, tuyển dụng và hành chính'),
  (2, 'Kế toán - Tài chính', 'Quản lý lương, thưởng và tài chính'),
  (3, 'Kinh doanh', 'Phát triển khách hàng và doanh thu'),
  (4, 'Kỹ thuật', 'Vận hành hệ thống và kỹ thuật');

INSERT IGNORE INTO `positions` (`position_id`, `position_name`, `description`) VALUES
  (1, 'Nhân viên', 'Nhân viên chính thức'),
  (2, 'Trưởng phòng', 'Quản lý phòng ban'),
  (3, 'HR', 'Nhân sự'),
  (4, 'Kế toán', 'Kế toán'),
  (5, 'Part-time', 'Nhân viên bán thời gian');

INSERT IGNORE INTO `department_positions` (`department_id`, `position_name`) VALUES
  (1, 'HR'),
  (1, 'Nhân viên'),
  (1, 'Trưởng phòng'),
  (2, 'Kế toán'),
  (2, 'Trưởng phòng'),
  (3, 'Nhân viên'),
  (3, 'Trưởng phòng'),
  (4, 'Nhân viên'),
  (4, 'Part-time');

INSERT IGNORE INTO `users`
  (`user_id`, `username`, `password`, `full_name`, `email`, `phone`, `role`, `status`)
VALUES
  (1, 'admin', '$2y$10$a77gEycYeePHnUc5GPLCrerIHpQJuAGt9XbFFirO2kSwYZzfLRuqe', 'Administrator', 'admin@example.com', '', 'Admin', 'active'),
  (2, 'hr', '$2y$10$a77gEycYeePHnUc5GPLCrerIHpQJuAGt9XbFFirO2kSwYZzfLRuqe', 'HR Demo', 'hr@example.com', '', 'HR', 'active'),
  (3, 'manager', '$2y$10$a77gEycYeePHnUc5GPLCrerIHpQJuAGt9XbFFirO2kSwYZzfLRuqe', 'Manager Demo', 'manager@example.com', '', 'Manager', 'active');

INSERT IGNORE INTO `shifts`
  (`id`, `shift_name`, `start_time`, `end_time`, `break_time`, `description`, `status`)
VALUES
  (1, 'Ca sáng', '08:00:00', '12:00:00', 0, 'Ca làm buổi sáng', 'Active'),
  (2, 'Ca chiều', '13:00:00', '17:00:00', 0, 'Ca làm buổi chiều', 'Active'),
  (3, 'Ca hành chính', '08:00:00', '17:00:00', 60, 'Ca làm hành chính', 'Active');
