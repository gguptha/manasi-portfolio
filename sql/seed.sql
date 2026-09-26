-- Seed master data. Run after schema.sql.
-- The admin user is created by install.php (not here), so you set your own password.

SET NAMES utf8mb4;

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
  ('site_name', 'Manasi'),
  ('photographer_name', 'Manasi'),
  ('tagline', 'Wildlife photographer specialising in Indian forests and conservation storytelling'),
  ('about_text', 'Field notes from Indian forests — tigers, birds, reptiles, and the quiet hours between sightings. Photographs made on foot and from the hide, with attention to habitat and light.'),
  ('contact_email', ''),
  ('instagram_url', ''),
  ('youtube_url', ''),
  ('footer_text', 'All images © Manasi. Please do not use without permission.')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
  ('photo_landing_text', 'Field photographs from Indian forests — made on foot and from the hide, with attention to habitat and light.'),
  ('photo_landing_hero', ''),
  ('photo_landing_rows', '2'),
  ('photo_landing_cols', '2'),
  ('photo_landing_slots', '[0,0,0,0]');

INSERT INTO `categories` (`name`, `slug`, `description`, `sort_order`, `is_active`) VALUES
  ('Reptiles', 'reptiles', 'Snakes, lizards, crocodiles, and turtles photographed in the wild.', 1, 1),
  ('Amphibians', 'amphibians', 'Frogs, toads, and other amphibians from forest floors and monsoon pools.', 2, 1),
  ('Birds', 'birds', 'Resident and migratory birds across Indian landscapes.', 3, 1),
  ('Mammals', 'mammals', 'Tigers, leopards, deer, primates, and other mammals.', 4, 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`), `sort_order` = VALUES(`sort_order`);

INSERT INTO `parks` (`name`, `slug`, `location`, `description`, `sort_order`, `is_active`) VALUES
  ('Ranthambore', 'ranthambore', 'Rajasthan', 'Dry deciduous forest and lakes of Ranthambore National Park, known for tigers and birdlife.', 1, 1),
  ('Tadoba', 'tadoba', 'Maharashtra', 'Tadoba Andhari Tiger Reserve — dense teak forest and a strong tiger population.', 2, 1),
  ('Bandhavgarh', 'bandhavgarh', 'Madhya Pradesh', 'Sal forest and meadows around the Bandhavgarh fort.', 3, 1),
  ('Kanha', 'kanha', 'Madhya Pradesh', 'Kanha Tiger Reserve, inspiration for Kipling’s jungle and home to barasingha.', 4, 1),
  ('Corbett', 'corbett', 'Uttarakhand', 'Jim Corbett National Park in the Himalayan foothills.', 5, 1),
  ('Kaziranga', 'kaziranga', 'Assam', 'Floodplains of the Brahmaputra, famous for the greater one-horned rhinoceros.', 6, 1),
  ('Gir', 'gir', 'Gujarat', 'Last wild home of the Asiatic lion.', 7, 1),
  ('Nagarhole', 'nagarhole', 'Karnataka', 'Nagarhole National Park in the Western Ghats, part of the Nilgiri Biosphere.', 8, 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `location` = VALUES(`location`), `description` = VALUES(`description`), `sort_order` = VALUES(`sort_order`);

INSERT INTO `years` (`year`, `description`, `sort_order`, `is_active`) VALUES
  (2015, NULL, 2015, 1),
  (2016, NULL, 2016, 1),
  (2017, NULL, 2017, 1),
  (2018, NULL, 2018, 1),
  (2019, NULL, 2019, 1),
  (2020, NULL, 2020, 1),
  (2021, NULL, 2021, 1),
  (2022, NULL, 2022, 1),
  (2023, NULL, 2023, 1),
  (2024, NULL, 2024, 1),
  (2025, NULL, 2025, 1),
  (2026, NULL, 2026, 1)
ON DUPLICATE KEY UPDATE `sort_order` = VALUES(`sort_order`);
