# MoneyKu - Personal Finance Management System (PFMS)

**MoneyKu** is a web-based Personal Finance Management System designed for young adults and undergraduate students to build healthy financial habits. It allows users to track income and expenses, manage budgets, set savings goals, monitor PTPTN student loans, and run financial simulations.

---

## 🌟 Key Features

### 👤 User Features
- 📊 **Financial Dashboard**: Overview of current balance, income, expenses, and savings trends.
- 💳 **Transaction Tracking**: Log daily income and expense transactions with custom categories.
- 🎯 **Budgeting & Savings Goals**: Set monthly budget limits and track savings goals.
- 🎓 **PTPTN Repayment & Calculator**: Monitor student loan balances and calculate repayment schedules.
- 📈 **Financial Simulation**: Interactive tool to forecast future savings and long-term financial growth.
- 🔒 **Account Management**: Register, login, update profile, and reset password via email (PHPMailer).
- 💬 **User Feedback**: Submit feedback and suggestions directly through the system.

### 🛡️ Admin Features
- 📈 **Admin Dashboard**: View system statistics and user analytics.
- 🚫 **Account Management**: Moderate user activity with account status controls.
- 📬 **Feedback Review**: Review and manage submitted user feedback.

---

## 🛠️ Tech Stack

- **Backend**: PHP (MySQLi)
- **Database**: MySQL (`pfms_db`)
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Email Service**: PHPMailer
- **Server Environment**: WAMP / XAMPP / Apache

---

## 🚀 Quick Start & Installation

### 1. Prerequisites
Ensure you have a local PHP web server installed (e.g., **WAMP Server** or **XAMPP** with PHP 8+ and MySQL).

### 2. Setup Project
Place the project folder (`PFM_system`) inside your web server directory:
- WAMP: `C:/wamp64/www/PFM_system`
- XAMPP: `C:/xampp/htdocs/PFM_system`

### 3. Database Configuration
1. Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
2. Create a new database named `pfms_db`.
3. Import your SQL schema into `pfms_db`.
4. Verify database credentials in `config/db_config.php`:
   ```php
   $host    = 'localhost';
   $db_user = 'root';
   $db_pass = '';
   $db_name = 'pfms_db';
   ```

### 4. Run the Application
Open your web browser and navigate to:
```text
http://localhost/PFM_system/public/index.php
```

---

## 📂 Project Structure

```text
PFM_system/
├── admin/          # Admin views, scripts, and stylesheets
├── config/         # Database configuration (db_config.php)
├── css/            # Global stylesheets
├── images/         # Static images and logos
├── PHPMailer/      # Email services for password reset
├── public/         # Landing, Login, Register, Forgot Password
├── user/           # User dashboard, transactions, budget, PTPTN, simulation views
└── uploads/        # Uploaded profile images and documents
```

---

## 📄 License
This project is developed for educational and personal finance management purposes.
