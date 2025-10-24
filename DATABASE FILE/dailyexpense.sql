-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Oct 24, 2025 at 09:04 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dailyexpense`
--

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

CREATE TABLE `budgets` (
  `budget_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `budget_limit` decimal(10,2) NOT NULL,
  `budget_month` int(2) NOT NULL,
  `budget_year` int(4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budgets`
--

INSERT INTO `budgets` (`budget_id`, `user_id`, `category`, `budget_limit`, `budget_month`, `budget_year`, `created_at`) VALUES
(1, 3, 'Bills', 100.00, 10, 2025, '2025-10-23 12:46:40'),
(4, 3, 'Food', 2000.00, 10, 2025, '2025-10-23 12:48:58'),
(9, 5, 'Bills', 2000.00, 10, 2025, '2025-10-24 06:17:37'),
(10, 5, 'Food', 2500.00, 10, 2025, '2025-10-24 06:17:46'),
(11, 5, 'Travel', 3000.00, 10, 2025, '2025-10-24 06:17:57');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `expense_id` int(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `expense` decimal(10,2) NOT NULL,
  `expensedate` date NOT NULL,
  `expensenote` text DEFAULT NULL,
  `expensecategory` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`expense_id`, `user_id`, `expense`, `expensedate`, `expensenote`, `expensecategory`) VALUES
(1, 1, 2000.00, '2025-10-12', NULL, 'Food'),
(2, 1, 300.00, '2025-10-13', '', 'Food & Dining'),
(3, 1, 4000.00, '2025-10-13', '', 'Food & Dining'),
(4, 3, 270.00, '2025-10-08', '', 'Food & Dining'),
(5, 3, 400.00, '2025-10-13', 'Bus', 'Transportation'),
(6, 3, 270.00, '2025-10-11', 'electricity', 'Bills & Utilities'),
(7, 3, 50.00, '2025-10-08', 'movie', 'Entertainment'),
(8, 3, 1000.00, '2025-09-11', '', 'Food & Dining'),
(9, 3, 2000.00, '2025-09-19', '', 'Travel'),
(10, 3, 2000.00, '2025-10-23', '', 'Food'),
(11, 5, 200.00, '2025-10-07', '', 'Food'),
(12, 5, 300.00, '2025-10-07', '', 'Travel');

-- --------------------------------------------------------

--
-- Table structure for table `expense_categories`
--

CREATE TABLE `expense_categories` (
  `category_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `category_status` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expense_categories`
--

INSERT INTO `expense_categories` (`category_id`, `user_id`, `category_name`, `category_status`) VALUES
(1, 3, 'Food', 'active'),
(2, 3, 'Travel', 'active'),
(3, 3, 'Bills', 'active'),
(4, 3, 'other', 'active'),
(8, 5, 'Bills', 'active'),
(9, 5, 'Food', 'active'),
(10, 5, 'Travel', 'active'),
(11, 5, 'Other', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `income`
--

CREATE TABLE `income` (
  `income_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `income_source` varchar(100) NOT NULL,
  `income_amount` decimal(10,2) NOT NULL,
  `income_date` date NOT NULL,
  `income_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `income`
--

INSERT INTO `income` (`income_id`, `user_id`, `income_source`, `income_amount`, `income_date`, `income_description`, `created_at`) VALUES
(3, 1, 'Salary', 500.00, '2025-10-13', '', '2025-10-12 22:01:17'),
(4, 1, 'Salary', 1000.00, '2025-10-13', '', '2025-10-12 22:01:36'),
(5, 3, 'Salary', 5000.00, '2025-10-13', 'salary credited', '2025-10-13 06:56:09'),
(6, 3, 'Gift', 2000.00, '2025-10-13', '', '2025-10-13 06:57:00'),
(7, 3, 'salary', 10000.00, '2025-09-02', '', '2025-10-23 11:18:38'),
(8, 5, 'salary', 10000.00, '2025-10-01', '', '2025-10-24 06:16:49');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(25) NOT NULL,
  `email` varchar(50) NOT NULL,
  `profile_path` varchar(50) NOT NULL DEFAULT 'default_profile.png',
  `password` varchar(255) NOT NULL,
  `trn_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `firstname`, `lastname`, `email`, `profile_path`, `password`, `trn_date`) VALUES
(1, 'abc', 'xyz', 'abc@gmail.com', '1_1760307248.jpg', '900150983cd24fb0d6963f7d28e17f72', '2025-10-12 22:43:44'),
(2, 'xyz', 'xyz', 'xyz@gmail.com', 'default_profile.png', '$2y$10$6/LczuVXHBd1kh6Cga7Va.JMFyTf88MC6BMq5.wOy14i1FRnXsM36', '2025-10-13 00:18:48'),
(3, 'Krina', 'Suthar', 'krina12@gmail.com', 'default_profile.png', '$2y$10$0Ugdgnk3dkTdovlDNEWlFOpNPLOejmPtVGOTYghtQwvN.0fCAqLxW', '2025-10-13 08:54:19'),
(4, 'abc', 'xyz', 'a@gmail.com', 'default_profile.png', '$2y$10$7FGgn0IVxEfgbET0pAgu6Od49Uo.f7h7Rq3G.U6rs1to8vzX/vOwq', '2025-10-23 12:25:18'),
(5, 'Abc', 'Xyz', 'ab@gmail.com', 'default_profile.png', '$2y$10$DVCjZ/3l7a46ixaxC5G5BuqUh1E4WYAHHT4veMT/09XLxhkDgbfZq', '2025-10-24 08:16:10');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`budget_id`),
  ADD UNIQUE KEY `user_category_month_year` (`user_id`,`category`,`budget_month`,`budget_year`),
  ADD UNIQUE KEY `unique_budget` (`user_id`,`category`,`budget_month`,`budget_year`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`expense_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `expense_categories`
--
ALTER TABLE `expense_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `user_category` (`user_id`,`category_name`);

--
-- Indexes for table `income`
--
ALTER TABLE `income`
  ADD PRIMARY KEY (`income_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `budgets`
--
ALTER TABLE `budgets`
  MODIFY `budget_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `expense_id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `expense_categories`
--
ALTER TABLE `expense_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `income`
--
ALTER TABLE `income`
  MODIFY `income_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `expense_categories`
--
ALTER TABLE `expense_categories`
  ADD CONSTRAINT `expense_categories_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
