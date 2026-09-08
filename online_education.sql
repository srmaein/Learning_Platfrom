-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 25, 2025 at 06:18 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `online_education`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_registration`
--

CREATE TABLE `admin_registration` (
  `id` int(11) NOT NULL,
  `admin_name` varchar(100) NOT NULL,
  `age` int(11) NOT NULL,
  `date_of_birth` date NOT NULL,
  `blood_group` enum('A-','A+','B-','B+','AB-','AB+','O-','O+') NOT NULL,
  `phone_number` varchar(11) NOT NULL,
  `address` text NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_registration`
--

INSERT INTO `admin_registration` (`id`, `admin_name`, `age`, `date_of_birth`, `blood_group`, `phone_number`, `address`, `email`, `username`, `password`, `created_at`) VALUES
(1, 'Abdul Rahman', 26, '1997-07-16', 'B-', '01788987412', 'mirpur', 'abdul@gmail.com', 'Abdul123', '$2y$10$58ybo1W5pQ.qXrJNFEeGROE/uit22Og7qiyaHAkpfy2SWf9RTx5yK', '2025-05-01 16:49:35'),
(2, 'Shahriar Kabir', 40, '1984-06-15', 'A+', '01710002233', 'Dhaka, Bangladesh', 'shahriar.kabir@example.com', 'shahriar_admin', 'admin123', '2025-05-01 16:52:07'),
(3, 'Nazia Akter', 35, '1989-03-10', 'B-', '01811003344', 'Chittagong, Bangladesh', 'nazia.akter@example.com', 'nazia_admin', 'secureadmin', '2025-05-01 16:52:07'),
(4, 'Rashed Hossain', 45, '1979-12-20', 'O+', '01999005566', 'Sylhet, Bangladesh', 'rashed.hossain@example.com', 'rashed_admin', 'rashed2024', '2025-05-01 16:52:07'),
(5, 'Farzana Yasmin', 38, '1986-09-05', 'AB+', '01722007788', 'Rajshahi, Bangladesh', 'farzana.y@example.com', 'farzana_admin', 'farzana456', '2025-05-01 16:52:07'),
(6, 'Jamal Uddin', 50, '1974-01-25', 'A-', '01688009900', 'Khulna, Bangladesh', 'jamal.uddin@example.com', 'jamal_admin', 'jamal@admin', '2025-05-01 16:52:07'),
(7, 'Sadman Rahman', 24, '1997-02-04', 'A-', '01578412011', 'mirpur arambag', 'sadmanr2@gmail.com', 'sadmanr2', '$2y$10$Q7J2V8CMePBcBjY67AU4ce/aYf3MXuJCc.nJoKFmHfszoc1Klmk4i', '2025-05-21 15:43:23'),
(8, 'lamia', 65, '2025-05-07', 'B-', '01788987412', 'lknhwserfvdfdlkngdpknghfkping', 'lamia@gmail.com', 'lamia', '$2y$10$IBZQJRr3keQVNk8WV33Ro.14EfDiuKQnmJ3EwnPsqfxFf0d1GSSke', '2025-05-25 15:57:00');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `transaction_id` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `course_id` varchar(255) DEFAULT NULL,
  `amount` varchar(50) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `transaction_date` datetime NOT NULL,
  `status` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `transaction_id`, `email`, `course_name`, `course_id`, `amount`, `payment_method`, `transaction_date`, `status`, `created_at`) VALUES
(1, 'tr568965gv76', 'meain@gmail.com', 'UI UX with ZHF Design Studio', 'unknown', 'BDT 1500', 'bKash', '2025-05-01 21:15:44', 'Completed', '2025-05-01 19:15:44'),
(2, 'teyhgterhetr', 'meain@gmail.com', 'Learn UI UX with ZHF Design Studio', 'uiux101', 'BDT 200', 'Nagad', '2025-05-01 22:12:48', 'Completed', '2025-05-01 20:12:48'),
(3, 'trgv9087407b', 'Meain@gmail.com', 'Structure Expert - Making Things Look 3D', '3d201', 'BDT 500', 'Nagad', '2025-05-01 22:19:50', 'Completed', '2025-05-01 20:19:50'),
(4, 'ytrfg565645645', 'rahim.uddin@example.com', 'Digital Marketing Masterclass 2023', 'mktg202', 'BDT 3999', 'bKash', '2025-05-01 22:20:39', 'Completed', '2025-05-01 20:20:39'),
(5, 'YIL7', 'admin@gmail.com', 'Learn UI UX with ZHF Design Studio', 'uiux101', 'BDT 6500', 'Visa', '2025-05-05 08:23:52', 'Completed', '2025-05-05 06:23:52'),
(6, 'bhbh', 'admin@phpzag.com', 'Learn UI UX with ZHF Design Studio', 'uiux101', 'BDT 6500', 'Visa', '2025-05-05 08:27:03', 'Completed', '2025-05-05 06:27:03'),
(7, 'bhbh', 'admin@phpzag.com', 'Learn UI UX with ZHF Design Studio', 'uiux101', 'BDT 6500', 'Visa', '2025-05-05 08:27:11', 'Completed', '2025-05-05 06:27:11'),
(8, 'teyhgterhetr', 'tanvirk@gmail.com', 'Mobile App Development with Flutter', 'mob202', 'BDT 7500', 'bKash', '2025-05-20 21:06:41', 'Completed', '2025-05-20 19:06:41');

-- --------------------------------------------------------

--
-- Table structure for table `student_registration`
--

CREATE TABLE `student_registration` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `contact` varchar(11) NOT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `blood_group` enum('A-','A+','B-','B+','AB-','AB+','O-','O+') NOT NULL,
  `user_type` enum('Admin','Teacher','Student') NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_registration`
--

INSERT INTO `student_registration` (`id`, `first_name`, `last_name`, `contact`, `gender`, `blood_group`, `user_type`, `email`, `password`, `created_at`) VALUES
(1, 'Meain', 'Rahman', '01784521471', 'male', 'A-', 'Student', 'meain@gmail.com', '$2y$10$pxn2YblmCivzLXLg4G/ykOY.sKsPqXbSPjF0LqBwYRfkA8mDw2p1K', '2025-05-01 16:03:27'),
(2, 'Rahim', 'Uddin', '01710001122', 'male', 'A+', 'Student', 'rahim.uddin@example.com', 'pass1234', '2025-05-01 16:04:56'),
(3, 'Amina', 'Khatun', '01811002233', 'female', 'B-', 'Student', 'amina.khatun@example.com', 'secure123', '2025-05-01 16:04:56'),
(4, 'Sabbir', 'Hossain', '01999003344', 'male', 'O+', 'Student', 'sabbir.h@example.com', 'student2024', '2025-05-01 16:04:56'),
(5, 'Nusrat', 'Jahan', '01712004455', 'female', 'AB+', 'Student', 'nusrat.j@example.com', 'mypassword', '2025-05-01 16:04:56'),
(6, 'Tanvir', 'Ahmed', '01677005566', 'male', 'A-', 'Student', 'tanvir.ahmed@example.com', 'tanvir@123', '2025-05-01 16:04:56'),
(7, 'Sharmin', 'Akter', '01833006677', 'female', 'B+', 'Student', 'sharmin.akter@example.com', 'abc789', '2025-05-01 16:04:56'),
(8, 'Rafi', 'Hasan', '01755007788', 'male', 'O-', 'Student', 'rafi.hasan@example.com', 'rafi456', '2025-05-01 16:04:56'),
(9, 'Maliha', 'Sultana', '01922008899', 'female', 'AB-', 'Student', 'maliha.s@example.com', 'maliha2025', '2025-05-01 16:04:56'),
(10, 'Kamal', 'Hossain', '01733009900', 'male', 'A+', 'Student', 'kamal.hossain@example.com', 'kamal123', '2025-05-01 16:04:56'),
(11, 'Sadia', 'Rahman', '01855001001', 'female', 'B+', 'Student', 'sadia.rahman@example.com', 'sadia321', '2025-05-01 16:04:56'),
(12, 'AKMSadman', 'Rahman', '01784521471', 'male', 'B+', 'Student', 'sadman@gmail.com', '$2y$10$8PvhEPs3da.FRVrz/Zchn.0ftaxwe8G1IQxjVNaMy7h6IEBX.dDHG', '2025-05-19 14:30:19'),
(16, 'AKMSadman', 'Rahman', '01784521471', 'male', 'A-', 'Student', 'sadman2@gmail.com', '$2y$10$UTPKE9hpuBGmjAh3rVbK3ukFYl1LlTIgBkVNOVz.7V5eqZUSyebRy', '2025-05-19 14:33:10'),
(17, 'Tanvir', 'Alam', '01578412587', 'male', 'B-', 'Student', 'tanvir2@gmail.com', '$2y$10$sA5SAHeTcU/Hosw.8IfDUuP5Z7nW6IKK6HFOjt.daRKMcSHNydRmm', '2025-05-20 17:46:21'),
(18, 'maein', 'Rahman', '01111111111', 'male', 'A+', 'Student', 'meain62@gmail.com', '$2y$10$JG5htbaIX4.m01wrX2aimOYoYNxVP2061MlHWyBQIwY8B.QyNd3pi', '2025-05-25 14:12:41'),
(19, 'sadman', 'Maein', '01754393923', 'male', 'A+', 'Student', 'smeain@gmail.com', '$2y$10$ni0MOUySFMXFh46co8sqK.yMmLFgkxNS6YjE0uvNcRpe3waMJb7Em', '2025-05-25 15:08:16');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `teacher_name` varchar(100) NOT NULL,
  `age` int(11) NOT NULL,
  `date_of_birth` date NOT NULL,
  `blood_group` enum('A-','A+','B-','B+','AB-','AB+','O-','O+') NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `address` text NOT NULL,
  `email` varchar(100) NOT NULL,
  `qualifications` text NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `teacher_name`, `age`, `date_of_birth`, `blood_group`, `phone_number`, `address`, `email`, `qualifications`, `user_id`, `username`, `password`, `created_at`, `updated_at`) VALUES
(1, 'Mohammad hasan ', 30, '1994-12-14', 'B-', '0134574124', 'mirpur', 'hasan@gmail.com', 'PhD , Masters', '2200-1000', 'Hasan', '$2y$10$6i2lE4z6Q29R.iIHacDSAOdA.Mu85JQjA0yUc7ipbV3UWHncEvkSq', '2025-05-01 15:13:36', '2025-05-01 15:13:36'),
(2, 'Abdul Karim', 45, '1979-02-15', 'A+', '01712345678', 'Dhaka, Bangladesh', 'karim.abdul@example.com', 'MSc in Mathematics', '2201-1201', 'karim45', 'pass1234', '2025-05-01 15:15:03', '2025-05-01 15:15:03'),
(3, 'Fatema Begum', 38, '1986-08-10', 'B-', '01811223344', 'Chittagong, Bangladesh', 'fatema.begum@example.com', 'MA in English', '2201-1202', 'fatema38', 'abc123', '2025-05-01 15:15:03', '2025-05-01 15:15:03'),
(4, 'Mohammad Rafiq', 50, '1974-05-20', 'O+', '01999887766', 'Rajshahi, Bangladesh', 'rafiq.m@example.com', 'M.Ed', '2201-1203', 'rafiq50', 'mypassword', '2025-05-01 15:15:03', '2025-05-01 15:15:03'),
(5, 'Nasrin Akter', 29, '1995-11-25', 'AB+', '01744556677', 'Khulna, Bangladesh', 'nasrin.akter@example.com', 'BSc in Physics', '2201-1204', 'nasrin29', 'securepass', '2025-05-01 15:15:03', '2025-05-01 15:15:03'),
(7, 'Rehana Sultana', 41, '1983-07-03', 'B+', '01833445566', 'Barisal, Bangladesh', 'rehana.s@example.com', 'MSS in Sociology', '2201-1206', 'rehana41', 'social2020', '2025-05-01 15:15:03', '2025-05-01 15:15:03'),
(8, 'Tanvir Hasan', 30, '1994-01-18', 'O-', '01755667788', 'Comilla, Bangladesh', 'tanvir.hasan@example.com', 'B.Ed', '2201-1207', 'tanvir30', 'edubd456', '2025-05-01 15:15:03', '2025-05-01 15:15:03'),
(9, 'Salma Khatun', 36, '1988-09-05', 'AB-', '01922334455', 'Mymensingh, Bangladesh', 'salma.khatun@example.com', 'MSc in Botany', '2201-1208', 'salma36', 'botany88', '2025-05-01 15:15:03', '2025-05-01 15:15:03'),
(10, 'Hasan Mahmud', 42, '1982-04-22', 'A+', '01733445566', 'Rangpur, Bangladesh', 'hasan.mahmud@example.com', 'MCom in Accounting', '2201-1209', 'hasan42', 'account42', '2025-05-01 15:15:03', '2025-05-01 15:15:03'),
(11, 'Shirin Akhter', 31, '1993-12-01', 'B+', '01855667788', 'Gazipur, Bangladesh', 'shirin.akhter@example.com', 'MA in Bangla', '2201-1210', 'shirin31', 'bangla123', '2025-05-01 15:15:03', '2025-05-01 15:15:03'),
(12, 'Alamin', 29, '1996-01-01', 'A+', '0174585545', 'Gagipur', 'alamin12@gmail.com', 'PhD , Masters', '2200-1001', 'alamin0022', '$2y$10$xPqpXaEZ4h1L6noFJ26HOOD01I89mTvOU9OY1x0nrFKAmcQWGFiPm', '2025-05-25 13:53:34', '2025-05-25 13:53:34'),
(13, 'Tanvir', 28, '1996-12-29', 'A-', '0165446635', 'mirpur', 'Tanvir12@phpzag.com', 'PhD , Masters', '2200-1011', 'Tanvir12', '$2y$10$SS9mRacDppjgwMxaieoa0.qQU6PkJKYhhgXRLWu5nXsKpFp1gMzgS', '2025-05-25 14:08:29', '2025-05-25 14:08:29'),
(14, 'sasa', 21, '2012-06-04', 'B+', '0178857397', 'mirpur arambag', 'sasa@phpzag.com', 'PhD , Masters', '2200-1003', 'sasa', '$2y$10$KvgAgAQXsFs7zxCPIMI15.IwkG3tOjxz416hD5r.ar0KoG6ODfqIm', '2025-05-25 15:41:47', '2025-05-25 15:41:47');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_documents`
--

CREATE TABLE `teacher_documents` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_registration`
--
ALTER TABLE `admin_registration`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_admin_email` (`email`),
  ADD KEY `idx_admin_username` (`username`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_registration`
--
ALTER TABLE `student_registration`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_teacher_email` (`email`),
  ADD KEY `idx_teacher_user_id` (`user_id`),
  ADD KEY `idx_teacher_username` (`username`);

--
-- Indexes for table `teacher_documents`
--
ALTER TABLE `teacher_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_registration`
--
ALTER TABLE `admin_registration`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `student_registration`
--
ALTER TABLE `student_registration`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `teacher_documents`
--
ALTER TABLE `teacher_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `teacher_documents`
--
ALTER TABLE `teacher_documents`
  ADD CONSTRAINT `teacher_documents_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
