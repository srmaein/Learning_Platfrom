# 🎓 Online Learning Platform (Railway + PostgreSQL Edition)

Production-ready Online Learning Platform migrated from legacy MySQL to a single source of truth powered by **Railway + PostgreSQL**.

---

## 🌟 Architecture & Features

```
┌────────────────────────────────────────────────────────────────────────┐
│                        RAILWAY PROJECT CANVAS                          │
│                                                                        │
│  ┌─────────────────────────────┐        ┌───────────────────────────┐  │
│  │   WEB APPLICATION SERVICE   │        │  POSTGRESQL DB SERVICE    │  │
│  │  (PHP 8.2 + Apache / Nginx) │        │  (Railway PostgreSQL 15)  │  │
│  │                             │        │                           │  │
│  │  Reads: ${DATABASE_URL}     │◄───────┼─ Provides:                │  │
│  │         (Auto-linked)       │        │  DATABASE_URL             │  │
│  └──────────────┬──────────────┘        └───────────────────────────┘  │
│                 │                                                      │
└─────────────────┼──────────────────────────────────────────────────────┘
                  │ HTTPS Public Domain
                  ▼
          End Users / Customers / Admin
```

### Key Functional Systems:
- 🔐 **Real Database Authentication**:
  - Unified `users` & `profiles` tables for `ADMIN`, `STUDENT`, and `TEACHER` roles.
  - Secure password hashing (`password_hash` with BCRYPT) & verification (`password_verify`).
  - Session security: HttpOnly cookies, SameSite=Lax, session regeneration, 2-hour inactivity timeout.
- 🛡️ **Role-Based Authorization**:
  - Server-side access enforcement (`require_auth(['admin'])`).
  - Students cannot access `/admin` or admin dashboards (returns 403 Forbidden).
- 📚 **Live Course CRUD & Real-Time Sync**:
  - Admin can create, edit, delete, publish, and unpublish courses live in PostgreSQL.
  - Student views dynamically load published courses from PostgreSQL without stale data.
- 📊 **Real-Time Dashboards**:
  - Admin Dashboard computes live metrics (`Total Students`, `Active Courses`, `Total Instructors`, `Enrollments`, `Recent Transactions`).
- 💳 **Payment & Enrollment System**:
  - Live payment recording and automatic course enrollment.

---

## 🛠️ Technology Stack

- **Backend**: PHP 8.1+ (PDO Database Layer)
- **Database**: Railway PostgreSQL 13+ (Same-Project Service)
- **Frontend**: HTML5, Vanilla CSS, JavaScript, jQuery, Font Awesome
- **Deployment Platform**: Railway (Nixpacks build with PHP 8.2 & `pdo_pgsql`)

---

## 🚀 Quick Start & Local Setup

### 1. Prerequisites
- PHP 8.1 or higher with `pdo_pgsql` extension enabled.
- PostgreSQL database server (Local or Railway PostgreSQL).

### 2. Environment Configuration
Copy `.env.example` to `.env` and fill in your credentials:
```env
DATABASE_URL=postgresql://postgres:postgres@127.0.0.1:5432/online_education
APP_ENV=development
APP_URL=http://localhost/Online-Learning-Platform
SESSION_SECRET=your_32_character_secret_key
ADMIN_SETUP_KEY=RailwayAdminSetup2026
```

### 3. Database Initialization
Execute the SQL setup scripts in order:
```bash
psql -U postgres -d online_education -f database/complete_database.sql
psql -U postgres -d online_education -f database/seed.sql
```

### 4. Create Initial Administrator Account
Run the secure admin creation script:
```bash
php create_admin.php admin@example.com admin "System Admin" SuperPassword123!
```

---

## 🚂 Railway Production Deployment (Same Project Setup)

Follow the complete step-by-step guide in [RAILWAY_DEPLOYMENT.md](file:///d:/Saas%20Development%20project/WEBSITE/Online-Learning-Platform-main/RAILWAY_DEPLOYMENT.md):

1. Create a new Railway project and deploy your GitHub repo.
2. In the **same Railway project canvas**, click **+ New** -> **Database** -> **Add PostgreSQL**.
3. Link your web application to PostgreSQL by adding variable `DATABASE_URL=${{ Postgres.DATABASE_URL }}`.
4. Run [database/complete_database.sql](file:///d:/Saas%20Development%20project/WEBSITE/Online-Learning-Platform-main/database/complete_database.sql) & [database/seed.sql](file:///d:/Saas%20Development%20project/WEBSITE/Online-Learning-Platform-main/database/seed.sql) on the PostgreSQL service.
5. Execute `create_admin.php` to generate the initial admin credentials.

---

## 📄 Documentation Links

- [RAILWAY_DEPLOYMENT.md](file:///d:/Saas%20Development%20project/WEBSITE/Online-Learning-Platform-main/RAILWAY_DEPLOYMENT.md) - Railway same-project deployment manual
- [DATABASE.md](file:///d:/Saas%20Development%20project/WEBSITE/Online-Learning-Platform-main/DATABASE.md) - PostgreSQL schema, tables & indexes
- [database/complete_database.sql](file:///d:/Saas%20Development%20project/WEBSITE/Online-Learning-Platform-main/database/complete_database.sql) - Database DDL script
- [database/seed.sql](file:///d:/Saas%20Development%20project/WEBSITE/Online-Learning-Platform-main/database/seed.sql) - Reference seed data
