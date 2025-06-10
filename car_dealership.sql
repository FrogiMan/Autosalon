-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3307
-- Время создания: Июн 10 2025 г., 21:05
-- Версия сервера: 5.5.62
-- Версия PHP: 7.4.5

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `car_dealership`
--

-- --------------------------------------------------------

--
-- Структура таблицы `brands`
--

CREATE TABLE `brands` (
  `id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `brands`
--

INSERT INTO `brands` (`id`, `name`, `logo`) VALUES
(1, 'Toyota', 'toyota_logo.png'),
(2, 'BMW', 'bmw_logo.png'),
(3, 'Ford', 'ford_logo.png'),
(4, 'Hyundai', 'hyundai_logo.png'),
(5, 'Volkswagen', 'vw_logo.png');

-- --------------------------------------------------------

--
-- Структура таблицы `cars`
--

CREATE TABLE `cars` (
  `id` int(11) NOT NULL,
  `brand_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `model` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `year` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_available` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `cars`
--

INSERT INTO `cars` (`id`, `brand_id`, `category_id`, `model`, `year`, `price`, `description`, `image`, `created_at`, `is_available`) VALUES
(1, 2, 2, 'вавпавап', 2022, '99999999.99', '0', 'img_68476c40785d4.jpg', '2025-06-09 16:25:26', 0),
(2, 1, 2, 'RAV4', 2022, '3500000.00', 'Надежный внедорожник для любых дорог.', 'toyota_rav4_2022.jpg', '2025-06-09 16:25:26', 1),
(3, 1, 3, 'Corolla', 2021, '2200000.00', 'Экономичный хэтчбек с современным дизайном.', 'toyota_corolla_2021.jpg', '2025-06-09 16:25:26', 1),
(4, 1, 4, 'Supra', 2024, '5500000.00', 'Спортивное купе с высокой производительностью.', 'toyota_supra_2024.jpg', '2025-06-09 16:25:26', 1),
(5, 1, 5, 'Sienna', 2023, '4200000.00', 'Просторный минивэн для всей семьи.', 'toyota_sienna_2023.jpg', '2025-06-09 16:25:26', 1),
(6, 2, 1, '3 Series', 2022, '4500000.00', 'Седан премиум-класса с отличной динамикой.', 'bmw_3series_2022.jpg', '2025-06-09 16:25:26', 1),
(7, 2, 2, 'X5', 2023, '6500000.00', 'Мощный внедорожник с премиальным интерьером.', 'bmw_x5_2023.jpg', '2025-06-09 16:25:26', 1),
(8, 2, 3, '1 Series', 2021, '3000000.00', 'Компактный хэтчбек для города.', 'bmw_1series_2021.jpg', '2025-06-09 16:25:26', 1),
(9, 2, 4, '4 Series', 2024, '5200000.00', 'Стильное купе с агрессивным дизайном.', 'bmw_4series_2024.jpg', '2025-06-09 16:25:26', 1),
(10, 2, 5, '2 Series Gran Tourer', 2022, '3800000.00', 'Минивэн для активных семей.', 'bmw_2series_gt_2022.jpg', '2025-06-09 16:25:26', 1),
(11, 3, 1, 'Focus', 2021, '2000000.00', 'Седан с отличной управляемостью.', 'ford_focus_2021.jpg', '2025-06-09 16:25:26', 1),
(12, 3, 2, 'Explorer', 2023, '4800000.00', 'Внедорожник для приключений.', 'ford_explorer_2023.jpg', '2025-06-09 16:25:26', 1),
(13, 3, 3, 'Fiesta', 2020, '1800000.00', 'Компактный хэтчбек для молодежи.', 'ford_fiesta_2020.jpg', '2025-06-09 16:25:26', 1),
(14, 3, 4, 'Mustang', 2024, '6000000.00', 'Легендарное спортивное купе.', 'ford_mustang_2024.jpg', '2025-06-09 16:25:26', 1),
(15, 3, 5, 'Tourneo', 2022, '3900000.00', 'Просторный минивэн для поездок.', 'ford_tourneo_2022.jpg', '2025-06-09 16:25:26', 1),
(16, 4, 1, 'Sonata', 2023, '2700000.00', 'Седан с современными технологиями.', 'hyundai_sonata_2023.jpg', '2025-06-09 16:25:26', 1),
(17, 4, 2, 'Tucson', 2022, '3400000.00', 'Стильный внедорожник для города и природы.', 'hyundai_tucson_2022.jpg', '2025-06-09 16:25:26', 1),
(18, 4, 3, 'i30', 2021, '2100000.00', 'Хэтчбек с экономичным двигателем.', 'hyundai_i30_2021.jpg', '2025-06-09 16:25:26', 1),
(19, 4, 4, 'Veloster', 2020, '2900000.00', 'Спортивное купе с уникальным дизайном.', 'hyundai_veloster_2020.jpg', '2025-06-09 16:25:26', 1),
(20, 4, 5, 'Staria', 2023, '4100000.00', 'Минивэн с футуристичным дизайном.', 'hyundai_staria_2023.jpg', '2025-06-09 16:25:26', 1),
(21, 5, 1, 'Passat', 2022, '3100000.00', 'Классический седан для деловых поездок.', 'vw_passat_2022.jpg', '2025-06-09 16:25:26', 1),
(22, 5, 2, 'Tiguan', 2023, '3700000.00', 'Универсальный внедорожник.', 'vw_tiguan_2023.jpg', '2025-06-09 16:25:26', 1),
(23, 5, 3, 'Golf', 2021, '2300000.00', 'Легендарный хэтчбек.', 'vw_golf_2021.jpg', '2025-06-09 16:25:26', 1),
(24, 5, 4, 'Scirocco', 2020, '3200000.00', 'Спортивное купе с динамичным характером.', 'vw_scirocco_2020.jpg', '2025-06-09 16:25:26', 1),
(25, 5, 5, 'Multivan', 2023, '4500000.00', 'Просторный минивэн для путешествий.', 'vw_multivan_2023.jpg', '2025-06-09 16:25:26', 1),
(26, 1, 1, 'Avalon', 2022, '3200000.00', 'Премиальный седан для комфорта.', 'toyota_avalon_2022.jpg', '2025-06-09 16:25:26', 1),
(27, 1, 2, 'Highlander', 2023, '4800000.00', 'Мощный внедорожник для семьи.', 'toyota_highlander_2023.jpg', '2025-06-09 16:25:26', 1),
(28, 1, 3, 'Yaris', 2021, '1900000.00', 'Компактный хэтчбек для города.', 'toyota_yaris_2021.jpg', '2025-06-09 16:25:26', 1),
(29, 1, 4, 'GR86', 2024, '5100000.00', 'Спортивное купе для энтузиастов.', 'toyota_gr86_2024.jpg', '2025-06-09 16:25:26', 0),
(30, 2, 1, '5 Series', 2023, '5500000.00', 'Седан премиум-класса.', 'bmw_5series_2023.jpg', '2025-06-09 16:25:26', 1),
(31, 2, 2, 'X3', 2022, '4700000.00', 'Компактный внедорожник.', 'bmw_x3_2022.jpg', '2025-06-09 16:25:26', 1),
(32, 2, 3, '2 Series', 2021, '3100000.00', 'Хэтчбек премиум-класса.', 'bmw_2series_2021.jpg', '2025-06-09 16:25:26', 1),
(33, 2, 4, 'Z4', 2024, '5800000.00', 'Спортивное купе-кабриолет.', 'bmw_z4_2024.jpg', '2025-06-09 16:25:26', 1),
(34, 3, 1, 'Mondeo', 2022, '2800000.00', 'Седан для деловых поездок.', 'ford_mondeo_2022.jpg', '2025-06-09 16:25:26', 1),
(35, 3, 2, 'Bronco', 2023, '5200000.00', 'Внедорожник для бездорожья.', 'ford_bronco_2023.jpg', '2025-06-09 16:25:26', 1),
(36, 3, 3, 'Puma', 2021, '2100000.00', 'Стильный хэтчбек-кроссовер.', 'ford_puma_2021.jpg', '2025-06-09 16:25:26', 1),
(37, 3, 4, 'Mustang Mach 1', 2024, '6500000.00', 'Спортивное купе с высокой мощностью.', 'ford_mustang_mach1_2024.jpg', '2025-06-09 16:25:26', 1),
(38, 4, 1, 'Elantra', 2023, '2500000.00', 'Седан с современным дизайном.', 'hyundai_elantra_2023.jpg', '2025-06-09 16:25:26', 1),
(39, 4, 2, 'Santa Fe', 2022, '3900000.00', 'Просторный внедорожник.', 'hyundai_santafe_2022.jpg', '2025-06-09 16:25:26', 1),
(40, 4, 3, 'i20', 2021, '2000000.00', 'Компактный хэтчбек.', 'hyundai_i20_2021.jpg', '2025-06-09 16:25:26', 1),
(41, 4, 4, 'Genesis Coupe', 2020, '3100000.00', 'Спортивное купе.', 'hyundai_genesis_coupe_2020.jpg', '2025-06-09 16:25:26', 1),
(42, 5, 1, 'Jetta', 2022, '2600000.00', 'Седан для ежедневных поездок.', 'vw_jetta_2022.jpg', '2025-06-09 16:25:26', 1),
(43, 5, 2, 'Touareg', 2023, '5500000.00', 'Премиальный внедорожник.', 'vw_touareg_2023.jpg', '2025-06-09 16:25:26', 1),
(44, 5, 3, 'Polo', 2021, '1900000.00', 'Компактный хэтчбек.', 'vw_polo_2021.jpg', '2025-06-09 16:25:26', 1),
(45, 5, 4, 'Arteon', 2024, '4200000.00', 'Стильное купе-седан.', 'vw_arteon_2024.jpg', '2025-06-09 16:25:26', 1),
(46, 1, 1, 'Prius', 2023, '2900000.00', 'Гибридный седан.', 'toyota_prius_2023.jpg', '2025-06-09 16:25:26', 1),
(47, 2, 2, 'X7', 2023, '7500000.00', 'Роскошный внедорожник.', 'bmw_x7_2023.jpg', '2025-06-09 16:25:26', 1),
(48, 3, 2, 'Edge', 2022, '4000000.00', 'Внедорожник для города.', 'ford_edge_2022.jpg', '2025-06-09 16:25:26', 1),
(49, 4, 2, 'Palisade', 2023, '4500000.00', 'Просторный внедорожник.', 'hyundai_palisade_2023.jpg', '2025-06-09 16:25:26', 1),
(50, 5, 3, 'T-Roc', 2021, '2400000.00', 'Стильный хэтчбек-кроссовер.', 'vw_troc_2021.jpg', '2025-06-09 16:25:26', 1),
(51, 3, 2, 'врврапр', 2022, '34343.00', '0', 'img_68477aac1cd5c.jpg', '2025-06-09 23:45:08', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `car_specifications`
--

CREATE TABLE `car_specifications` (
  `id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `engine` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `horsepower` int(11) DEFAULT NULL,
  `transmission` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fuel_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `car_specifications`
--

INSERT INTO `car_specifications` (`id`, `car_id`, `engine`, `horsepower`, `transmission`, `fuel_type`) VALUES
(1, 1, '2.5L 4-cylinder', 203, 'Automatic', 'Gasoline'),
(2, 2, '2.0L 4-cylinder', 190, 'Automatic', 'Gasoline'),
(3, 3, '1.8L 4-cylinder', 140, 'Manual', 'Gasoline'),
(4, 4, '3.0L 6-cylinder', 382, 'Automatic', 'Gasoline'),
(5, 5, '3.5L V6', 295, 'Automatic', 'Hybrid'),
(6, 6, '2.0L 4-cylinder', 255, 'Automatic', 'Gasoline'),
(7, 7, '3.0L 6-cylinder', 335, 'Automatic', 'Diesel'),
(8, 8, '1.5L 4-cylinder', 181, 'Automatic', 'Gasoline'),
(9, 9, '2.0L 4-cylinder', 299, 'Automatic', 'Gasoline'),
(10, 10, '2.0L 4-cylinder', 190, 'Automatic', 'Gasoline'),
(11, 11, '1.5L 4-cylinder', 182, 'Manual', 'Gasoline'),
(12, 12, '2.7L V6', 310, 'Automatic', 'Gasoline'),
(13, 13, '1.0L 3-cylinder', 125, 'Manual', 'Gasoline'),
(14, 14, '5.0L V8', 460, 'Manual', 'Gasoline'),
(15, 15, '2.5L 4-cylinder', 200, 'Automatic', 'Gasoline'),
(16, 16, '2.0L 4-cylinder', 200, 'Automatic', 'Gasoline'),
(17, 17, '2.0L 4-cylinder', 192, 'Automatic', 'Gasoline'),
(18, 18, '1.6L 4-cylinder', 180, 'Manual', 'Gasoline'),
(19, 19, '2.0L 4-cylinder', 201, 'Automatic', 'Gasoline'),
(20, 20, '2.5L 4-cylinder', 204, 'Automatic', 'Gasoline'),
(21, 21, '2.0L 4-cylinder', 200, 'Automatic', 'Gasoline'),
(22, 22, '2.0L 4-cylinder', 190, 'Automatic', 'Gasoline'),
(23, 23, '1.5L 4-cylinder', 150, 'Manual', 'Gasoline'),
(24, 24, '2.0L 4-cylinder', 200, 'Automatic', 'Gasoline'),
(25, 25, '2.5L 4-cylinder', 204, 'Automatic', 'Gasoline'),
(26, 26, '3.5L V6', 295, 'Automatic', 'Gasoline'),
(27, 27, '3.5L V6', 290, 'Automatic', 'Gasoline'),
(28, 28, '1.5L 4-cylinder', 130, 'Manual', 'Gasoline'),
(29, 29, '2.0L 4-cylinder', 280, 'Automatic', 'Gasoline'),
(30, 30, '3.0L 6-cylinder', 382, 'Automatic', 'Gasoline'),
(31, 31, '2.0L 4-cylinder', 255, 'Automatic', 'Gasoline'),
(32, 32, '2.0L 4-cylinder', 190, 'Automatic', 'Gasoline'),
(33, 33, '2.0L 4-cylinder', 255, 'Automatic', 'Gasoline'),
(34, 34, '2.0L 4-cylinder', 192, 'Automatic', 'Gasoline'),
(35, 35, '2.3L 4-cylinder', 300, 'Automatic', 'Gasoline'),
(36, 36, '1.5L 4-cylinder', 181, 'Automatic', 'Gasoline'),
(37, 37, '5.0L V8', 460, 'Automatic', 'Gasoline'),
(38, 38, '1.6L 4-cylinder', 180, 'Automatic', 'Gasoline'),
(39, 39, '2.5L 4-cylinder', 192, 'Automatic', 'Gasoline'),
(40, 40, '1.5L 4-cylinder', 130, 'Manual', 'Gasoline'),
(41, 41, '2.0L 4-cylinder', 250, 'Automatic', 'Gasoline'),
(42, 42, '1.5L 4-cylinder', 150, 'Automatic', 'Gasoline'),
(43, 43, '3.0L 6-cylinder', 300, 'Automatic', 'Diesel'),
(44, 44, '1.5L 4-cylinder', 130, 'Manual', 'Gasoline'),
(45, 45, '2.0L 4-cylinder', 280, 'Automatic', 'Gasoline'),
(46, 46, '1.8L 4-cylinder', 141, 'Automatic', 'Hybrid'),
(47, 47, '3.0L 6-cylinder', 400, 'Automatic', 'Gasoline'),
(48, 48, '2.0L 4-cylinder', 250, 'Automatic', 'Gasoline'),
(49, 49, '2.5L 4-cylinder', 204, 'Automatic', 'Gasoline'),
(50, 50, '1.5L 4-cylinder', 150, 'Automatic', 'Gasoline'),
(51, 1, '464656', 4546, '445', '0'),
(52, 51, 'ввпа', 46, '445', '0'),
(53, 51, 'ввпа', 46, '445', '0'),
(54, 51, 'ввпа', 46, '445', '0'),
(55, 51, 'ввпа', 46, '445', '0');

-- --------------------------------------------------------

--
-- Структура таблицы `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Седан'),
(2, 'Внедорожник'),
(3, 'Хэтчбек'),
(4, 'Купе'),
(5, 'Минивэн');

-- --------------------------------------------------------

--
-- Структура таблицы `comparisons`
--

CREATE TABLE `comparisons` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `comparisons`
--

INSERT INTO `comparisons` (`id`, `user_id`, `car_id`, `created_at`) VALUES
(1, 1, 33, '2025-06-09 18:39:46'),
(2, 1, 29, '2025-06-09 18:49:51'),
(3, 1, 14, '2025-06-09 18:49:55');

-- --------------------------------------------------------

--
-- Структура таблицы `purchases`
--

CREATE TABLE `purchases` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','declined') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `purchases`
--

INSERT INTO `purchases` (`id`, `user_id`, `car_id`, `amount`, `status`, `created_at`) VALUES
(1, 1, 29, '5100000.00', 'pending', '2025-06-09 18:54:29');

-- --------------------------------------------------------

--
-- Структура таблицы `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `car_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `test_drive_requests`
--

CREATE TABLE `test_drive_requests` (
  `id` int(11) NOT NULL,
  `car_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `request_date` datetime DEFAULT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `test_drive_requests`
--

INSERT INTO `test_drive_requests` (`id`, `car_id`, `user_id`, `name`, `phone`, `email`, `request_date`, `status`, `created_at`) VALUES
(1, 14, 1, 'Ефремов', '344654654465', 'efremov@mail.ru', '2025-06-18 03:04:00', 'approved', '2025-06-09 22:13:35');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('admin','manager','client') COLLATE utf8mb4_unicode_ci DEFAULT 'client',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `birth_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `created_at`, `birth_date`) VALUES
(1, 'Ефремов', '$2y$10$t9D7oKY9RWVC4Yxqs1yGhud6mIR1F6h7/b21vCyZuKrW/eOQPK8Fu', 'efremov@mail.ru', 'client', '2025-06-09 18:08:16', '1972-06-06'),
(2, 'Иванов', '$2y$10$OYV/cjivxvGX2KxlwQOJWe22YEzNuf0Fq/bUA40hB7Iq3IYR.NGNW', 'gfhhgj@mail.re', 'admin', '2025-06-09 23:00:38', NULL);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`id`),
  ADD KEY `brand_id` (`brand_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Индексы таблицы `car_specifications`
--
ALTER TABLE `car_specifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `car_id` (`car_id`);

--
-- Индексы таблицы `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `comparisons`
--
ALTER TABLE `comparisons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `car_id` (`car_id`);

--
-- Индексы таблицы `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `car_id` (`car_id`);

--
-- Индексы таблицы `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `car_id` (`car_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `test_drive_requests`
--
ALTER TABLE `test_drive_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `car_id` (`car_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `cars`
--
ALTER TABLE `cars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT для таблицы `car_specifications`
--
ALTER TABLE `car_specifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT для таблицы `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `comparisons`
--
ALTER TABLE `comparisons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `test_drive_requests`
--
ALTER TABLE `test_drive_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `cars`
--
ALTER TABLE `cars`
  ADD CONSTRAINT `cars_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`),
  ADD CONSTRAINT `cars_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Ограничения внешнего ключа таблицы `car_specifications`
--
ALTER TABLE `car_specifications`
  ADD CONSTRAINT `car_specifications_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`);

--
-- Ограничения внешнего ключа таблицы `comparisons`
--
ALTER TABLE `comparisons`
  ADD CONSTRAINT `comparisons_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `comparisons_ibfk_2` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`);

--
-- Ограничения внешнего ключа таблицы `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `purchases_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `purchases_ibfk_2` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`);

--
-- Ограничения внешнего ключа таблицы `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `test_drive_requests`
--
ALTER TABLE `test_drive_requests`
  ADD CONSTRAINT `test_drive_requests_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`),
  ADD CONSTRAINT `test_drive_requests_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
