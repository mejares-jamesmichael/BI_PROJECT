-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 22, 2025 at 07:03 PM
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
-- Database: `destination_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `customer_analytics`
--

CREATE TABLE `customer_analytics` (
  `id` int(11) NOT NULL,
  `customer_number` int(11) DEFAULT NULL,
  `customer_name` varchar(50) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `total_orders` int(11) DEFAULT NULL,
  `total_spent` decimal(15,2) DEFAULT NULL,
  `avg_order_value` decimal(10,2) DEFAULT NULL,
  `first_order_date` date DEFAULT NULL,
  `last_order_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_performance`
--

CREATE TABLE `employee_performance` (
  `id` int(11) NOT NULL,
  `employee_number` int(11) DEFAULT NULL,
  `employee_name` varchar(100) DEFAULT NULL,
  `job_title` varchar(50) DEFAULT NULL,
  `office_city` varchar(50) DEFAULT NULL,
  `customers_managed` int(11) DEFAULT NULL,
  `total_sales` decimal(15,2) DEFAULT NULL,
  `avg_sales_per_customer` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `geographic_analytics`
--

CREATE TABLE `geographic_analytics` (
  `id` int(11) NOT NULL,
  `country` varchar(50) DEFAULT NULL,
  `customer_count` int(11) DEFAULT NULL,
  `total_revenue` decimal(15,2) DEFAULT NULL,
  `avg_revenue_per_customer` decimal(10,2) DEFAULT NULL,
  `order_count` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_performance`
--

CREATE TABLE `product_performance` (
  `id` int(11) NOT NULL,
  `product_code` varchar(15) DEFAULT NULL,
  `product_name` varchar(70) DEFAULT NULL,
  `product_line` varchar(50) DEFAULT NULL,
  `total_quantity_sold` int(11) DEFAULT NULL,
  `total_revenue` decimal(15,2) DEFAULT NULL,
  `avg_price` decimal(10,2) DEFAULT NULL,
  `order_count` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales_analytics`
--

CREATE TABLE `sales_analytics` (
  `id` int(11) NOT NULL,
  `year` int(11) DEFAULT NULL,
  `month` int(11) DEFAULT NULL,
  `total_sales` decimal(15,2) DEFAULT NULL,
  `order_count` int(11) DEFAULT NULL,
  `customer_count` int(11) DEFAULT NULL,
  `avg_order_value` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customer_analytics`
--
ALTER TABLE `customer_analytics`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employee_performance`
--
ALTER TABLE `employee_performance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `geographic_analytics`
--
ALTER TABLE `geographic_analytics`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_performance`
--
ALTER TABLE `product_performance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales_analytics`
--
ALTER TABLE `sales_analytics`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customer_analytics`
--
ALTER TABLE `customer_analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1840;

--
-- AUTO_INCREMENT for table `employee_performance`
--
ALTER TABLE `employee_performance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=421;

--
-- AUTO_INCREMENT for table `geographic_analytics`
--
ALTER TABLE `geographic_analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=442;

--
-- AUTO_INCREMENT for table `product_performance`
--
ALTER TABLE `product_performance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2038;

--
-- AUTO_INCREMENT for table `sales_analytics`
--
ALTER TABLE `sales_analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=598;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
