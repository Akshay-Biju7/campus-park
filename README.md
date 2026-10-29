# CampusPark – College Parking Management System

**CampusPark** is a complete, modern, and responsive web application designed to replace the traditional handwritten college parking register book with a digital system that securely records student details, vehicle information, and daily college attendance.

Built specifically for universities and colleges supporting **B.Tech, M.Tech, MCA, MBA, and all departmental programs**.

---

## 🌟 Key Features

### 1. Student Portal
- **Secure Registration & Login:** Students register with full name, college ID/roll number, course, department, year of study, email, contact number, and password.
- **Multi-Vehicle Registration:** Students can register bikes, scooters, cars, or bicycles/EVs. Duplicate vehicle registrations are strictly prohibited.
- **Daily Arrival Status Marking:** With a single click, students mark whether their vehicle is **"Brought to College"** (green) or **"Not Brought"** (red/grey) today.
- **Automatic Timestamps:** Records exact entry time and allows optional parking zone remarks.
- **Attendance History:** Students can review their full daily parking attendance ledger across any date.
- **Profile & Password Management:** Self-service profile updates and secure password changes.

### 2. Administrator & Security Control Panel
- **Real-Time KPI Dashboard:**
  - Total registered students
  - Total registered vehicles
  - Vehicles brought to campus today
  - Vehicles marked as not brought today
  - Unmarked / pending check-ins
- **Digital Parking Register Book:**
  - Faithfully replicates the physical college parking logbook in digital format.
  - Complete with token numbers, entry timestamps, student roll numbers, course, department, vehicle types, and license plate badges.
  - Date filtering (Today, Yesterday, Past 7 Days, Custom Date Range, or All-Time).
  - Department, course, vehicle type, and status filtering.
- **Master Student & Vehicle Directory:**
  - Searchable by student name, roll number, plate number, or contact.
  - In-place account status toggle (Activate / Deactivate).
  - Ability to edit student details or register a vehicle directly on behalf of a student.
- **Exporting & Printing:**
  - **1-Click CSV / Excel Export** formatted with UTF-8 BOM for Microsoft Excel.
  - **Printable Register Sheet** with specialized `@media print` layout for official college administration and security records.

---

## 💻 Technology Stack

- **Frontend:** HTML5, CSS3 (Modern custom design system in Navy Blue, White, and Light Grey), JavaScript (ES6+), Bootstrap 5.3.2, Bootstrap Icons
- **Backend:** PHP 8+ (Session-based role authentication, CSRF tokens, secure input validation)
- **Database:** MySQL / MariaDB (Relational schema with primary keys, foreign keys, unique plate constraints, and cascade rules)
- **Development Environment:** XAMPP (Apache + MySQL)

---

## 📂 Project Directory Structure

```text
parking/
│
├── assets/
│   ├── css/
│   │   └── style.css            # Custom responsive navy blue & light grey stylesheet
│   └── js/
│       └── app.js               # Dynamic client script (search filter, auto-fill, clock)
│
├── config/
│   ├── config.php               # Global configuration, constants, courses & departments list
│   └── database.php             # PDO database connection handler & error fallbacks
│
├── database/
│   └── campuspark.sql           # Complete MySQL schema & pre-seeded sample records
│
├── includes/
│   ├── admin_sidebar.php        # Admin navigation sidebar
│   ├── auth.php                 # Session middleware, role guards, CSRF & flash alerts
│   ├── footer.php               # Global footer template
│   ├── functions.php            # Helper domain functions, status badges, and queries
│   ├── header.php               # Top navbar & HTML head template
│   └── student_sidebar.php      # Student navigation sidebar
│
├── admin_dashboard.php          # Admin KPI dashboard & live register preview
├── admin_export.php             # Streaming CSV / Excel download handler
├── admin_login.php              # Administrator login portal
├── admin_parking_records.php    # Official Digital Register Book & date history
├── admin_students_vehicles.php  # Master directory of students and registered vehicles
├── index.php                    # Public landing page with project introduction
├── logout.php                   # Safe session logout handler
├── setup.php                    # 1-Click Database Setup & Auto-Installer Wizard
├── student_daily_status.php     # Daily vehicle arrival status page
├── student_dashboard.php        # Personalized student dashboard
├── student_login.php            # Student login page (roll number or email)
├── student_profile.php          # Student profile & password settings
├── student_register.php         # Student registration page
├── student_vehicles.php         # Vehicle registration & management page
└── README.md                    # Project documentation & setup instructions
```

---

## 🚀 Step-by-Step Instructions to Run using XAMPP

### Step 1: Place the Project in XAMPP `htdocs`
1. Ensure XAMPP is installed on your computer (typically at `C:\xampp`).
2. Copy or move this `parking` project folder into the XAMPP `htdocs` directory:
   ```text
   C:\xampp\htdocs\parking
   ```

### Step 2: Start Apache and MySQL Services
1. Open the **XAMPP Control Panel**.
2. Click the **Start** button next to **Apache**.
3. Click the **Start** button next to **MySQL**.
4. Ensure both services show green status indicators.

### Step 3: Run the 1-Click Database Setup Wizard
1. Open your web browser (Google Chrome, Edge, Firefox, etc.).
2. Navigate to:
   ```text
   http://localhost/parking/setup.php
   ```
3. Click the **"Initialize Database & Seed Sample Records"** button.
4. The wizard will automatically:
   - Create the `campuspark_db` database.
   - Set up tables (`admins`, `students`, `vehicles`, `parking_records`) with proper foreign keys and constraints.
   - Hash passwords with standard bcrypt hashes.
   - Seed ready-to-test student accounts, registered vehicles, and parking records for today and past dates.

*(Alternative Manual Method via phpMyAdmin)*:
- Open `http://localhost/phpmyadmin/`.
- Create a database named `campuspark_db`.
- Import the file located at `database/campuspark.sql`.

### Step 4: Access the Website
Open your browser and navigate to:
```text
http://localhost/parking/index.php
```

---

## 🔑 Pre-Configured Demo Accounts for Testing

Both login portals include convenient **1-Click Quick Fill** buttons so you don't even have to type passwords during evaluation!

### 1. Administrator Account
- **Portal URL:** `http://localhost/parking/admin_login.php`
- **Username:** `admin` (or `admin@campuspark.edu`)
- **Password:** `admin123`
- **Capabilities:** View live digital register, filter by date/course/department, edit gate entry times and remarks, activate/deactivate accounts, export CSV.

### 2. Sample Student Accounts
- **Portal URL:** `http://localhost/parking/student_login.php`
- **Password for all demo students:** `student123`

| Student ID / Roll No | Student Name | Course & Department | Registered Vehicles |
| :--- | :--- | :--- | :--- |
| `22BTCS045` | Rahul Sharma | B.Tech - Computer Science & Engineering | Bike (`MH 12 AB 4590`), Scooter (`MH 12 CD 1245`) |
| `23BTEC019` | Priya Patel | B.Tech - Electronics & Comm. Engg | Scooter (`DL 04 ES 8912`) |
| `24MTDS008` | Mohammed Ansar | M.Tech - AI & Data Science | Car (`KA 05 MC 3344`) |
| `21BTCE031` | Ananya Nair | B.Tech - Civil Engineering | Scooter (`TN 09 BK 7765`) |
| `22BTME072` | Siddharth Verma | B.Tech - Mechanical Engineering | Bike (`MH 14 GH 9988`) |
| `23MCA015` | Sneha Roy | MCA - Master of Computer Applications | Car (`WB 02 AX 5511`), EV (`WB 02 EV 0909`) |

---

## 🗄️ Relational Database Schema

### 1. `admins`
Stores authorized administrator credentials for college security staff.
- `admin_id` (INT, Primary Key, Auto Increment)
- `username` (VARCHAR(50), Unique)
- `password_hash` (VARCHAR(255))
- `full_name` (VARCHAR(100))
- `email` (VARCHAR(100), Unique)
- `created_at` (TIMESTAMP)

### 2. `students`
Stores student academic and contact profiles.
- `student_id` (INT, Primary Key, Auto Increment)
- `roll_number` (VARCHAR(30), Unique, Indexed)
- `full_name` (VARCHAR(100))
- `email` (VARCHAR(100), Unique)
- `password_hash` (VARCHAR(255))
- `contact_number` (VARCHAR(20))
- `course` (VARCHAR(50))
- `department` (VARCHAR(100))
- `year_of_study` (VARCHAR(20))
- `is_active` (TINYINT(1), Default 1)
- `created_at` (TIMESTAMP)

### 3. `vehicles`
Stores vehicles belonging to registered students.
- `vehicle_id` (INT, Primary Key, Auto Increment)
- `student_id` (INT, Foreign Key -> `students.student_id` ON DELETE CASCADE)
- `vehicle_type` (ENUM: 'Bike', 'Scooter', 'Car', 'Bicycle/EV', 'Other')
- `registration_number` (VARCHAR(30), Unique, Indexed)
- `vehicle_model` (VARCHAR(60), Optional)
- `colour` (VARCHAR(30), Optional)
- `is_active` (TINYINT(1), Default 1)
- `registration_date` (TIMESTAMP)

### 4. `parking_records`
Stores daily parking arrival and departure logs.
- `record_id` (INT, Primary Key, Auto Increment)
- `student_id` (INT, Foreign Key -> `students.student_id` ON DELETE CASCADE)
- `vehicle_id` (INT, Foreign Key -> `vehicles.vehicle_id` ON DELETE CASCADE)
- `parking_date` (DATE, Indexed)
- `arrival_status` (ENUM: 'brought', 'not_brought', Indexed)
- `entry_time` (TIME, Optional)
- `remarks` (VARCHAR(255), Optional)
- `updated_at` (TIMESTAMP)
- **Constraint:** `UNIQUE KEY (vehicle_id, parking_date)` ensures each vehicle has exactly one daily status log per calendar date.

---

## 🛡️ Security Implementation

1. **SQL Injection Protection:** All database interactions use PHP Data Objects (PDO) with prepared statements and parameter binding.
2. **Password Hashing:** Passwords are encrypted using PHP's native `password_hash()` with the strong `PASSWORD_DEFAULT` (bcrypt) algorithm and validated via `password_verify()`.
3. **Cross-Site Request Forgery (CSRF):** Crucial forms generate and validate unique random cryptographic session tokens.
4. **Role-Based Session Isolation:** Middleware guards (`require_student()` and `require_admin()`) prevent unauthorized cross-role access and protect personal student contact information.
5. **Cross-Site Scripting (XSS) Prevention:** All dynamic browser outputs are sanitized with `htmlspecialchars()` using UTF-8 flags.
6. **Vehicle Plate Uniqueness:** Duplicate vehicle license plates are validated on both frontend and backend to stop fraudulent entries.

---

## 💡 Troubleshooting & FAQs

- **Q: "Cannot connect to MySQL server" message appears.**
  - **A:** Open your XAMPP Control Panel and verify that MySQL is started on port 3306. If port 3306 is occupied by another local MySQL installation, update `DB_HOST` in `config/config.php` (e.g. `127.0.0.1:3307`).
- **Q: How do I change default database credentials?**
  - **A:** Open `config/config.php` and edit lines 28–31 (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).
- **Q: How do I print the register book?**
  - **A:** Navigate to the **Digital Register Book** page (`admin_parking_records.php`) and click **Print Register Sheet**. The page will automatically hide all sidebars and buttons, presenting a clean print-ready format.
