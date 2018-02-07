CREATE TABLE `firebase_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) unsigned NOT NULL,
  `device_registration_token` VARCHAR(1024) NOT NULL,
  `created_at` TIMESTAMP DEFAULT NOW(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;