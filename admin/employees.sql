-- إنشاء جدول الموظفين
CREATE TABLE IF NOT EXISTS employees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    department VARCHAR(50) NOT NULL,
    role ENUM('مدير_عام', 'موظف') NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
-- إدخال بيانات المدير العام
INSERT INTO employees (
        username,
        password,
        full_name,
        email,
        department,
        role
    )
VALUES (
        'admin',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        -- كلمة المرور: password
        'المدير العام',
        'admin@example.com',
        'الإدارة العامة',
        'مدير_عام'
    );
-- إدخال بيانات الموظف
INSERT INTO employees (
        username,
        password,
        full_name,
        email,
        department,
        role
    )
VALUES (
        'employee',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        -- كلمة المرور: password
        'موظف النظام',
        'employee@example.com',
        'خدمة العملاء',
        'موظف'
    );
-- إنشاء جدول المدفوعات للشركات
CREATE TABLE IF NOT EXISTS company_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_date DATETIME NOT NULL,
    payment_method ENUM('cash', 'bank_transfer', 'check') NOT NULL,
    reference_number VARCHAR(50),
    notes TEXT,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'completed',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (created_by) REFERENCES employees(id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;