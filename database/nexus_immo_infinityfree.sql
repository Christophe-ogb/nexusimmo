


DROP TABLE IF EXISTS `listing_media_immo`;
CREATE TABLE `listing_media_immo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `listing_id` int(10) unsigned NOT NULL,
  `media_type` enum('image','video') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `position` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_listing` (`listing_id`),
  CONSTRAINT `fk_media_listing` FOREIGN KEY (`listing_id`) REFERENCES `listings_immo` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


LOCK TABLES `listing_media_immo` WRITE;
INSERT INTO `listing_media_immo` VALUES (3,2,'image','uploads/images/068a03f62cd6d6c359885400461eb826.jpg',0,'2026-07-24 15:18:35'),(4,2,'image','uploads/images/c58090516c1b28b6827ee5ad17f36078.jpg',1,'2026-07-24 15:18:35'),(5,2,'image','uploads/images/ae8635e0ab975373dd0c9ea38c44617b.jpg',2,'2026-07-24 15:18:36'),(6,2,'image','uploads/images/a48d4a29d4bdc2410beff3cf22bded21.jpg',3,'2026-07-24 15:18:36'),(7,2,'image','uploads/images/fea9fa304789faa3ddd2148503461331.jpg',4,'2026-07-24 15:18:36'),(8,2,'image','uploads/images/94d604e7369f6524a9f6ec3e4ebf1497.jpg',5,'2026-07-24 15:18:36'),(9,2,'image','uploads/images/405061b18c065d46f76ed17614ed4c6c.jpg',6,'2026-07-24 15:18:36'),(10,2,'image','uploads/images/31050413d8709b0614b3b215341d2bf3.jpg',7,'2026-07-24 15:18:36'),(11,2,'image','uploads/images/fd570dc241ae8fe9c0b2ff3fcad722dd.jpg',8,'2026-07-24 15:18:36');
UNLOCK TABLES;


DROP TABLE IF EXISTS `listings_immo`;
CREATE TABLE `listings_immo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `locality_id` int(10) unsigned NOT NULL,
  `listing_type` enum('location','vente') NOT NULL,
  `category` enum('maison','parcelle') NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `address_detail` varchar(255) DEFAULT NULL,
  `phone_call` varchar(20) NOT NULL,
  `whatsapp_number` varchar(20) NOT NULL,
  `status` enum('active','sold_rented','archived') NOT NULL DEFAULT 'active',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `views_count` int(10) unsigned NOT NULL DEFAULT 0,
  `sold_at` datetime DEFAULT NULL,
  `last_reminder_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_listings_user` (`user_id`),
  KEY `idx_type` (`listing_type`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`),
  KEY `idx_price` (`price`),
  KEY `idx_locality` (`locality_id`),
  FULLTEXT KEY `ft_search` (`title`,`description`),
  CONSTRAINT `fk_listings_locality` FOREIGN KEY (`locality_id`) REFERENCES `localities_immo` (`id`),
  CONSTRAINT `fk_listings_user` FOREIGN KEY (`user_id`) REFERENCES `users_immo` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


LOCK TABLES `listings_immo` WRITE;
INSERT INTO `listings_immo` VALUES (2,5,1,'location','maison','Chambre Salon Sanitaire','Disponible à Fidjrosse Sinoutin une 02 chambres salon sanitaire au premier étage avec 02 douches, compteur personnel à carte, deuxième position des pavés,seul au premier \r\n \r\nLoyer 70mil\r\n \r\nConditions :\r\n03 mois d\'avance \r\n03 mois de loyers prépayé \r\nCaution eau électricité 30.000 FCFA \r\nCommission',70000.00,NULL,'+2290150387418','+2290150387418','active',0,2,NULL,NULL,'2026-07-24 15:18:35','2026-07-24 17:45:48');
UNLOCK TABLES;


DROP TABLE IF EXISTS `localities_immo`;
CREATE TABLE `localities_immo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


LOCK TABLES `localities_immo` WRITE;
INSERT INTO `localities_immo` VALUES (1,'Cotonou','2026-07-19 10:27:12'),(2,'Abomey-Calavi','2026-07-19 10:27:12'),(3,'Porto-Novo','2026-07-19 10:27:12'),(4,'Parakou','2026-07-19 10:27:12'),(5,'Ouidah','2026-07-19 10:27:12');
UNLOCK TABLES;


DROP TABLE IF EXISTS `users_immo`;
CREATE TABLE `users_immo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `verification_token_hash` char(64) DEFAULT NULL,
  `verification_expires_at` datetime DEFAULT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


LOCK TABLES `users_immo` WRITE;
INSERT INTO `users_immo` VALUES (5,NULL,'happer880@gmail.com','+22950387418','$2y$10$E4Nv8urmAAXSwEA8wpUtaOyegXA/Vjn5MfQcNr3Ef.Z36zRUfgn0G','admin',1,NULL,NULL,NULL,'2026-07-24 12:25:17','2026-07-24 13:01:03'),(6,NULL,'jessyhope41@gmail.com','+22996800326','$2y$10$UoswdSh0KJpK2eDZI6KU7eSCojlgoYEm2uyiVDEgQN3450R1wvAoq','user',1,NULL,NULL,NULL,'2026-07-24 12:27:21','2026-07-24 12:27:21'),(7,NULL,'lydiesmith32@gmail.com','+2290168848073','$2y$10$CyOCmLwgx08wtOoUhaPZD.5hh260rzUfzDKQoEp/TIVoeR2/YJhMK','user',0,'939c3d3d4a12a051e2f24c7834a76e3d11aa6435cd98ce262dfe1926244aa300','2026-07-25 15:56:43',NULL,'2026-07-24 14:56:43','2026-07-24 14:56:43');
UNLOCK TABLES;


