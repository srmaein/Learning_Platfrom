-- =============================================================================
-- ONLINE LEARNING PLATFORM - POSTGRESQL SEED DATA (PHASE 2)
-- Migrated from online_education.sql & application catalog
-- =============================================================================

BEGIN;

-- -----------------------------------------------------------------------------
-- 1. CATEGORIES SEED DATA
-- -----------------------------------------------------------------------------
INSERT INTO categories (id, name, slug, description, icon) VALUES
(1, 'UI/UX Design', 'ui-ux-design', 'Learn modern UI/UX design techniques, Figma, wireframing and prototyping.', 'fas fa-palette'),
(2, '3D Modeling & Architecture', '3d-modeling', 'Master 3D structural expert tools, render engines, and texturing.', 'fas fa-cubes'),
(3, 'Programming & Software Development', 'programming', 'Learn web development, JavaScript, Python, PHP, and modern frameworks.', 'fas fa-code'),
(4, 'Digital Marketing', 'digital-marketing', 'SEO, social media marketing, PPC campaigns, and branding strategies.', 'fas fa-bullhorn'),
(5, 'Mobile Development', 'mobile-development', 'Cross-platform app development using Flutter, Dart, and React Native.', 'fas fa-mobile-alt'),
(6, 'Data Science & Analytics', 'data-science', 'Python data analysis, machine learning algorithms, and data visualization.', 'fas fa-chart-line'),
(7, 'Photography & Video', 'photography', 'Camera optics, lighting setups, color grading, and video editing.', 'fas fa-camera')
ON CONFLICT (id) DO NOTHING;

SELECT setval('categories_id_seq', (SELECT MAX(id) FROM categories));

-- -----------------------------------------------------------------------------
-- 2. USERS & PROFILES SEED DATA
-- -----------------------------------------------------------------------------

-- Teachers
INSERT INTO users (id, email, username, password_hash, role, status) VALUES
(1, 'hasan@gmail.com', 'Hasan', '$2y$10$6i2lE4z6Q29R.iIHacDSAOdA.Mu85JQjA0yUc7ipbV3UWHncEvkSq', 'teacher', 'ACTIVE'),
(2, 'karim.abdul@example.com', 'karim45', '$2y$10$58ybo1W5pQ.qXrJNFEeGROE/uit22Og7qiyaHAkpfy2SWf9RTx5yK', 'teacher', 'ACTIVE')
ON CONFLICT (id) DO NOTHING;

INSERT INTO profiles (user_id, first_name, last_name, full_name, age, date_of_birth, blood_group, phone_number, address, qualifications, teacher_user_id) VALUES
(1, 'Mohammad', 'Hasan', 'Mohammad Hasan', 30, '1994-12-14', 'B-', '0134574124', 'Mirpur, Dhaka', 'PhD, Masters', '2200-1000'),
(2, 'Abdul', 'Karim', 'Abdul Karim', 45, '1979-02-15', 'A+', '01712345678', 'Dhaka, Bangladesh', 'MSc in Mathematics', '2201-1201')
ON CONFLICT (user_id) DO NOTHING;

-- Students
INSERT INTO users (id, email, username, password_hash, role, status) VALUES
(3, 'meain@gmail.com', 'meain_student', '$2y$10$pxn2YblmCivzLXLg4G/ykOY.sKsPqXbSPjF0LqBwYRfkA8mDw2p1K', 'student', 'ACTIVE'),
(4, 'rahim.uddin@example.com', 'rahim_student', '$2y$10$pxn2YblmCivzLXLg4G/ykOY.sKsPqXbSPjF0LqBwYRfkA8mDw2p1K', 'student', 'ACTIVE'),
(5, 'sadman@gmail.com', 'sadman_student', '$2y$10$8PvhEPs3da.FRVrz/Zchn.0ftaxwe8G1IQxjVNaMy7h6IEBX.dDHG', 'student', 'ACTIVE')
ON CONFLICT (id) DO NOTHING;

INSERT INTO profiles (user_id, first_name, last_name, full_name, gender, blood_group, phone_number) VALUES
(3, 'Meain', 'Rahman', 'Meain Rahman', 'male', 'A-', '01784521471'),
(4, 'Rahim', 'Uddin', 'Rahim Uddin', 'male', 'A+', '01710001122'),
(5, 'Sadman', 'Rahman', 'AKMSadman Rahman', 'male', 'B+', '01784521471')
ON CONFLICT (user_id) DO NOTHING;

SELECT setval('users_id_seq', (SELECT MAX(id) FROM users));

-- -----------------------------------------------------------------------------
-- 3. COURSES SEED DATA
-- -----------------------------------------------------------------------------
INSERT INTO courses (id, course_code, title, slug, description, category_id, instructor_id, price, duration, level, thumbnail, tutorials_count, is_published) VALUES
(1, 'uiux101', 'Learn UI UX with ZHF Design Studio', 'learn-ui-ux-with-zhf-design-studio', 'Master user interface design, visual hierarchy, micro-interactions, and Figma workflow with ZHF Studio.', 1, 1, 6500.00, '08:30 hours/Daily', 'Intermediate', 'PUBLIC/pic/Learn UI UX with ZHF Design Studio.png', 35, TRUE),
(2, '3d201', 'Structure Expert - Making Things Look 3D', 'structure-expert-making-things-look-3d', 'Comprehensive 3D design and structural visualization course covering lighting, texturing, and spatial rendering.', 2, 1, 500.00, '08:00 hours/Daily', 'Advanced', 'PUBLIC/pic/Structure Expert - Making Things Look 3D.jpg', 120, TRUE),
(3, 'prog101', 'Learn Programming FAST! My Favorite Method!', 'learn-programming-fast-my-favorite-method', 'Accelerated modern programming methodology covering problem solving, algorithms, web fundamentals, and software principles.', 3, 2, 1500.00, '03:40 hours/Daily', 'Beginner', 'PUBLIC/pic/Learn Programming FAST! My Favorite Method.jpg', 55, TRUE),
(4, 'mktg202', 'Digital Marketing Masterclass 2023', 'digital-marketing-masterclass-2023', 'Complete digital marketing guide: SEO, social media strategies, audience analytics, and conversion funnels.', 4, 2, 3999.00, '04:00 hours/Daily', 'All Levels', 'PUBLIC/pic/img.jpg', 40, TRUE),
(5, 'mob202', 'Mobile App Development with Flutter', 'mobile-app-development-with-flutter', 'Build cross-platform iOS and Android applications with Google Flutter and Dart programming language.', 5, 1, 7500.00, '06:00 hours/Daily', 'Intermediate', 'PUBLIC/pic/img.jpg', 80, TRUE),
(6, 'fsd301', 'Full Stack Development', 'full-stack-development', 'Become a complete full-stack web developer mastering HTML, CSS, JavaScript, PHP, PostgreSQL, and REST APIs.', 3, 2, 8500.00, '10:00 hours/Daily', 'Advanced', 'PUBLIC/pic/img.jpg', 95, TRUE),
(7, 'ds401', 'Data Science & Machine Learning Fundamentals', 'data-science-machine-learning-fundamentals', 'Introduction to Python data science stack: NumPy, Pandas, Scikit-Learn, and statistical analysis.', 6, 2, 9000.00, '05:30 hours/Daily', 'Intermediate', 'PUBLIC/pic/img.jpg', 70, TRUE),
(8, 'photo101', 'Photography & Lighting Secrets', 'photography-lighting-secrets', 'Professional camera techniques, studio lighting setups, portrait framing, and post-processing techniques.', 7, 1, 2500.00, '03:00 hours/Daily', 'Beginner', 'PUBLIC/pic/img.jpg', 30, TRUE)
ON CONFLICT (id) DO NOTHING;

SELECT setval('courses_id_seq', (SELECT MAX(id) FROM courses));

-- -----------------------------------------------------------------------------
-- 4. LESSONS SEED DATA
-- -----------------------------------------------------------------------------
INSERT INTO lessons (id, course_id, title, content, video_url, duration, sort_order) VALUES
(1, 1, 'Introduction to UI/UX Principles', 'Understand core user experience principles, design thinking, and usability guidelines.', 'https://www.youtube.com/embed/dQw4w9WgXcQ', '15 mins', 1),
(2, 1, 'Figma Interface & Design Grid', 'Mastering the Figma workspace, layout grids, components, and design tokens.', 'https://www.youtube.com/embed/dQw4w9WgXcQ', '25 mins', 2),
(3, 2, '3D Spatial Concepts & Wireframes', 'Understanding depth, perspective, light refraction, and shadow projection in 3D environments.', 'https://www.youtube.com/embed/dQw4w9WgWgQ', '30 mins', 1),
(4, 3, 'Programming Fundamentals & Logic', 'Variables, control flows, data structures, and algorithmic thinking.', 'https://www.youtube.com/embed/dQw4w9WgXcQ', '20 mins', 1),
(5, 6, 'Full Stack Overview & HTTP Protocol', 'Client-server architecture, HTTP requests/responses, RESTful APIs, and database fundamentals.', 'https://www.youtube.com/embed/dQw4w9WgXcQ', '45 mins', 1)
ON CONFLICT (id) DO NOTHING;

SELECT setval('lessons_id_seq', (SELECT MAX(id) FROM lessons));

-- -----------------------------------------------------------------------------
-- 5. ENROLLMENTS & PAYMENTS SEED DATA
-- -----------------------------------------------------------------------------
INSERT INTO enrollments (id, user_id, course_id, status, progress_percent) VALUES
(1, 3, 1, 'ENROLLED', 65),
(2, 3, 2, 'ENROLLED', 30),
(3, 3, 3, 'ENROLLED', 88),
(4, 4, 4, 'ENROLLED', 10)
ON CONFLICT (user_id, course_id) DO NOTHING;

SELECT setval('enrollments_id_seq', (SELECT MAX(id) FROM enrollments));

INSERT INTO payments (id, transaction_id, user_id, email, course_name, course_id, amount, currency, payment_method, status, transaction_date) VALUES
(1, 'tr568965gv76', 3, 'meain@gmail.com', 'Learn UI UX with ZHF Design Studio', 1, 'BDT 6500', 'BDT', 'bKash', 'COMPLETED', '2025-05-01 21:15:44+00'),
(2, 'teyhgterhetr', 3, 'meain@gmail.com', 'Learn UI UX with ZHF Design Studio', 1, 'BDT 6500', 'BDT', 'Nagad', 'COMPLETED', '2025-05-01 22:12:48+00'),
(3, 'trgv9087407b', 3, 'meain@gmail.com', 'Structure Expert - Making Things Look 3D', 2, 'BDT 500', 'BDT', 'Nagad', 'COMPLETED', '2025-05-01 22:19:50+00'),
(4, 'ytrfg565645645', 4, 'rahim.uddin@example.com', 'Digital Marketing Masterclass 2023', 4, 'BDT 3999', 'BDT', 'bKash', 'COMPLETED', '2025-05-01 22:20:39+00')
ON CONFLICT (id) DO NOTHING;

SELECT setval('payments_id_seq', (SELECT MAX(id) FROM payments));

COMMIT;
