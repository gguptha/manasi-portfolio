# Manasi — Wildlife Photography Portfolio

PHP + MySQL website for a wildlife photography archive. Photographs are stored on disk; titles, descriptions, master data, and EXIF live in MySQL. Built for **GoDaddy shared hosting** (no Composer, no framework).

## What you get

Public site
- Home with a full-bleed photograph, collections, and a 4-across thumbnail grid
- **Categories** — reptiles, amphibians, birds, mammals (editable)
- **National Parks** — Ranthambore, Tadoba, and others (editable)
- **Year** — field-season archive
- **Videography** — YouTube/Vimeo embeds or uploaded MP4
- **Design** — stills / graphic work
- Photograph page with the original file, description, and camera EXIF

Admin (`/admin/`)
- Login-protected
- CRUD for categories, parks, and years
- Photo upload capturing title, description, category, park, year, featured/published flags
- Automatic EXIF (camera, lens, aperture, shutter, ISO, date, GPS when present)
- Videography and design uploads
- Site name, tagline, about text, social links, password change

## Requirements

- PHP 7.4 or 8.x
- MySQL 5.7+ / MariaDB 10.2+
- Extensions: `pdo_mysql`, `gd`, `fileinfo`, and `exif` (for camera data), plus `mbstring` if available

## GoDaddy deployment

1. In **cPanel → MySQL Databases**, create a database and a user, and add the user to the database. Note the full names (they often look like `user_portfolio`).
2. In **cPanel → Select PHP Version**, pick 8.1 or 8.2 and enable **pdo_mysql**, **gd**, **exif**, **fileinfo**.
3. Upload this project into `public_html` (or a subfolder). Keep the folder structure intact.
4. Make `uploads/` writable (permission **755** or **775**).
5. Visit `https://your-domain.com/install.php` and enter the database details plus an admin username/password.
6. Sign in at `/admin/`, then **delete `install.php`** from the server.
7. Upload photographs as **JPEG** (long edge about 2500–4000px). RAW files are not supported.

If the site lives in a subdirectory, set Base URL during install to `/foldername`, or leave `auto`.

### MySQL scripts (manual import)

If you prefer phpMyAdmin instead of the installer:

1. Copy `includes/config.example.php` to `includes/config.php` and fill in host, database, user, password.
2. Import `sql/schema.sql`, then `sql/seed.sql`.
3. Create an admin user (phpMyAdmin SQL tab will not hash passwords). Easiest: still run `install.php` after the SQL import — it will keep existing tables and create/update the admin account.
4. Create the folders under `uploads/` if they are missing.

### Upload size

`.user.ini` in the project root asks PHP for 32MB uploads. If large JPEGs fail, raise `upload_max_filesize` and `post_max_size` in cPanel, or export smaller web JPEGs. Prefer YouTube/Vimeo links for long films.

## Daily use

1. Open **Admin → Categories / National Parks / Years** and add any extra master data.
2. **Photographs → Upload photograph**. Choose the image, enter title and description, and select category, park, and year. If year is left blank, the app tries to use the EXIF capture year.
3. Tick **Featured on home** for the hero/selected edit.
4. Edit **Settings** for the photographer name, intro, Instagram, and footer credit.

Originals are stored in `uploads/photos/originals/`. Grid thumbnails (4:3) are in `uploads/photos/thumbs/`.

## Local notes

There is no Node build step. Point a PHP/MySQL stack (XAMPP, Laragon, MAMP) at this folder, create a database, and run `install.php`.
