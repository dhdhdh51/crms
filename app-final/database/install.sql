-- ============================================================
-- VastuVeda Realty CRM — COMPLETE INSTALL (Single File)
-- Schema + All Migrations + Attendance Module + Face Auth + Seed
-- Engine: InnoDB | Charset: utf8mb4_unicode_ci
-- Default login: admin@vastuveda.com / Admin@1234
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- NOTE: Change the database name below to match config/database.php (default: rtuzdalc_asm)
CREATE DATABASE IF NOT EXISTS rtuzdalc_asm
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rtuzdalc_asm;

-- Drop in reverse-dependency order
DROP TABLE IF EXISTS leave_requests;
DROP TABLE IF EXISTS office_locations;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS expenses;
DROP TABLE IF EXISTS targets;
DROP TABLE IF EXISTS lead_upload_logs;
DROP TABLE IF EXISTS attendance;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS salaries;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS site_visits;
DROP TABLE IF EXISTS followups;
DROP TABLE IF EXISTS leads;
DROP TABLE IF EXISTS units;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

-- ======================================
-- SCHEMA
-- ======================================

CREATE TABLE roles (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(50) NOT NULL UNIQUE,
    slug        VARCHAR(50) NOT NULL UNIQUE,
    permissions JSON,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id              INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    employee_id     VARCHAR(20)   NOT NULL UNIQUE,
    name            VARCHAR(100)  NOT NULL,
    email           VARCHAR(150)  UNIQUE,
    phone           VARCHAR(15),
    password        VARCHAR(255)  NOT NULL,
    role_id         INT UNSIGNED  NOT NULL,
    designation     VARCHAR(100),
    department      VARCHAR(100),
    join_date       DATE,
    is_active       TINYINT(1)    DEFAULT 1,
    last_login      TIMESTAMP     NULL,
    profile_photo   VARCHAR(255),
    face_descriptor JSON          DEFAULT NULL,
    face_updated_by INT UNSIGNED  DEFAULT NULL,
    face_updated_at TIMESTAMP     NULL,
    geo_lat         DECIMAL(10,7) DEFAULT NULL,
    geo_lng         DECIMAL(10,7) DEFAULT NULL,
    geo_radius      INT           DEFAULT 100,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT,
    INDEX idx_employee_id   (employee_id),
    INDEX idx_role_id       (role_id),
    INDEX idx_is_active     (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE projects (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(200) NOT NULL,
    location        VARCHAR(300),
    description     TEXT,
    total_units     INT DEFAULT 0,
    available_units INT DEFAULT 0,
    price_per_sqft  DECIMAL(10,2),
    status          ENUM('active','upcoming','sold_out','on_hold') DEFAULT 'active',
    image           VARCHAR(255),
    brochure        VARCHAR(255),
    created_by      INT UNSIGNED,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE units (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id  INT UNSIGNED NOT NULL,
    unit_number VARCHAR(50)  NOT NULL,
    type        ENUM('apartment','plot','villa','office','shop') DEFAULT 'apartment',
    floor       INT,
    area_sqft   DECIMAL(10,2),
    price       DECIMAL(15,2),
    status      ENUM('available','booked','sold','reserved') DEFAULT 'available',
    facing      VARCHAR(50),
    description TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    UNIQUE KEY uk_unit (project_id, unit_number),
    INDEX idx_project_status (project_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE leads (
    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name                  VARCHAR(100) NOT NULL,
    phone                 VARCHAR(15)  NOT NULL,
    email                 VARCHAR(150),
    source                ENUM('website','referral','walk_in','social_media','advertisement','cold_call','other') DEFAULT 'website',
    status                ENUM('new','hot','warm','cold','closed','lost') DEFAULT 'new',
    interested_project_id INT UNSIGNED,
    budget_min            DECIMAL(15,2),
    budget_max            DECIMAL(15,2),
    preferred_type        VARCHAR(100),
    assigned_to           INT UNSIGNED,
    notes                 TEXT,
    address               TEXT,
    next_followup_date    DATE,
    closed_value          DECIMAL(15,2),
    created_by            INT UNSIGNED,
    created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (interested_project_id) REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to)           REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by)            REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status        (status),
    INDEX idx_assigned_to   (assigned_to),
    INDEX idx_next_followup (next_followup_date),
    INDEX idx_created_at    (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE followups (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lead_id            INT UNSIGNED NOT NULL,
    user_id            INT UNSIGNED NOT NULL,
    followup_date      DATETIME     NOT NULL,
    type               ENUM('call','visit','email','whatsapp','meeting') DEFAULT 'call',
    notes              TEXT,
    outcome            TEXT,
    next_followup_date DATE,
    status             ENUM('pending','completed','missed') DEFAULT 'pending',
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_lead_id       (lead_id),
    INDEX idx_followup_date (followup_date),
    INDEX idx_status        (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE site_visits (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lead_id     INT UNSIGNED NOT NULL,
    project_id  INT UNSIGNED,
    assigned_to INT UNSIGNED,
    visit_date  DATETIME     NOT NULL,
    status      ENUM('scheduled','completed','cancelled','no_show') DEFAULT 'scheduled',
    feedback    TEXT,
    notes       TEXT,
    created_by  INT UNSIGNED,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id)     REFERENCES leads(id)    ON DELETE CASCADE,
    FOREIGN KEY (project_id)  REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id)    ON DELETE SET NULL,
    FOREIGN KEY (created_by)  REFERENCES users(id)    ON DELETE SET NULL,
    INDEX idx_visit_date  (visit_date),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_status      (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bookings (
    id             INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    lead_id        INT UNSIGNED  NOT NULL,
    unit_id        INT UNSIGNED  NOT NULL,
    booking_date   DATE          NOT NULL,
    total_amount   DECIMAL(15,2) NOT NULL,
    booking_amount DECIMAL(15,2),
    payment_status ENUM('pending','partial','completed') DEFAULT 'pending',
    handled_by     INT UNSIGNED,
    notes          TEXT,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id)    REFERENCES leads(id) ON DELETE RESTRICT,
    FOREIGN KEY (unit_id)    REFERENCES units(id) ON DELETE RESTRICT,
    FOREIGN KEY (handled_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_booking_date   (booking_date),
    INDEX idx_payment_status (payment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE salaries (
    id             INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    user_id        INT UNSIGNED  NOT NULL,
    month          TINYINT       NOT NULL,
    year           YEAR          NOT NULL,
    base_salary    DECIMAL(10,2) NOT NULL DEFAULT 0,
    incentives     DECIMAL(10,2) DEFAULT 0,
    bonus          DECIMAL(10,2) DEFAULT 0,
    deductions     DECIMAL(10,2) DEFAULT 0,
    net_salary     DECIMAL(10,2) GENERATED ALWAYS AS (base_salary + IFNULL(incentives,0) + IFNULL(bonus,0) - IFNULL(deductions,0)) STORED,
    payment_date   DATE,
    payment_status ENUM('pending','paid') DEFAULT 'pending',
    remarks        TEXT,
    generated_by   INT UNSIGNED,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)      REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uk_salary  (user_id, month, year),
    INDEX idx_year_month  (year, month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    type       VARCHAR(50)  NOT NULL,
    message    TEXT         NOT NULL,
    is_read    TINYINT(1)   DEFAULT 0,
    link       VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_unread (user_id, is_read),
    INDEX idx_created_at  (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activity_logs (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED,
    action     VARCHAR(100) NOT NULL,
    module     VARCHAR(50),
    record_id  INT UNSIGNED,
    details    TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id    (user_id),
    INDEX idx_module     (module),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ATTENDANCE: merged columns from all migrations
CREATE TABLE attendance (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id           INT UNSIGNED  NOT NULL,
    date              DATE          NOT NULL,
    check_in          TIME,
    check_out         TIME,
    status            ENUM('present','absent','late','half_day','holiday') DEFAULT 'present',
    method            ENUM('face','manual') DEFAULT 'face',
    checkin_image     VARCHAR(255)  DEFAULT NULL,
    checkout_image    VARCHAR(255)  DEFAULT NULL,
    latitude          DECIMAL(10,8) DEFAULT NULL,
    longitude         DECIMAL(11,8) DEFAULT NULL,
    checkin_lat       DECIMAL(10,7) DEFAULT NULL,
    checkin_lng       DECIMAL(10,7) DEFAULT NULL,
    checkout_lat      DECIMAL(10,7) DEFAULT NULL,
    checkout_lng      DECIMAL(10,7) DEFAULT NULL,
    distance_meters   DECIMAL(8,2)  DEFAULT NULL,
    location_verified TINYINT(1)    DEFAULT 0,
    geo_valid         TINYINT(1)    DEFAULT 1,
    notes             TEXT,
    marked_by         INT UNSIGNED,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)   REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uk_attendance     (user_id, date),
    INDEX idx_att_user_date      (user_id, date),
    INDEX idx_date               (date),
    INDEX idx_status             (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE lead_upload_logs (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filename    VARCHAR(255) NOT NULL,
    total_rows  INT DEFAULT 0,
    imported    INT DEFAULT 0,
    failed      INT DEFAULT 0,
    uploaded_by INT UNSIGNED,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE targets (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id        INT UNSIGNED NOT NULL,
    month          TINYINT      NOT NULL,
    year           YEAR         NOT NULL,
    target_leads   INT DEFAULT 0,
    target_visits  INT DEFAULT 0,
    target_sales   INT DEFAULT 0,
    target_revenue DECIMAL(12,2) DEFAULT 0,
    created_by     INT UNSIGNED,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_target (user_id, month, year),
    FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE expenses (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category     VARCHAR(100) NOT NULL,
    description  TEXT,
    amount       DECIMAL(10,2) NOT NULL DEFAULT 0,
    expense_date DATE          NOT NULL,
    receipt      VARCHAR(255)  DEFAULT NULL,
    added_by     INT UNSIGNED,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_date     (expense_date),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
    `key`   VARCHAR(100) PRIMARY KEY,
    `value` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE office_locations (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(150) NOT NULL,
    latitude      DECIMAL(10,8) NOT NULL,
    longitude     DECIMAL(11,8) NOT NULL,
    radius_meters INT          NOT NULL DEFAULT 150,
    is_active     TINYINT(1)   DEFAULT 1,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE leave_requests (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    leave_type  ENUM('annual','sick','unpaid','other') NOT NULL DEFAULT 'annual',
    from_date   DATE         NOT NULL,
    to_date     DATE         NOT NULL,
    days_count  INT          NOT NULL DEFAULT 1,
    reason      TEXT,
    status      ENUM('pending','approved','rejected') DEFAULT 'pending',
    reviewed_by INT UNSIGNED NULL,
    review_note TEXT         NULL,
    reviewed_at TIMESTAMP    NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)     REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id   (user_id),
    INDEX idx_status    (status),
    INDEX idx_from_date (from_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ======================================
-- SEED DATA
-- ======================================

INSERT INTO roles (name, slug, permissions) VALUES
('Administrator',  'admin',          '["all"]'),
('Super Admin',    'super_admin',     '["*"]'),
('HR Manager',     'hr',             '["employees","attendance","leave_requests","salary","targets","expenses","face_management"]'),
('Manager',        'manager',        '["leads","projects","employees","reports","payroll","site_visits","attendance"]'),
('Sales Executive','sales_executive', '["leads","site_visits","projects","attendance"]');

-- Default password for all: Admin@1234
INSERT INTO users (employee_id, name, email, phone, password, role_id, designation, department, join_date, is_active) VALUES
('EMP001', 'Rajesh Sharma', 'admin@vastuveda.com',  '9876543210', '$2y$12$c9Vd5ON5wpk3QeKrcX4vgOcKkpCoZXh6iRXoZkLK2onNejz1DHhAK', 1, 'Managing Director', 'Administration', '2020-01-15', 1),
('EMP002', 'Priya Mehta',   'hr@vastuveda.com',     '9876543211', '$2y$12$c9Vd5ON5wpk3QeKrcX4vgOcKkpCoZXh6iRXoZkLK2onNejz1DHhAK', 3, 'HR Manager',       'Human Resource', '2021-03-01', 1),
('EMP003', 'Amit Patel',    'amit@vastuveda.com',   '9876543212', '$2y$12$c9Vd5ON5wpk3QeKrcX4vgOcKkpCoZXh6iRXoZkLK2onNejz1DHhAK', 4, 'Sales Manager',    'Sales',          '2022-06-15', 1),
('EMP004', 'Sunita Verma',  'sunita@vastuveda.com', '9876543213', '$2y$12$c9Vd5ON5wpk3QeKrcX4vgOcKkpCoZXh6iRXoZkLK2onNejz1DHhAK', 5, 'Senior Sales Exec','Sales',          '2022-09-01', 1),
('EMP005', 'Ravi Krishnan', 'ravi@vastuveda.com',   '9876543214', '$2y$12$c9Vd5ON5wpk3QeKrcX4vgOcKkpCoZXh6iRXoZkLK2onNejz1DHhAK', 5, 'Sales Executive',  'Sales',          '2023-01-10', 1);

INSERT INTO settings (`key`, `value`) VALUES
('office_lat',    NULL),
('office_lng',    NULL),
('office_radius', '150'),
('office_name',   'Head Office');

-- UPDATE lat/lng to your real office coordinates before going live
INSERT INTO office_locations (name, latitude, longitude, radius_meters) VALUES
('Head Office', 26.84990000, 80.94620000, 200);

INSERT INTO projects (name, location, description, total_units, available_units, price_per_sqft, status, created_by) VALUES
('Vastu Heights Phase I',    'Sector 45, Noida, UP',       'Premium residential complex with world-class amenities, swimming pool, and landscaped gardens.',    120, 45,  8500.00,  'active',   1),
('Golden Meadows Villa',     'Gomti Nagar Ext., Lucknow',  'Exclusive villas with Vastu-compliant designs, private gardens, and state-of-the-art security.',    40,  18,  12000.00, 'active',   1),
('Realty Square Commercial', 'Hazratganj, Lucknow',        'Premium commercial spaces in the heart of the city, ideal for offices, showrooms, and retail.',     60,  35,  15000.00, 'active',   1),
('Sunrise Valley Plots',     'Amar Shaheed Path, Lucknow', 'RERA-approved residential plots with proper infrastructure, near schools, hospitals, and metro.',    200, 120, 5500.00,  'upcoming', 1),
('Pearl Residency',          'Indiranagar, Lucknow',       'Affordable luxury apartments with modern amenities and easy connectivity to major IT hubs.',         80,  5,   9200.00,  'sold_out', 1);

INSERT INTO units (project_id, unit_number, type, floor, area_sqft, price, status, facing) VALUES
(1,'A-101','apartment',1,1200.00,10200000.00,'sold','East'),(1,'A-102','apartment',1,1350.00,11475000.00,'available','North'),
(1,'A-201','apartment',2,1200.00,10200000.00,'booked','East'),(1,'A-202','apartment',2,1650.00,14025000.00,'available','South'),
(1,'B-101','apartment',1,1800.00,15300000.00,'available','North-East'),(2,'V-01','villa',0,3200.00,38400000.00,'sold','East'),
(2,'V-02','villa',0,3200.00,38400000.00,'available','North'),(2,'V-03','villa',0,4000.00,48000000.00,'reserved','North-East'),
(3,'G-101','office',1,800.00,12000000.00,'available','South'),(3,'G-102','shop',0,400.00,6000000.00,'booked','East');

INSERT INTO leads (name, phone, email, source, status, interested_project_id, budget_min, budget_max, preferred_type, assigned_to, notes, next_followup_date, created_by) VALUES
('Arun Kumar Singh','9811223344','arun@example.com',  'website',      'hot',   1,8000000, 15000000,'apartment',4,'Very interested, ready to visit',      DATE_ADD(CURDATE(),INTERVAL 1 DAY),1),
('Meena Agarwal',   '9822334455','meena@example.com', 'referral',     'warm',  2,30000000,50000000,'villa',    4,'Looking for Vastu-compliant villa',    DATE_ADD(CURDATE(),INTERVAL 2 DAY),1),
('Suresh Chandra',  '9833445566',NULL,                 'walk_in',      'new',   3,10000000,20000000,'office',   4,'Visited showroom today',               DATE_ADD(CURDATE(),INTERVAL 3 DAY),1),
('Pooja Nair',      '9844556677','pooja@example.com', 'social_media', 'cold',  1,5000000, 10000000,'apartment',5,'Not fully decided yet',                DATE_ADD(CURDATE(),INTERVAL 7 DAY),2),
('Vikram Malhotra', '9855667788','vikram@example.com','advertisement','closed',1,9000000, 12000000,'apartment',4,'Booked Unit A-201',                    NULL,1),
('Anita Desai',     '9866778899','anita@example.com', 'referral',     'hot',   2,35000000,45000000,'villa',    4,'NRI client, very serious buyer',       DATE_ADD(CURDATE(),INTERVAL 1 DAY),2),
('Ramesh Yadav',    '9877889900',NULL,                 'cold_call',    'warm',  4,4000000, 7000000, 'plot',     5,'Interested in plots',                  DATE_ADD(CURDATE(),INTERVAL 4 DAY),1),
('Divya Kapoor',    '9888990011','divya@example.com', 'website',      'new',   1,7000000, 11000000,'apartment',4,'First-time buyer',                     DATE_ADD(CURDATE(),INTERVAL 5 DAY),1),
('Sanjay Mishra',   '9899001122',NULL,                 'walk_in',      'lost',  3,8000000, 15000000,'office',   4,'Went with competitor',                 NULL,2),
('Kavita Pandey',   '9800112233','kavita@example.com','referral',     'hot',   2,38000000,50000000,'villa',    5,'Referred by Mr. Vikram',              DATE_ADD(CURDATE(),INTERVAL 2 DAY),1);

INSERT INTO followups (lead_id,user_id,followup_date,type,notes,outcome,next_followup_date,status) VALUES
(1,4,DATE_SUB(NOW(),INTERVAL 3 DAY),'call','Initial contact call','Very interested, wants to visit site',DATE_ADD(CURDATE(),INTERVAL 1 DAY),'completed'),
(1,4,DATE_ADD(NOW(),INTERVAL 1 DAY),'visit','Site visit scheduled',NULL,NULL,'pending'),
(2,4,DATE_SUB(NOW(),INTERVAL 5 DAY),'call','Explained villa features','Requested brochure',DATE_ADD(CURDATE(),INTERVAL 2 DAY),'completed'),
(5,4,DATE_SUB(NOW(),INTERVAL 10 DAY),'meeting','Deal finalization','Booking done!',NULL,'completed'),
(6,4,DATE_ADD(NOW(),INTERVAL 1 DAY),'call','Follow-up for NRI client',NULL,NULL,'pending'),
(7,5,DATE_ADD(NOW(),INTERVAL 4 DAY),'call','Explain plot investment benefits',NULL,NULL,'pending');

INSERT INTO site_visits (lead_id,project_id,assigned_to,visit_date,status,feedback,notes,created_by) VALUES
(1,1,4,DATE_ADD(NOW(),INTERVAL 1 DAY),'scheduled',NULL,'Pickup from Hazratganj Metro',1),
(2,2,4,DATE_SUB(NOW(),INTERVAL 2 DAY),'completed','Client loved the villa','Came with spouse',2),
(5,1,4,DATE_SUB(NOW(),INTERVAL 12 DAY),'completed','Loved the view, decided to book','Result: Booking confirmed',1),
(6,2,4,DATE_ADD(NOW(),INTERVAL 3 DAY),'scheduled',NULL,'NRI visit, arrange interpreter',2);

INSERT INTO bookings (lead_id,unit_id,booking_date,total_amount,booking_amount,payment_status,handled_by,notes) VALUES
(5,3,DATE_SUB(CURDATE(),INTERVAL 10 DAY),10200000.00,1000000.00,'partial',4,'Booking amount received. Remaining via home loan.'),
(6,7,DATE_SUB(CURDATE(),INTERVAL 2 DAY), 38400000.00,5000000.00,'partial',4,'NRI booking. Full payment in 60 days.');

INSERT INTO salaries (user_id,month,year,base_salary,incentives,bonus,deductions,payment_date,payment_status,remarks,generated_by) VALUES
(4,MONTH(CURDATE()),YEAR(CURDATE()),35000.00,8000.00,5000.00,3500.00,MAKEDATE(YEAR(CURDATE()),1)+INTERVAL(MONTH(CURDATE())-1) MONTH+INTERVAL 27 DAY,'paid','Incentive for bookings',1),
(5,MONTH(CURDATE()),YEAR(CURDATE()),32000.00,3000.00,0.00,3200.00,NULL,'pending','Salary processing',1);

INSERT INTO notifications (user_id,type,message,is_read,link) VALUES
(4,'followup','You have a site visit scheduled tomorrow with Arun Kumar Singh',0,'/site-visits'),
(4,'lead','New lead assigned: Divya Kapoor',0,'/leads/8'),
(1,'booking','New booking recorded — Unit A-201 by Vikram Malhotra',0,'/reports/sales'),
(1,'system','Monthly report for this month is ready to view',1,'/reports');

INSERT INTO attendance (user_id,date,check_in,check_out,status,method,marked_by) VALUES
(4,CURDATE(),'09:05:00',NULL,'present','face',4),
(5,CURDATE(),'09:45:00',NULL,'late','face',5),
(4,DATE_SUB(CURDATE(),INTERVAL 1 DAY),'09:10:00','18:05:00','present','face',4),
(5,DATE_SUB(CURDATE(),INTERVAL 1 DAY),'09:55:00','18:30:00','late','face',5),
(4,DATE_SUB(CURDATE(),INTERVAL 2 DAY),'09:00:00','17:55:00','present','face',4),
(5,DATE_SUB(CURDATE(),INTERVAL 2 DAY),NULL,NULL,'absent','manual',1),
(4,DATE_SUB(CURDATE(),INTERVAL 3 DAY),'09:30:00','18:00:00','late','face',4),
(5,DATE_SUB(CURDATE(),INTERVAL 3 DAY),'09:00:00','17:30:00','present','face',5);

UPDATE projects p SET
    total_units     = (SELECT COUNT(*) FROM units u WHERE u.project_id = p.id),
    available_units = (SELECT COUNT(*) FROM units u WHERE u.project_id = p.id AND u.status = 'available');

-- ============================================================
-- DONE. Login: admin@vastuveda.com / Admin@1234
-- ============================================================
