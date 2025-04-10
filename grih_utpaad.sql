CREATE DATABASE grih_utpaad;
USE grih_utpaad;

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_superadmin` tinyint(1) NOT NULL DEFAULT 0,
  `permissions` text DEFAULT NULL COMMENT 'JSON array of permissions',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `user_id`, `is_superadmin`, `permissions`, `last_login`, `created_at`) VALUES
(1, 4, 1, '[\"all\"]', '2025-04-10 04:12:16', '2025-04-10 02:03:09');

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `table_affected` varchar(50) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `performed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `admin_id`, `action`, `table_affected`, `record_id`, `old_values`, `new_values`, `performed_at`) VALUES
(1, 1, 'LOGIN', 'users', NULL, NULL, '{\"login_time\":\"2025-04-10 04:13:07\"}', '2025-04-10 02:13:07'),
(2, 1, 'BLOCK_USER', 'users', 2, NULL, '{\"is_blocked\":1}', '2025-04-10 02:31:17'),
(3, 1, 'BLOCK_USER', 'users', 1, NULL, '{\"is_blocked\":1}', '2025-04-10 02:31:18'),
(4, 1, 'BLOCK_USER', 'users', 3, NULL, '{\"is_blocked\":1}', '2025-04-10 02:31:20'),
(5, 1, 'APPROVE', 'products', 2, NULL, '{\"approved\":true,\"approved_at\":\"2025-04-10 04:31:51\"}', '2025-04-10 02:31:51'),
(6, 1, 'LOGIN', 'users', NULL, NULL, '{\"login_time\":\"2025-04-10 04:37:45\"}', '2025-04-10 02:37:45'),
(7, 1, 'ADD_CATEGORY', 'product_categories', 1, NULL, '{\"name\":\"Snacks\",\"description\":\"Any item can be called snack which is easy to cook, ready to eat.\"}', '2025-04-10 02:39:01'),
(8, 1, 'LOGIN', 'users', NULL, NULL, '{\"login_time\":\"2025-04-10 05:08:29\"}', '2025-04-10 03:08:29'),
(9, 1, 'BLOCK_USER', 'users', 6, NULL, '{\"is_blocked\":1}', '2025-04-10 03:08:42'),
(10, 1, 'UNBLOCK_USER', 'users', 6, NULL, '{\"is_blocked\":0}', '2025-04-10 03:08:45'),
(11, 1, 'UNBLOCK_USER', 'users', 3, NULL, '{\"is_blocked\":0}', '2025-04-10 03:08:47'),
(12, 1, 'UNBLOCK_USER', 'users', 2, NULL, '{\"is_blocked\":0}', '2025-04-10 03:08:48'),
(13, 1, 'UNBLOCK_USER', 'users', 1, NULL, '{\"is_blocked\":0}', '2025-04-10 03:08:49'),
(14, 1, 'ADD_CATEGORY', 'product_categories', 2, NULL, '{\"name\":\"beverages\",\"description\":\"taral padarth\"}', '2025-04-10 03:09:33'),
(15, 1, 'EDIT_CATEGORY', 'product_categories', 2, NULL, '{\"name\":\"beverages\",\"description\":\"taral padartham\"}', '2025-04-10 03:09:42'),
(16, 1, 'APPROVE', 'products', 5, NULL, '{\"approved\":true,\"approved_at\":\"2025-04-10 05:10:01\"}', '2025-04-10 03:10:01'),
(17, 1, 'APPROVE', 'products', 3, NULL, '{\"approved\":true,\"approved_at\":\"2025-04-10 05:10:03\"}', '2025-04-10 03:10:03'),
(20, 1, 'LOGIN', 'users', NULL, NULL, '{\"login_time\":\"2025-04-10 05:25:00\"}', '2025-04-10 03:25:00'),
(21, 1, 'BLOCK_USER', 'users', 6, NULL, '{\"is_blocked\":1}', '2025-04-10 03:25:12'),
(22, 1, 'UNBLOCK_USER', 'users', 6, NULL, '{\"is_blocked\":0}', '2025-04-10 03:25:13'),
(23, 1, 'DELETE_CATEGORY', 'product_categories', 2, NULL, NULL, '2025-04-10 03:25:20'),
(24, 1, 'LOGIN', 'users', NULL, NULL, '{\"login_time\":\"2025-04-10 06:12:16\"}', '2025-04-10 04:12:16'),
(25, 1, 'BLOCK_USER', 'users', 8, NULL, '{\"is_blocked\":1}', '2025-04-10 04:13:34'),
(26, 1, 'BLOCK_USER', 'users', 7, NULL, '{\"is_blocked\":1}', '2025-04-10 04:13:35'),
(27, 1, 'BLOCK_USER', 'users', 6, NULL, '{\"is_blocked\":1}', '2025-04-10 04:13:35'),
(28, 1, 'UNBLOCK_USER', 'users', 8, NULL, '{\"is_blocked\":0}', '2025-04-10 04:13:41'),
(29, 1, 'UNBLOCK_USER', 'users', 7, NULL, '{\"is_blocked\":0}', '2025-04-10 04:13:42'),
(30, 1, 'UNBLOCK_USER', 'users', 6, NULL, '{\"is_blocked\":0}', '2025-04-10 04:13:43'),
(31, 1, 'ADD_CATEGORY', 'product_categories', 3, NULL, '{\"name\":\"Pickle\",\"description\":\"Khatta meetha\"}', '2025-04-10 04:14:22'),
(32, 1, 'DELETE_CATEGORY', 'product_categories', 3, NULL, NULL, '2025-04-10 04:14:46'),
(33, 1, 'ADD_CATEGORY', 'product_categories', 4, NULL, '{\"name\":\"Pickle\",\"description\":\"\"}', '2025-04-10 04:14:58'),
(34, 1, 'ADD_CATEGORY', 'product_categories', 5, NULL, '{\"name\":\"Pickle\",\"description\":\"\"}', '2025-04-10 04:15:08'),
(35, 1, 'APPROVE', 'products', 6, NULL, '{\"approved\":true,\"approved_at\":\"2025-04-10 06:15:44\"}', '2025-04-10 04:15:44'),
(36, 1, 'DELETE_CATEGORY', 'product_categories', 5, NULL, NULL, '2025-04-10 04:16:17');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `consumer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `status` enum('pending','fulfilled','cancelled') DEFAULT 'pending',
  `ordered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `fulfilled_at` timestamp NULL DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `consumer_id`, `product_id`, `status`, `ordered_at`, `fulfilled_at`, `quantity`, `total_price`, `updated_by`, `updated_at`) VALUES
(1, 2, 2, 'fulfilled', '2025-04-10 01:43:13', '2025-04-10 03:16:58', 1, 10000.00, NULL, NULL),
(2, 5, 2, 'fulfilled', '2025-04-10 02:47:02', '2025-04-10 03:19:06', 2, 20000.00, 4, '2025-04-10 03:19:06'),
(3, 5, 2, 'fulfilled', '2025-04-10 02:47:43', '2025-04-10 03:19:18', 1, 10000.00, 4, '2025-04-10 03:19:18'),
(4, 8, 3, 'cancelled', '2025-04-10 03:41:56', NULL, 1, 10000.00, 4, '2025-04-10 04:18:02'),
(5, 8, 6, 'pending', '2025-04-10 04:19:38', NULL, 2, 220.00, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `approved` tinyint(1) NOT NULL DEFAULT 0,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `user_id`, `title`, `description`, `category_id`, `price`, `is_approved`, `image`, `approved`, `approved_by`, `approved_at`, `created_at`) VALUES
(2, 3, 'product', 'a product is a product of the product, product is very beautiful', 0, 10000.00, 0, '1744249083_hanuman ji.jpg', 1, 1, '2025-04-10 02:31:51', '2025-04-10 01:38:03'),
(3, 2, 'product', 'description', 0, 10000.00, 0, '1744249511_SMS23.jpg', 1, 1, '2025-04-10 03:10:03', '2025-04-10 01:45:11'),
(5, 6, 'chipssss', 'lays', 1, 5.00, 0, '1744253404_ritesh singhbba1.jpg', 1, 1, '2025-04-10 03:10:01', '2025-04-10 02:50:05'),
(6, 7, 'namkeen', 'jai sita ram', 1, 110.00, 0, '1744255866_Namkeen-collection-pouch-png-Pngsource-EJ19GLH8.png', 1, 1, '2025-04-10 04:15:44', '2025-04-10 03:31:06');

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`id`, `name`, `description`, `created_by`, `created_at`) VALUES
(1, 'Snacks', 'Any item can be called snack which is easy to cook, ready to eat.', 1, '2025-04-10 02:39:01'),
(4, 'Pickle', '', 1, '2025-04-10 04:14:58');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `consumer_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `review_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('webmaster','female_householder','consumer','admin') NOT NULL,
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_blocked` tinyint(1) NOT NULL DEFAULT 0,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `about` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `registered_at`, `is_blocked`, `registration_date`, `phone`, `address`, `about`) VALUES
(1, 'Arya', 'kaustubhshukla66@gmail.com', '$2y$10$dyCkYmR376EJnjFdhEu0buzxAfg43IqGPWpOKpPKg8cqkVkgVmuJS', 'female_householder', '2025-04-10 00:52:53', 0, '2025-04-10 03:20:17', NULL, NULL, NULL),
(2, 'jav', 'jav@jav.jav', '$2y$10$9w6rEZaccMDxzTCqF3nRs.u4DQ8Xr4eOjAmQk8iS/2/NrfaG6xj0e', 'consumer', '2025-04-10 01:13:50', 0, '2025-04-10 03:20:17', NULL, NULL, NULL),
(3, 'jav', 'mca2322049@smsvaranasi.in', '$2y$10$ln3JRAOLZhXsYD72SNdb/ul.0QnvT2JmkjA1leTim21jT7zA1OSbm', 'female_householder', '2025-04-10 01:21:39', 0, '2025-04-10 03:20:17', NULL, NULL, NULL),
(4, 'Super Admin', 'admin@grihutpaad.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2025-04-10 02:03:09', 0, '2025-04-10 03:20:17', NULL, NULL, NULL),
(5, 'akash', 'a@gmail.com', '$2y$10$cax84LtUCY3bTBK5umTe1OnRkWXHk3tPz71IWwPrC.51UU7E3/Xk2', 'consumer', '2025-04-10 02:46:23', 0, '2025-04-10 03:20:17', NULL, NULL, NULL),
(6, 'arya', 'a@g.com', '$2y$10$MRze.V2g2Fk4cIz2yOEsp.1wq2oLKwIRHzfOvL3bDVuwEouk8XkVO', 'female_householder', '2025-04-10 02:48:57', 0, '2025-04-10 03:20:17', NULL, NULL, NULL),
(7, 'Arya', 'arya123@gmail.com', '$2y$10$0/TFYVqQsWFnQijsRfDi6O/Hpv8iXvKxiiAgMBNr8/PaobYPag6.S', 'female_householder', '2025-04-10 03:26:45', 0, '2025-04-10 03:26:45', '96964833617', 'khushipur, Varanasi', 'AMbitious, Hardworking, Practical'),
(8, 'akash', 'ab@gmail.com', '$2y$10$uDzG/Y0Pxw.mGJaXuTK3ceRE4Qltf0pXEPeSIVrGemZA8PnCI2fPq', 'consumer', '2025-04-10 03:37:28', 0, '2025-04-10 03:37:28', NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `consumer_id` (`consumer_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `products_ibfk_2` (`approved_by`),
  ADD KEY `category` (`category_id`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `consumer_id` (`consumer_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`consumer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `admins` (`id`),
  ADD CONSTRAINT `products_ibfk_3` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`);

--
-- Constraints for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD CONSTRAINT `product_categories_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`consumer_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
