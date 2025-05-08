-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Anamakine: localhost:3306
-- Üretim Zamanı: 08 May 2025, 17:49:48
-- Sunucu sürümü: 5.7.24
-- PHP Sürümü: 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `task_manager`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `tasks`
--

CREATE TABLE `tasks` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(64) NOT NULL,
  `description` varchar(255) NOT NULL,
  `status` enum('pending','in_progress','done') NOT NULL,
  `due_date` date NOT NULL,
  `is_deleted` bit(1) NOT NULL DEFAULT b'0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Tablo döküm verisi `tasks`
--

INSERT INTO `tasks` (`id`, `user_id`, `title`, `description`, `status`, `due_date`, `is_deleted`) VALUES
(1, 2, 'feed dog', 'buy dog food and feed dog', 'pending', '2025-05-10', b'0'),
(2, 2, 'feed cat', 'buy cat food and feed cat', 'pending', '2025-05-12', b'0'),
(12, 2, 'buy groceries', 'buy food for the week', 'done', '2025-05-18', b'0'),
(13, 5, 'write', 'write it come on', 'in_progress', '2025-05-15', b'1'),
(14, 5, 'sleep', 'zzzz', 'pending', '2025-05-20', b'0'),
(15, 5, 'walk', 'zzzz', 'in_progress', '2025-05-17', b'0'),
(16, 5, 'run', 'run a marathon', 'done', '2025-05-23', b'0'),
(17, 5, 'swim', 'go to the pool', 'pending', '2025-05-28', b'0');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `username`, `password`) VALUES
(2, 'John', '$2y$10$F4egX5tlc7SCCJqRDrW2luvlcs14OA/og4gGDz53O1fskpnPppYGe'),
(3, 'Alice', '$2y$10$5n0/kzGsXtHZvtJFuSEjVuF51tPmOhASz/cDF6w9NPPB0Zxg1CcLG'),
(4, 'Bob', '$2y$10$y/8FNBdfqi54JiPE9WSUDuM2qAHTUiWtyOrbC3YknEHkZoXK8cz72'),
(5, 'ahmet', '$2y$10$/eSpx/bXJutJDSr8bLqi5.B1NV3lNvx6kGn6pUjnwy.pChd5Eb2MK');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `password` (`password`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
