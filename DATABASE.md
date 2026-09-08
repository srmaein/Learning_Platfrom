# Database Documentation: PostgreSQL Single Source of Truth

This document describes the relational architecture, table structures, constraints, and relationships for the **Online Learning Platform** PostgreSQL database.

---

## Entity Relationship Diagram Overview

```
                      ┌───────────────────┐
                      │       users       │
                      │ (id, email, role) │
                      └─────────┬─────────┘
                                │ 1:1
                                ▼
                      ┌───────────────────┐
                      │     profiles      │
                      │ (user_id, name)   │
                      └───────────────────┘
                                │
        ┌───────────────────────┼───────────────────────┐
        │ 1:N                   │ 1:N                   │ 1:N
        ▼                       ▼                       ▼
┌───────────────┐       ┌───────────────┐       ┌───────────────┐
│    courses    │       │  enrollments  │       │   payments    │
│ (id, title)   │       │(user, course) │       │ (txid, amount)│
└───────┬───────┘       └───────────────┘       └───────────────┘
        │ 1:N
        ▼
┌───────────────┐
│    lessons    │
│ (course_id)   │
└───────────────┘
```

---

## 🗄️ Database Tables Specification

### 1. `users` (Core Authentication Table)
Stores single source of truth user credentials, roles, and status across Admin, Student, and Teacher user types.

| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `SERIAL` | `PRIMARY KEY` | Unique User Identifier |
| `email` | `VARCHAR(150)` | `NOT NULL UNIQUE` | Account Email Address |
| `username` | `VARCHAR(100)` | `NOT NULL UNIQUE` | Account Username |
| `password_hash` | `VARCHAR(255)` | `NOT NULL` | Password Hash (BCRYPT) |
| `role` | `VARCHAR(20)` | `CHECK IN ('admin', 'student', 'teacher')` | User Role |
| `status` | `VARCHAR(20)` | `CHECK IN ('ACTIVE', 'DISABLED', 'PENDING')` | Account Status |
| `reset_token` | `VARCHAR(255)` | `DEFAULT NULL` | Password Reset Token |
| `reset_token_expiry` | `TIMESTAMPTZ` | `DEFAULT NULL` | Token Expiry Timestamp |
| `last_login_at` | `TIMESTAMPTZ` | `DEFAULT NULL` | Last Authentication Time |
| `created_at` | `TIMESTAMPTZ` | `DEFAULT CURRENT_TIMESTAMP` | Account Creation Date |
| `updated_at` | `TIMESTAMPTZ` | `DEFAULT CURRENT_TIMESTAMP` | Automatic Update Timestamp |

---

### 2. `profiles` (User Meta & Details)
Holds personal, demographic, and qualification records linked 1:1 with `users`.

| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `SERIAL` | `PRIMARY KEY` | Profile ID |
| `user_id` | `INT` | `UNIQUE REFERENCES users(id) ON DELETE CASCADE` | 1:1 Link to User |
| `first_name` | `VARCHAR(100)` | `NULL` | First Name |
| `last_name` | `VARCHAR(100)` | `NULL` | Last Name |
| `full_name` | `VARCHAR(200)` | `NULL` | Complete Full Name |
| `age` | `INT` | `NULL` | User Age |
| `date_of_birth` | `DATE` | `NULL` | Birth Date |
| `gender` | `VARCHAR(20)` | `CHECK IN ('male', 'female', 'other')` | Gender |
| `blood_group` | `VARCHAR(10)` | `CHECK IN ('A-', 'A+', 'B-', 'B+', 'AB-', 'AB+', 'O-', 'O+')` | Blood Group |
| `phone_number` | `VARCHAR(30)` | `NULL` | Contact Phone Number |
| `address` | `TEXT` | `NULL` | Physical Address |
| `qualifications` | `TEXT` | `NULL` | Educational Qualifications |
| `teacher_user_id` | `VARCHAR(50)` | `UNIQUE` | Legacy Teacher Code (e.g. 2200-1000) |

---

### 3. `categories`
Organizes courses into distinct subject domain areas.

| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `SERIAL` | `PRIMARY KEY` | Category ID |
| `name` | `VARCHAR(100)` | `NOT NULL UNIQUE` | Category Name |
| `slug` | `VARCHAR(100)` | `NOT NULL UNIQUE` | URL-friendly Slug |
| `description` | `TEXT` | `NULL` | Category Overview |

---

### 4. `courses`
Stores course catalog data managed by Administrators and viewable by Students.

| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `SERIAL` | `PRIMARY KEY` | Course ID |
| `course_code` | `VARCHAR(50)` | `UNIQUE` | Internal Code (e.g. uiux101) |
| `title` | `VARCHAR(255)` | `NOT NULL` | Course Title |
| `slug` | `VARCHAR(255)` | `NOT NULL UNIQUE` | URL Slug |
| `description` | `TEXT` | `NULL` | Complete Description |
| `category_id` | `INT` | `REFERENCES categories(id) ON DELETE SET NULL` | Category Foreign Key |
| `instructor_id` | `INT` | `REFERENCES users(id) ON DELETE SET NULL` | Instructor Foreign Key |
| `price` | `NUMERIC(10,2)` | `DEFAULT 0.00` | Course Fee (BDT) |
| `duration` | `VARCHAR(100)` | `NULL` | Course Duration |
| `level` | `VARCHAR(50)` | `DEFAULT 'Beginner'` | Complexity Level |
| `thumbnail` | `TEXT` | `NULL` | Thumbnail Image Path/URL |
| `is_published` | `BOOLEAN` | `DEFAULT TRUE` | Publish Visibility Toggle |

---

### 5. `lessons`
Individual tutorial topics belonging to a parent course.

| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `SERIAL` | `PRIMARY KEY` | Lesson ID |
| `course_id` | `INT` | `REFERENCES courses(id) ON DELETE CASCADE` | Course Foreign Key |
| `title` | `VARCHAR(255)` | `NOT NULL` | Lesson Title |
| `content` | `TEXT` | `NULL` | Textual Lesson Notes |
| `video_url` | `TEXT` | `NULL` | Video Embed Link |
| `sort_order` | `INT` | `DEFAULT 1` | Lesson Display Order |

---

### 6. `enrollments`
Tracks student course enrollments and progress metrics.

| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `SERIAL` | `PRIMARY KEY` | Enrollment ID |
| `user_id` | `INT` | `REFERENCES users(id) ON DELETE CASCADE` | Student User FK |
| `course_id` | `INT` | `REFERENCES courses(id) ON DELETE CASCADE` | Course FK |
| `status` | `VARCHAR(20)` | `CHECK IN ('ENROLLED', 'COMPLETED', 'CANCELLED')` | Status |
| `progress_percent`| `INT` | `DEFAULT 0 CHECK (0..100)` | Completion Percentage |
| `enrolled_at` | `TIMESTAMPTZ` | `DEFAULT CURRENT_TIMESTAMP` | Enrollment Date |

**Unique Constraint:** `(user_id, course_id)` prevents duplicate enrollments for the same user.

---

### 7. `payments`
Financial transaction history for course purchases.

| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `SERIAL` | `PRIMARY KEY` | Payment Record ID |
| `transaction_id` | `VARCHAR(255)` | `NOT NULL UNIQUE` | Gateway Transaction ID |
| `user_id` | `INT` | `REFERENCES users(id) ON DELETE SET NULL` | User FK |
| `email` | `VARCHAR(255)` | `NOT NULL` | Payer Email |
| `course_name` | `VARCHAR(255)` | `NOT NULL` | Purchased Course Title |
| `amount` | `VARCHAR(50)` | `NOT NULL` | Transaction Amount |
| `payment_method` | `VARCHAR(50)` | `NOT NULL` | Gateway (bKash/Nagad/Visa) |
| `status` | `VARCHAR(50)` | `DEFAULT 'Completed'` | Payment Status |

---

## 🔒 Indexing Strategy
- `idx_users_email` on `users(email)`
- `idx_users_username` on `users(username)`
- `idx_users_role` on `users(role)`
- `idx_courses_published` on `courses(is_published)`
- `idx_enrollments_user_id` on `enrollments(user_id)`
- `idx_payments_transaction_id` on `payments(transaction_id)`
