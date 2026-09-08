# Railway + PostgreSQL Production Deployment Guide

This guide walks you step-by-step through deploying the **Online Learning Platform** to **Railway** by adding the **PostgreSQL Database Service into the exact same Railway Project** alongside the web application service.

---

## 🏗️ Railway Same-Project Service Architecture

In Railway, both your **Web Application Service** and **PostgreSQL Database Service** run together inside the **same Railway project environment**.

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

---

## 🚀 Step 1: Railway Account & Project Setup
1. Visit [Railway.app](https://railway.app) and sign in using your GitHub account.
2. Click **New Project** on your Railway dashboard.
3. Select **Deploy from GitHub repo** and choose your `Online-Learning-Platform` repository.

---

## 🗄️ Step 2: Add PostgreSQL Database Service to the SAME Project
1. Open your newly created Railway Project canvas.
2. Click the **+ New** button inside the project canvas.
3. Select **Database** -> **Add PostgreSQL**.
4. Railway will instantly provision a dedicated PostgreSQL database service within your project alongside your web service.

---

## 🔗 Step 3: Link PostgreSQL Database to Web Application Service
1. Click on your **Web Application Service** card inside the Railway Project canvas.
2. Navigate to the **Variables** tab.
3. Click **Add Reference Variable** or select **DATABASE_URL**.
4. Set the value to:
   ```env
   DATABASE_URL=${{ Postgres.DATABASE_URL }}
   ```
   *(Railway will automatically link the database host, port, credentials, and database name from the PostgreSQL service in the same project).*

5. Add additional application environment variables:

| Variable | Value / Description |
| :--- | :--- |
| `DATABASE_URL` | `${{ Postgres.DATABASE_URL }}` (Auto-linked from PostgreSQL service) |
| `APP_ENV` | `production` |
| `APP_URL` | `https://${{ RAILWAY_PUBLIC_DOMAIN }}` |
| `SESSION_SECRET` | Generate a random 32-character secret key |
| `ADMIN_SETUP_KEY` | Set your custom secure setup key (e.g. `RailwayAdminSetup2026`) |

---

## 🛢️ Step 4: Initialize PostgreSQL Database Schema & Seed Data
Connect to the PostgreSQL database service in your Railway project:

### Method A: Via Railway Project Query Editor (Simplest)
1. Click on the **PostgreSQL Service** card in your Railway project canvas.
2. Open the **Data** or **Query** tab.
3. Open [database/complete_database.sql](file:///d:/Saas%20Development%20project/WEBSITE/Online-Learning-Platform-main/database/complete_database.sql), copy all content, and run it.
4. Open [database/seed.sql](file:///d:/Saas%20Development%20project/WEBSITE/Online-Learning-Platform-main/database/seed.sql), copy all content, and run it.

### Method B: Via Railway CLI `psql`
```bash
railway connect Postgres < database/complete_database.sql
railway connect Postgres < database/seed.sql
```

---

## 👑 Step 5: Create the First Administrator Account
Run the secure admin setup script:

### Method A: CLI (Recommended)
```bash
php create_admin.php admin@yourdomain.com main_admin "Platform Administrator" SuperSecurePassword123!
```

### Method B: Browser Web Setup
1. Open your deployed Railway domain: `https://<your-project-domain>.up.railway.app/create_admin.php`
2. Enter Admin details, password, and your `ADMIN_SETUP_KEY`.
3. Click **Create Administrator**.

---

## 🧪 Step 6: Verification Checklist

| Test Case | Procedure | Expected Result |
| :--- | :--- | :--- |
| **Customer Registration** | Open `/VIEWS/USER/Student_Registration.html` and register | Record created in PostgreSQL `users` & `profiles` tables inside Railway project. |
| **Customer Login** | Login with student credentials | Verifies password hash against PostgreSQL database service. Secure session established. |
| **Admin Login** | Login with admin credentials | Verified against PostgreSQL database service. Role `admin` confirmed. |
| **Authorization Enforcement** | Attempt student access to `/VIEWS/USER/courses_admin.php` | Access blocked with 403 Forbidden. |
| **Course CRUD & Synchronization** | Admin creates/edits a course in `/VIEWS/USER/courses_admin.php` | Updated course info saved in PostgreSQL service and instantly reflected on customer dashboard. |

---

## 💾 Step 7: Database Export & Backup
Export your Railway PostgreSQL database at any time:
```bash
railway connect Postgres "pg_dump > railway_backup.sql"
```
