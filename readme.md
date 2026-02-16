# User Management System

A comprehensive **Role-Based Access Control (RBAC)** system with user management, task assignment, and two-factor authentication.

![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=flat&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?style=flat&logo=bootstrap&logoColor=white)

---

## ✨ Features

- 🔐 **Secure Authentication** - Login/Logout with session management
- 🔑 **Two-Factor Authentication** - TOTP-based 2FA
- 👥 **User Management** - Complete CRUD with role assignment
- 📝 **Task Management** - Create, assign, and track tasks
- 🎯 **Role-Based Access** - Super Admin, Admin, Manager, User
- 📊 **Dashboard** - Real-time statistics and analytics
- 🔒 **Security** - Password hashing, SQL injection prevention, XSS protection

---

## 🛠️ Tech Stack

**Backend:** PHP 8.4 (OOP) • MySQL • PDO  
**Frontend:** Bootstrap 5 • jQuery • SweetAlert2 • Font Awesome  
**Security:** bcrypt • Environment Variables • CSRF Protection

---

## 📦 Quick Start

### Prerequisites
- PHP 8.0+
- MySQL 5.7+
- Apache/Nginx

### Installation

```bash
# Clone repository
git clone https://github.com/Sanedo22/user-management-system.git
cd user-management-system

# Configure environment
cp .env.example .env
# Edit .env with your database credentials

# Create database
mysql -u root -p
CREATE DATABASE user_management_system;

# Access application
http://localhost/user-management-system/
```

---

## 🔑 Demo Credentials

| Role | Email | Password |
|------|-------|----------|
| **Super Admin** | admin@admin.com | admin123 |
| **Admin** | john.admin@company.com | password123 |
| **Manager** | sarah.manager@company.com | password123 |
| **User** | emma.user@company.com | password123 |

---

## 📁 Project Structure

```
user_management-system/
├── admin/          # Admin panel pages
├── assets/         # CSS, JS, images
├── config/         # Database & constants
├── includes/       # Services & components
├── .env            # Environment config
└── index.php       # Entry point
```

---

## 🔒 Security Features

✅ Password Hashing (bcrypt)  
✅ SQL Injection Prevention (PDO)  
✅ XSS Protection  
✅ CSRF Protection  
✅ Session Security  
✅ 2FA Support  
✅ Environment Variables  

---

## 📄 License

MIT License - see [LICENSE](LICENSE) file

---

## 👨‍💻 Author

**Dhruv Ppatni**  
GitHub: [@Sanedo22](https://github.com/Sanedo22)

---

<p align="center">Made with ❤️ using PHP</p>
