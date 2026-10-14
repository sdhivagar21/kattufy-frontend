-- =====================================================================
-- Kattufy V1 — MySQL schema (39 main tables)
-- Design v0.2, matched to the Kattufy V1 Technical Architecture
-- Target: MySQL 8.0 / 8.4 LTS, InnoDB, utf8mb4 / utf8mb4_unicode_ci
--
-- Use this to create and review the database in MySQL Workbench or phpMyAdmin.
-- In the Laravel project the same tables are created by migrations
-- (php artisan migrate), which also create Laravel's sessions, cache and
-- queue tables and Spatie's model_has_roles, model_has_permissions and
-- role_has_permissions. Do not run both on the same database.
--
-- Tables are created in dependency order, so every foreign key points
-- to a table that already exists.
-- To start over:  DROP DATABASE kattufy;
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `kattufy` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `kattufy`;


-- ---------------------------------------------------------------------
-- ACCOUNTS
-- ---------------------------------------------------------------------

-- 1. users — Everyone who signs in — customers, providers and staff. Phone OTP is the only login, staff included (Architecture §7).
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key, AUTO_INCREMENT',
  `name` VARCHAR(150) NULL DEFAULT NULL COMMENT 'Display name. Nullable: the OTP flow creates the user before a name is known.',
  `phone` VARCHAR(16) NOT NULL COMMENT 'Mobile number in E.164 (+91XXXXXXXXXX). Login identity. Unique. Set to an anonymized placeholder on deletion.',
  `phone_verified_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'When the phone was verified by OTP.',
  `email` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Optional. Staff email for enquiry alerts. Unique when present.',
  `locale` VARCHAR(5) NOT NULL DEFAULT 'en' COMMENT 'Preferred language: en or ta.',
  `status` VARCHAR(20) NOT NULL DEFAULT 'active' COMMENT 'active, blocked, anonymized (PHP enum UserStatus).',
  `last_login_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last successful login.',
  `anonymized_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'When personal fields were erased on request. Users are anonymized, never hard-deleted.',
  `remember_token` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Laravel remember-me token (kept for the framework; long sessions come from session lifetime).',
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who created the record. Set by the HasUserAttribution trait from the logged-in user, never from request input. NULL = self sign-up, scheduled job, seeder or system process.',
  `updated_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who last edited the record (also set on soft delete). Same rules as created_by.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Record creation time, stored in UTC, shown in IST.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last modification time, stored in UTC.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_phone_unique` (`phone`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_status_index` (`status`),
  CONSTRAINT `users_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Everyone who signs in — customers, providers and staff. Phone OTP is the only login, staff included (Architecture §7).';

-- 2. otp_codes — One row per OTP sent. Source of truth for expiry, attempts, resend gap and send caps.
CREATE TABLE `otp_codes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key, AUTO_INCREMENT',
  `phone` VARCHAR(16) NOT NULL COMMENT 'Destination phone (E.164). Not an FK: the user may not exist yet.',
  `purpose` VARCHAR(30) NOT NULL DEFAULT 'login' COMMENT 'login, provider_phone_verification (staff send an OTP to a provider from the admin).',
  `code_hash` CHAR(64) NOT NULL COMMENT 'Hash of the 6-digit code (HMAC-SHA256 recommended, D-05). The code itself is never stored.',
  `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Verification attempts used; the code is dead at 5.',
  `expires_at` TIMESTAMP NOT NULL COMMENT 'created_at + 5 minutes.',
  `consumed_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Set on successful verification; a used code cannot be reused.',
  `sent_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'When the SendOtpSms job handed it to the gateway.',
  `ip_address` VARCHAR(45) NOT NULL COMMENT 'Requesting IP, for the 10-per-IP-per-hour limit.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Request time; drives the 60-second resend gap, hourly limits and the daily SMS cap.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last change (attempts, consumption).',
  PRIMARY KEY (`id`),
  KEY `otp_codes_phone_purpose_created_at_index` (`phone`, `purpose`, `created_at`),
  KEY `otp_codes_ip_address_created_at_index` (`ip_address`, `created_at`),
  KEY `otp_codes_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='One row per OTP sent. Source of truth for expiry, attempts, resend gap and send caps.';

-- 3. permissions — Permission names from the Architecture §8 matrix (see Roles & Permissions sheet).
CREATE TABLE `permissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key, AUTO_INCREMENT',
  `name` VARCHAR(255) NOT NULL COMMENT 'Permission key, e.g. credentials.view_documents.',
  `guard_name` VARCHAR(255) NOT NULL COMMENT 'Auth guard, web.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Package timestamp.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Package timestamp.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`, `guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Permission names from the Architecture §8 matrix (see Roles & Permissions sheet).';

-- 4. roles — Six roles: super_admin, verification_staff, onboarding_staff, content_staff, provider, customer.
CREATE TABLE `roles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key, AUTO_INCREMENT',
  `name` VARCHAR(255) NOT NULL COMMENT 'Role key (snake_case); labels come from lang files.',
  `guard_name` VARCHAR(255) NOT NULL COMMENT 'Auth guard, web.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Package timestamp.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Package timestamp.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`, `guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Six roles: super_admin, verification_staff, onboarding_staff, content_staff, provider, customer.';

-- ---------------------------------------------------------------------
-- SHARED
-- ---------------------------------------------------------------------

-- 5. audit_logs — Every admin create, update, delete, status change and lifecycle transition, with user, time, before and after values.
CREATE TABLE `audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key, AUTO_INCREMENT',
  `log_name` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Channel: default, auth, lifecycle, contact, system.',
  `description` TEXT NOT NULL COMMENT 'Human-readable action.',
  `subject_type` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Morph alias of the changed record (e.g. provider).',
  `event` VARCHAR(255) NULL DEFAULT NULL COMMENT 'created, updated, deleted, transitioned …',
  `subject_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'ID of the changed record.',
  `causer_type` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Morph alias of the actor (user); NULL for system jobs.',
  `causer_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Actor user ID; NULL for system jobs.',
  `properties` JSON NULL DEFAULT NULL COMMENT 'Before/after values and context. Phone numbers and OTP codes excluded.',
  `batch_uuid` CHAR(36) NULL DEFAULT NULL COMMENT 'Groups entries written together.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Log time.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Package timestamp (never updated in practice).',
  PRIMARY KEY (`id`),
  KEY `audit_logs_log_name_index` (`log_name`),
  KEY `subject` (`subject_type`, `subject_id`),
  KEY `causer` (`causer_type`, `causer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Every admin create, update, delete, status change and lifecycle transition, with user, time, before and after values.';

-- ---------------------------------------------------------------------
-- GEOGRAPHY
-- ---------------------------------------------------------------------

-- 6. states — Indian states (V1 seeds Tamil Nadu).
CREATE TABLE `states` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key, AUTO_INCREMENT',
  `name_en` VARCHAR(100) NOT NULL COMMENT 'English name.',
  `name_ta` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Tamil name. English is shown (marked lang="en") when NULL.',
  `slug` VARCHAR(120) NOT NULL COMMENT 'URL slug in English letters, lowercase kebab-case, unique globally.',
  `code` VARCHAR(5) NOT NULL COMMENT 'State code, e.g. TN. Unique.',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Switch off instead of deleting; inactive rows are hidden from the public site and pickers.',
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Display order (ascending).',
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who created the record. Set by the HasUserAttribution trait from the logged-in user, never from request input. NULL = self sign-up, scheduled job, seeder or system process.',
  `updated_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who last edited the record (also set on soft delete). Same rules as created_by.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Record creation time, stored in UTC, shown in IST.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last modification time, stored in UTC.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `states_slug_unique` (`slug`),
  UNIQUE KEY `states_code_unique` (`code`),
  CONSTRAINT `states_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `states_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Indian states (V1 seeds Tamil Nadu).';

-- 7. districts — Districts within a state.
CREATE TABLE `districts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key, AUTO_INCREMENT',
  `state_id` BIGINT UNSIGNED NOT NULL COMMENT 'Parent state.',
  `name_en` VARCHAR(100) NOT NULL COMMENT 'English name.',
  `name_ta` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Tamil name. English is shown (marked lang="en") when NULL.',
  `slug` VARCHAR(120) NOT NULL COMMENT 'URL slug in English letters, lowercase kebab-case, unique within its state.',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Switch off instead of deleting; inactive rows are hidden from the public site and pickers.',
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Display order (ascending).',
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who created the record. Set by the HasUserAttribution trait from the logged-in user, never from request input. NULL = self sign-up, scheduled job, seeder or system process.',
  `updated_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who last edited the record (also set on soft delete). Same rules as created_by.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Record creation time, stored in UTC, shown in IST.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last modification time, stored in UTC.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `districts_state_id_slug_unique` (`state_id`, `slug`),
  CONSTRAINT `districts_state_id_foreign` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `districts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `districts_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Districts within a state.';

-- 8. cities — Cities; the slug is the city segment of URLs (/architects/chennai/). Provider slugs may never equal a city slug.
CREATE TABLE `cities` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key, AUTO_INCREMENT',
  `district_id` BIGINT UNSIGNED NOT NULL COMMENT 'Parent district.',
  `name_en` VARCHAR(100) NOT NULL COMMENT 'English name.',
  `name_ta` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Tamil name. English is shown (marked lang="en") when NULL.',
  `slug` VARCHAR(120) NOT NULL COMMENT 'URL slug in English letters, lowercase kebab-case, unique globally.',
  `latitude` DECIMAL(10,7) NULL DEFAULT NULL COMMENT 'City centre latitude.',
  `longitude` DECIMAL(10,7) NULL DEFAULT NULL COMMENT 'City centre longitude.',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Switch off instead of deleting; inactive rows are hidden from the public site and pickers.',
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Display order (ascending).',
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who created the record. Set by the HasUserAttribution trait from the logged-in user, never from request input. NULL = self sign-up, scheduled job, seeder or system process.',
  `updated_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who last edited the record (also set on soft delete). Same rules as created_by.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Record creation time, stored in UTC, shown in IST.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last modification time, stored in UTC.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `cities_slug_unique` (`slug`),
  CONSTRAINT `cities_district_id_foreign` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `cities_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cities_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cities; the slug is the city segment of URLs (/architects/chennai/). Provider slugs may never equal a city slug.';

-- 9. zones — Service zones in a city. Providers declare the zones they serve; a locality matches through its zone.
CREATE TABLE `zones` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key, AUTO_INCREMENT',
  `city_id` BIGINT UNSIGNED NOT NULL COMMENT 'Parent city.',
  `name_en` VARCHAR(100) NOT NULL COMMENT 'English name.',
  `name_ta` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Tamil name. English is shown (marked lang="en") when NULL.',
  `slug` VARCHAR(120) NOT NULL COMMENT 'URL slug in English letters, lowercase kebab-case, unique within its city.',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Switch off instead of deleting; inactive rows are hidden from the public site and pickers.',
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Display order (ascending).',
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who created the record. Set by the HasUserAttribution trait from the logged-in user, never from request input. NULL = self sign-up, scheduled job, seeder or system process.',
  `updated_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who last edited the record (also set on soft delete). Same rules as created_by.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Record creation time, stored in UTC, shown in IST.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last modification time, stored in UTC.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `zones_city_id_slug_unique` (`city_id`, `slug`),
  CONSTRAINT `zones_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `zones_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `zones_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Service zones in a city. Providers declare the zones they serve; a locality matches through its zone.';

-- 10. localities — Neighbourhoods (Velachery, Adyar …). Locality pages exist only above kattufy.locality_page_threshold (default 5).
CREATE TABLE `localities` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key, AUTO_INCREMENT',
  `city_id` BIGINT UNSIGNED NOT NULL COMMENT 'Parent city.',
  `zone_id` BIGINT UNSIGNED NOT NULL COMMENT 'The zone that contains this locality; search matches providers through it.',
  `name_en` VARCHAR(150) NOT NULL COMMENT 'English name.',
  `name_ta` VARCHAR(150) NULL DEFAULT NULL COMMENT 'Tamil name. English is shown (marked lang="en") when NULL.',
  `slug` VARCHAR(160) NOT NULL COMMENT 'URL slug in English letters, lowercase kebab-case, unique within its city.',
  `aliases` VARCHAR(500) NULL DEFAULT NULL COMMENT 'Alternate spellings for location search, comma-separated (e.g. Velacheri).',
  `latitude` DECIMAL(10,7) NULL DEFAULT NULL COMMENT 'Latitude.',
  `longitude` DECIMAL(10,7) NULL DEFAULT NULL COMMENT 'Longitude.',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Switch off instead of deleting; inactive rows are hidden from the public site and pickers.',
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Display order (ascending).',
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who created the record. Set by the HasUserAttribution trait from the logged-in user, never from request input. NULL = self sign-up, scheduled job, seeder or system process.',
  `updated_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who last edited the record (also set on soft delete). Same rules as created_by.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Record creation time, stored in UTC, shown in IST.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last modification time, stored in UTC.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `localities_city_id_slug_unique` (`city_id`, `slug`),
  KEY `localities_zone_id_index` (`zone_id`),
  CONSTRAINT `localities_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `localities_zone_id_foreign` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `localities_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `localities_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Neighbourhoods (Velachery, Adyar …). Locality pages exist only above kattufy.locality_page_threshold (default 5).';

-- 11. pincodes — Six-digit PIN codes. A pincode resolves to a locality; it is never a parent.
CREATE TABLE `pincodes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key, AUTO_INCREMENT',
  `code` CHAR(6) NOT NULL COMMENT 'PIN code. Unique.',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Switch off instead of deleting; inactive rows are hidden from the public site and pickers.',
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who created the record. Set by the HasUserAttribution trait from the logged-in user, never from request input. NULL = self sign-up, scheduled job, seeder or system process.',
  `updated_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who last edited the record (also set on soft delete). Same rules as created_by.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Record creation time, stored in UTC, shown in IST.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last modification time, stored in UTC.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `pincodes_code_unique` (`code`),
  CONSTRAINT `pincodes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pincodes_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Six-digit PIN codes. A pincode resolves to a locality; it is never a parent.';

-- 12. locality_pincode — Many-to-many between localities and pincodes.
CREATE TABLE `locality_pincode` (
  `locality_id` BIGINT UNSIGNED NOT NULL COMMENT 'Locality.',
  `pincode_id` BIGINT UNSIGNED NOT NULL COMMENT 'Pincode.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'When the link was made (withTimestamps).',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'When the link was last changed (withTimestamps).',
  PRIMARY KEY (`locality_id`, `pincode_id`),
  KEY `locality_pincode_pincode_id_locality_id_index` (`pincode_id`, `locality_id`),
  CONSTRAINT `locality_pincode_locality_id_foreign` FOREIGN KEY (`locality_id`) REFERENCES `localities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `locality_pincode_pincode_id_foreign` FOREIGN KEY (`pincode_id`) REFERENCES `pincodes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Many-to-many between localities and pincodes.';

-- ---------------------------------------------------------------------
-- DIRECTORY
-- ---------------------------------------------------------------------

-- 13. category_groups — Build-stage groupings, e.g. the /building-a-house/ hub.
CREATE TABLE `category_groups` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key, AUTO_INCREMENT',
  `name_en` VARCHAR(150) NOT NULL COMMENT 'English name.',
  `name_ta` VARCHAR(150) NULL DEFAULT NULL COMMENT 'Tamil name. English is shown (marked lang="en") when NULL.',
  `slug` VARCHAR(160) NOT NULL COMMENT 'URL slug in English letters, lowercase kebab-case, unique globally.',
  `description_en` TEXT NULL DEFAULT NULL COMMENT 'English description.',
  `description_ta` TEXT NULL DEFAULT NULL COMMENT 'Tamil description.',
  `icon` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Icon key.',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Switch off instead of deleting; inactive rows are hidden from the public site and pickers.',
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Display order (ascending).',
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who created the record. Set by the HasUserAttribution trait from the logged-in user, never from request input. NULL = self sign-up, scheduled job, seeder or system process.',
  `updated_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who last edited the record (also set on soft delete). Same rules as created_by.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Record creation time, stored in UTC, shown in IST.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last modification time, stored in UTC.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_groups_slug_unique` (`slug`),
  CONSTRAINT `category_groups_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `category_groups_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Build-stage groupings, e.g. the /building-a-house/ hub.';

-- 14. categories — Directory categories (architects, plumbers …); the first URL segment (/architects/).
CREATE TABLE `categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key, AUTO_INCREMENT',
  `category_group_id` BIGINT UNSIGNED NOT NULL COMMENT 'Build-stage group.',
  `name_en` VARCHAR(150) NOT NULL COMMENT 'English name.',
  `name_ta` VARCHAR(150) NULL DEFAULT NULL COMMENT 'Tamil name. English is shown (marked lang="en") when NULL.',
  `slug` VARCHAR(160) NOT NULL COMMENT 'URL slug in English letters, lowercase kebab-case, unique globally.',
  `description_en` TEXT NULL DEFAULT NULL COMMENT 'English description.',
  `description_ta` TEXT NULL DEFAULT NULL COMMENT 'Tamil description.',
  `meta_description_en` VARCHAR(170) NULL DEFAULT NULL COMMENT 'SEO meta description (English).',
  `meta_description_ta` VARCHAR(170) NULL DEFAULT NULL COMMENT 'SEO meta description (Tamil).',
  `icon` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Icon key.',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Switch off instead of deleting; inactive rows are hidden from the public site and pickers.',
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Display order (ascending).',
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who created the record. Set by the HasUserAttribution trait from the logged-in user, never from request input. NULL = self sign-up, scheduled job, seeder or system process.',
  `updated_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who last edited the record (also set on soft delete). Same rules as created_by.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Record creation time, stored in UTC, shown in IST.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last modification time, stored in UTC.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`),
  KEY `categories_category_group_id_sort_order_index` (`category_group_id`, `sort_order`),
  CONSTRAINT `categories_category_group_id_foreign` FOREIGN KEY (`category_group_id`) REFERENCES `category_groups` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `categories_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `categories_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Directory categories (architects, plumbers …); the first URL segment (/architects/).';

-- 15. services — Specialisations within a category, used as optional search filters.
CREATE TABLE `services` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key, AUTO_INCREMENT',
  `category_id` BIGINT UNSIGNED NOT NULL COMMENT 'Parent category.',
  `name_en` VARCHAR(150) NOT NULL COMMENT 'English name.',
  `name_ta` VARCHAR(150) NULL DEFAULT NULL COMMENT 'Tamil name. English is shown (marked lang="en") when NULL.',
  `slug` VARCHAR(160) NOT NULL COMMENT 'URL slug in English letters, lowercase kebab-case, unique within its category.',
  `description_en` TEXT NULL DEFAULT NULL COMMENT 'English description.',
  `description_ta` TEXT NULL DEFAULT NULL COMMENT 'Tamil description.',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Switch off instead of deleting; inactive rows are hidden from the public site and pickers.',
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Display order (ascending).',
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who created the record. Set by the HasUserAttribution trait from the logged-in user, never from request input. NULL = self sign-up, scheduled job, seeder or system process.',
  `updated_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who last edited the record (also set on soft delete). Same rules as created_by.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Record creation time, stored in UTC, shown in IST.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last modification time, stored in UTC.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `services_category_id_slug_unique` (`category_id`, `slug`),
  CONSTRAINT `services_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `services_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `services_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Specialisations within a category, used as optional search filters.';

-- 16. provider_types — Kind of provider (professional, contractor, supplier …). Required to publish.
CREATE TABLE `provider_types` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key, AUTO_INCREMENT',
  `name_en` VARCHAR(100) NOT NULL COMMENT 'English name.',
  `name_ta` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Tamil name. English is shown (marked lang="en") when NULL.',
  `slug` VARCHAR(120) NOT NULL COMMENT 'URL slug in English letters, lowercase kebab-case, unique globally.',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Switch off instead of deleting; inactive rows are hidden from the public site and pickers.',
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Display order (ascending).',
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who created the record. Set by the HasUserAttribution trait from the logged-in user, never from request input. NULL = self sign-up, scheduled job, seeder or system process.',
  `updated_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who last edited the record (also set on soft delete). Same rules as created_by.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Record creation time, stored in UTC, shown in IST.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last modification time, stored in UTC.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `provider_types_slug_unique` (`slug`),
  CONSTRAINT `provider_types_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `provider_types_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Kind of provider (professional, contractor, supplier …). Required to publish.';

-- 17. provider_subtypes — Finer classification within a provider type.
CREATE TABLE `provider_subtypes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key, AUTO_INCREMENT',
  `provider_type_id` BIGINT UNSIGNED NOT NULL COMMENT 'Parent type.',
  `name_en` VARCHAR(100) NOT NULL COMMENT 'English name.',
  `name_ta` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Tamil name. English is shown (marked lang="en") when NULL.',
  `slug` VARCHAR(120) NOT NULL COMMENT 'URL slug in English letters, lowercase kebab-case, unique within its type.',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Switch off instead of deleting; inactive rows are hidden from the public site and pickers.',
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Display order (ascending).',
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who created the record. Set by the HasUserAttribution trait from the logged-in user, never from request input. NULL = self sign-up, scheduled job, seeder or system process.',
  `updated_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who last edited the record (also set on soft delete). Same rules as created_by.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Record creation time, stored in UTC, shown in IST.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last modification time, stored in UTC.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `provider_subtypes_provider_type_id_slug_unique` (`provider_type_id`, `slug`),
  CONSTRAINT `provider_subtypes_provider_type_id_foreign` FOREIGN KEY (`provider_type_id`) REFERENCES `provider_types` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `provider_subtypes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `provider_subtypes_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Finer classification within a provider type.';

-- 18. category_city_status — Live or Coming soon per category-city pair. Live at kattufy.live_threshold (default 10) published providers.
CREATE TABLE `category_city_status` (
  `category_id` BIGINT UNSIGNED NOT NULL COMMENT 'Category (part of the composite key).',
  `city_id` BIGINT UNSIGNED NOT NULL COMMENT 'City (part of the composite key).',
  `status` VARCHAR(20) NOT NULL DEFAULT 'coming_soon' COMMENT 'live or coming_soon. Coming soon pages show the fallback enquiry, carry noindex and stay out of the sitemap.',
  `is_held` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Staff hold the pair at Coming soon; RefreshCategoryGates leaves it alone.',
  `hold_reason` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Why staff are holding it.',
  `published_provider_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Published providers for the pair, recalculated hourly.',
  `status_changed_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last switch between live and coming_soon.',
  `last_evaluated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last RefreshCategoryGates run.',
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who created the record. Set by the HasUserAttribution trait from the logged-in user, never from request input. NULL = self sign-up, scheduled job, seeder or system process.',
  `updated_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who last edited the record (also set on soft delete). Same rules as created_by.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Record creation time, stored in UTC, shown in IST.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last modification time, stored in UTC.',
  PRIMARY KEY (`category_id`, `city_id`),
  KEY `category_city_status_city_id_status_index` (`city_id`, `status`),
  CONSTRAINT `category_city_status_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `category_city_status_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `category_city_status_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `category_city_status_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Live or Coming soon per category-city pair. Live at kattufy.live_threshold (default 10) published providers.';

-- ---------------------------------------------------------------------
-- PROVIDERS
-- ---------------------------------------------------------------------

-- 19. brands — Brands suppliers stock or install. Brand names are searchable (FULLTEXT).
CREATE TABLE `brands` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key, AUTO_INCREMENT',
  `name_en` VARCHAR(150) NOT NULL COMMENT 'English name.',
  `name_ta` VARCHAR(150) NULL DEFAULT NULL COMMENT 'Tamil name. English is shown (marked lang="en") when NULL.',
  `slug` VARCHAR(160) NOT NULL COMMENT 'URL slug in English letters, lowercase kebab-case, unique globally.',
  `website_url` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Brand website.',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Switch off instead of deleting; inactive rows are hidden from the public site and pickers.',
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Display order (ascending).',
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who created the record. Set by the HasUserAttribution trait from the logged-in user, never from request input. NULL = self sign-up, scheduled job, seeder or system process.',
  `updated_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who last edited the record (also set on soft delete). Same rules as created_by.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Record creation time, stored in UTC, shown in IST.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last modification time, stored in UTC.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `brands_slug_unique` (`slug`),
  FULLTEXT KEY `brands_name_fulltext` (`name_en`, `name_ta`),
  CONSTRAINT `brands_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `brands_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Brands suppliers stock or install. Brand names are searchable (FULLTEXT).';

-- 20. product_categories — PROPOSED — products suppliers sell (tiles, cement …); the product segment of /suppliers/tiles/chennai/.
CREATE TABLE `product_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key, AUTO_INCREMENT',
  `name_en` VARCHAR(150) NOT NULL COMMENT 'English name.',
  `name_ta` VARCHAR(150) NULL DEFAULT NULL COMMENT 'Tamil name. English is shown (marked lang="en") when NULL.',
  `slug` VARCHAR(160) NOT NULL COMMENT 'URL slug in English letters, lowercase kebab-case, unique globally.',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Switch off instead of deleting; inactive rows are hidden from the public site and pickers.',
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Display order (ascending).',
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who created the record. Set by the HasUserAttribution trait from the logged-in user, never from request input. NULL = self sign-up, scheduled job, seeder or system process.',
  `updated_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who last edited the record (also set on soft delete). Same rules as created_by.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Record creation time, stored in UTC, shown in IST.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last modification time, stored in UTC.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_categories_slug_unique` (`slug`),
  CONSTRAINT `product_categories_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_categories_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='PROPOSED — products suppliers sell (tiles, cement …); the product segment of /suppliers/tiles/chennai/.';

-- 21. providers — Service providers and suppliers. Status changes ONLY through LifecycleService::transition().
CREATE TABLE `providers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key, AUTO_INCREMENT',
  `user_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Owner account. Set when the provider first logs in with the verified phone.',
  `provider_type_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Type. Nullable while Found; required to publish.',
  `provider_subtype_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Optional subtype.',
  `business_name_en` VARCHAR(200) NOT NULL COMMENT 'English business name.',
  `business_name_ta` VARCHAR(200) NULL DEFAULT NULL COMMENT 'Tamil business name. English is shown (marked lang="en") when NULL.',
  `slug` VARCHAR(220) NOT NULL COMMENT 'Profile slug (/architects/abc-architects-chennai/). Unique; validation rejects any slug equal to a city slug.',
  `description_en` TEXT NULL DEFAULT NULL COMMENT 'English description.',
  `description_ta` TEXT NULL DEFAULT NULL COMMENT 'Tamil description.',
  `description_ta_status` VARCHAR(20) NOT NULL DEFAULT 'empty' COMMENT 'empty, machine_draft, reviewed. Tamil shows publicly only when reviewed.',
  `description_ta_reviewed_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Content staff member who marked the Tamil reviewed.',
  `description_ta_reviewed_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'When the Tamil was marked reviewed.',
  `status` VARCHAR(20) NOT NULL DEFAULT 'found' COMMENT 'found, consented, under_review, published, needs_update, suspended. There is no ''claimed'' status.',
  `status_changed_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last lifecycle transition.',
  `consent_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Fact: when consent was first recorded (copy of the consents row).',
  `phone_verified_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Fact: phone verified by OTP. Guard for Consented → Under review; drives the ''Phone verified'' badge.',
  `claimed_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Fact: when the owner claimed the listing.',
  `published_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Fact: set by PublishGate on pass.',
  `last_verified_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Fact: last re-verification; FlagStaleListings flags 12 months without update or re-check.',
  `reviewed_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Verification staff member recorded by PublishGate.',
  `phone` VARCHAR(16) NULL DEFAULT NULL COMMENT 'PRIVATE. Primary phone (E.164). Never in HTML, JSON-LD, sitemaps, OG tags, search results or cached pages; only via ContactRevealService.',
  `whatsapp_number` VARCHAR(16) NULL DEFAULT NULL COMMENT 'PRIVATE. Used to build the wa.me link after reveal.',
  `email` VARCHAR(255) NULL DEFAULT NULL COMMENT 'PRIVATE. Same exposure rules as phone.',
  `allow_call` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Contact option: Call button enabled.',
  `allow_whatsapp` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Contact option: WhatsApp button enabled.',
  `allow_enquiry` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Contact option: Send enquiry button enabled.',
  `website_url` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Public website.',
  `address` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Business address; public only when show_address = 1 and consent scope allows.',
  `show_address` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Whether the address may be shown.',
  `city_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Base city (Architecture: city scope is a normal city_id column).',
  `locality_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Base locality — the optional locality tag (C-11).',
  `working_hours` JSON NULL DEFAULT NULL COMMENT 'Opening hours by weekday; provider edits save immediately.',
  `established_year` SMALLINT UNSIGNED NULL DEFAULT NULL COMMENT 'Year the business started.',
  `portfolio_status` VARCHAR(20) NOT NULL DEFAULT 'not_recorded' COMMENT 'not_recorded, has_portfolio, no_portfolio. PublishGate needs a recorded value.',
  `completeness_score` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0–100. First ranking key; recalculated on save and nightly.',
  `source` VARCHAR(30) NULL DEFAULT NULL COMMENT 'How the candidate was found: telecall, import, referral, self_signup.',
  `last_call_outcome` VARCHAR(30) NULL DEFAULT NULL COMMENT 'Telecaller''s last call result: interested, call_back, not_interested, wrong_number, no_answer.',
  `last_called_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'When the last onboarding call happened.',
  `do_not_contact` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Never call this candidate again; respected by onboarding screens.',
  `assigned_to` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Onboarding staff member responsible.',
  `unreviewed_edits_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Set when a provider edits a Published profile; puts it on the staff review list until cleared.',
  `anonymized_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'When personal fields and media were removed on a deletion request.',
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who created the record. Set by the HasUserAttribution trait from the logged-in user, never from request input. NULL = self sign-up, scheduled job, seeder or system process.',
  `updated_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who last edited the record (also set on soft delete). Same rules as created_by.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Record creation time, stored in UTC, shown in IST.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last modification time, stored in UTC.',
  `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Soft-delete marker. Soft-deleted rows are hidden from every public query.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `providers_slug_unique` (`slug`),
  KEY `providers_status_provider_type_id_index` (`status`, `provider_type_id`),
  KEY `providers_status_city_id_index` (`status`, `city_id`),
  KEY `providers_status_last_verified_at_index` (`status`, `last_verified_at`),
  KEY `providers_phone_index` (`phone`),
  FULLTEXT KEY `providers_search_fulltext` (`business_name_en`, `business_name_ta`, `description_en`, `description_ta`),
  CONSTRAINT `providers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `providers_provider_type_id_foreign` FOREIGN KEY (`provider_type_id`) REFERENCES `provider_types` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `providers_provider_subtype_id_foreign` FOREIGN KEY (`provider_subtype_id`) REFERENCES `provider_subtypes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `providers_description_ta_reviewed_by_foreign` FOREIGN KEY (`description_ta_reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `providers_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `providers_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `providers_locality_id_foreign` FOREIGN KEY (`locality_id`) REFERENCES `localities` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `providers_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `providers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `providers_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Service providers and suppliers. Status changes ONLY through LifecycleService::transition().';

-- 22. provider_categories — Categories a provider is listed under; at least one to publish. A change moves a Published profile to Under review.
CREATE TABLE `provider_categories` (
  `provider_id` BIGINT UNSIGNED NOT NULL COMMENT 'Provider (owner side).',
  `category_id` BIGINT UNSIGNED NOT NULL COMMENT 'Linked category. RESTRICT: reference data in use cannot be deleted.',
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Primary category used in the profile URL; one per provider, enforced by ProviderProfileService.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'When the link was made (withTimestamps).',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'When the link was last changed (withTimestamps).',
  PRIMARY KEY (`provider_id`, `category_id`),
  KEY `provider_categories_category_id_provider_id_index` (`category_id`, `provider_id`),
  CONSTRAINT `provider_categories_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `provider_categories_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Categories a provider is listed under; at least one to publish. A change moves a Published profile to Under review.';

-- 23. provider_zones — Zones a provider serves (service area); required to publish.
CREATE TABLE `provider_zones` (
  `provider_id` BIGINT UNSIGNED NOT NULL COMMENT 'Provider (owner side).',
  `zone_id` BIGINT UNSIGNED NOT NULL COMMENT 'Linked zone. RESTRICT: reference data in use cannot be deleted.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'When the link was made (withTimestamps).',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'When the link was last changed (withTimestamps).',
  PRIMARY KEY (`provider_id`, `zone_id`),
  KEY `provider_zones_zone_id_provider_id_index` (`zone_id`, `provider_id`),
  CONSTRAINT `provider_zones_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `provider_zones_zone_id_foreign` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Zones a provider serves (service area); required to publish.';

-- 24. provider_services — Specialisations a provider offers.
CREATE TABLE `provider_services` (
  `provider_id` BIGINT UNSIGNED NOT NULL COMMENT 'Provider (owner side).',
  `service_id` BIGINT UNSIGNED NOT NULL COMMENT 'Linked service. RESTRICT: reference data in use cannot be deleted.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'When the link was made (withTimestamps).',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'When the link was last changed (withTimestamps).',
  PRIMARY KEY (`provider_id`, `service_id`),
  KEY `provider_services_service_id_provider_id_index` (`service_id`, `provider_id`),
  CONSTRAINT `provider_services_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `provider_services_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Specialisations a provider offers.';

-- 25. provider_brands — Brands a supplier stocks or installs.
CREATE TABLE `provider_brands` (
  `provider_id` BIGINT UNSIGNED NOT NULL COMMENT 'Provider (owner side).',
  `brand_id` BIGINT UNSIGNED NOT NULL COMMENT 'Linked brand. RESTRICT: reference data in use cannot be deleted.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'When the link was made (withTimestamps).',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'When the link was last changed (withTimestamps).',
  PRIMARY KEY (`provider_id`, `brand_id`),
  KEY `provider_brands_brand_id_provider_id_index` (`brand_id`, `provider_id`),
  CONSTRAINT `provider_brands_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `provider_brands_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Brands a supplier stocks or installs.';

-- 26. provider_product_categories — Product categories a supplier sells.
CREATE TABLE `provider_product_categories` (
  `provider_id` BIGINT UNSIGNED NOT NULL COMMENT 'Provider (owner side).',
  `product_category_id` BIGINT UNSIGNED NOT NULL COMMENT 'Linked product category. RESTRICT: reference data in use cannot be deleted.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'When the link was made (withTimestamps).',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'When the link was last changed (withTimestamps).',
  PRIMARY KEY (`provider_id`, `product_category_id`),
  KEY `ppc_product_category_id_provider_id_index` (`product_category_id`, `provider_id`),
  CONSTRAINT `provider_product_categories_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `provider_product_categories_product_category_id_foreign` FOREIGN KEY (`product_category_id`) REFERENCES `product_categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Product categories a supplier sells.';

-- 27. portfolio_items — Past projects shown on the profile. 'Portfolio shown' badge = at least one published item.
CREATE TABLE `portfolio_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key, AUTO_INCREMENT',
  `provider_id` BIGINT UNSIGNED NOT NULL COMMENT 'Owning provider.',
  `title_en` VARCHAR(200) NOT NULL COMMENT 'English title.',
  `title_ta` VARCHAR(200) NULL DEFAULT NULL COMMENT 'Tamil title. English is shown (marked lang="en") when NULL.',
  `description_en` TEXT NULL DEFAULT NULL COMMENT 'English description.',
  `description_ta` TEXT NULL DEFAULT NULL COMMENT 'Tamil description.',
  `locality_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Where the project was done.',
  `completed_on` DATE NULL DEFAULT NULL COMMENT 'Completion date.',
  `is_published` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Counts toward the badge and ranking only when 1.',
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Display order (ascending).',
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who created the record. Set by the HasUserAttribution trait from the logged-in user, never from request input. NULL = self sign-up, scheduled job, seeder or system process.',
  `updated_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who last edited the record (also set on soft delete). Same rules as created_by.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Record creation time, stored in UTC, shown in IST.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last modification time, stored in UTC.',
  `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Soft-delete marker. Soft-deleted rows are hidden from every public query.',
  PRIMARY KEY (`id`),
  KEY `portfolio_items_provider_id_is_published_sort_order_index` (`provider_id`, `is_published`, `sort_order`),
  CONSTRAINT `portfolio_items_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `portfolio_items_locality_id_foreign` FOREIGN KEY (`locality_id`) REFERENCES `localities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `portfolio_items_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `portfolio_items_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Past projects shown on the profile. ''Portfolio shown'' badge = at least one published item.';

-- 28. media — Every uploaded file, polymorphic. Photos get public WebP versions; credential documents stay private with no public version.
CREATE TABLE `media` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key, AUTO_INCREMENT',
  `mediable_type` VARCHAR(255) NOT NULL COMMENT 'Morph alias of the owner: provider, portfolio_item, credential, brand, category, content_page.',
  `mediable_id` BIGINT UNSIGNED NOT NULL COMMENT 'Owner ID.',
  `collection` VARCHAR(30) NOT NULL COMMENT 'logo, cover, portfolio, credential_document, share_image, brand_logo, category_icon, content_image.',
  `visibility` VARCHAR(10) NOT NULL DEFAULT 'public' COMMENT 'public or private. credential_document is always private.',
  `original_disk` VARCHAR(30) NOT NULL DEFAULT 'private' COMMENT 'Originals always land on the private disk and are never published.',
  `original_path` VARCHAR(500) NOT NULL COMMENT 'Random, non-guessable path of the original.',
  `variants` JSON NULL DEFAULT NULL COMMENT 'Public files: WebP at 400/800/1600 px + JPEG fallback (or 1200×630 share image). Always NULL for private media.',
  `mime_type` VARCHAR(100) NOT NULL COMMENT 'Server-checked MIME: image/jpeg, image/png, image/webp, application/pdf.',
  `size_bytes` INT UNSIGNED NOT NULL COMMENT 'Original size; max 8 MB.',
  `width` SMALLINT UNSIGNED NULL DEFAULT NULL COMMENT 'Pixel width, for explicit width/height in HTML.',
  `height` SMALLINT UNSIGNED NULL DEFAULT NULL COMMENT 'Pixel height.',
  `alt_en` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Alt text (English).',
  `alt_ta` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Alt text (Tamil).',
  `original_filename` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Uploader''s filename, display only.',
  `checksum_sha256` CHAR(64) NULL DEFAULT NULL COMMENT 'Hash of the original, for duplicates and integrity.',
  `processing_status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, processing, ready, failed (ProcessUploadedImage).',
  `processed_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'When processing finished.',
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Display order (ascending).',
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who created the record. Set by the HasUserAttribution trait from the logged-in user, never from request input. NULL = self sign-up, scheduled job, seeder or system process.',
  `updated_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who last edited the record (also set on soft delete). Same rules as created_by.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Record creation time, stored in UTC, shown in IST.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last modification time, stored in UTC.',
  `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Soft-delete marker. Soft-deleted rows are hidden from every public query.',
  PRIMARY KEY (`id`),
  KEY `media_mediable_type_mediable_id_collection_sort_order_index` (`mediable_type`, `mediable_id`, `collection`, `sort_order`),
  KEY `media_processing_status_index` (`processing_status`),
  CONSTRAINT `media_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `media_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Every uploaded file, polymorphic. Photos get public WebP versions; credential documents stay private with no public version.';

-- ---------------------------------------------------------------------
-- VERIFICATION
-- ---------------------------------------------------------------------

-- 29. issuing_authorities — Bodies whose registers credentials are checked against (COA, CMDA, GCC, TN RERA …).
CREATE TABLE `issuing_authorities` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key, AUTO_INCREMENT',
  `name_en` VARCHAR(200) NOT NULL COMMENT 'English name.',
  `name_ta` VARCHAR(200) NULL DEFAULT NULL COMMENT 'Tamil name. English is shown (marked lang="en") when NULL.',
  `slug` VARCHAR(220) NOT NULL COMMENT 'URL slug in English letters, lowercase kebab-case, unique globally.',
  `state_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'State; NULL = national.',
  `website_url` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Authority website.',
  `register_url` VARCHAR(500) NULL DEFAULT NULL COMMENT 'The named public register staff check against.',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Switch off instead of deleting; inactive rows are hidden from the public site and pickers.',
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who created the record. Set by the HasUserAttribution trait from the logged-in user, never from request input. NULL = self sign-up, scheduled job, seeder or system process.',
  `updated_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who last edited the record (also set on soft delete). Same rules as created_by.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Record creation time, stored in UTC, shown in IST.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last modification time, stored in UTC.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `issuing_authorities_slug_unique` (`slug`),
  CONSTRAINT `issuing_authorities_state_id_foreign` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `issuing_authorities_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `issuing_authorities_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bodies whose registers credentials are checked against (COA, CMDA, GCC, TN RERA …).';

-- 30. credential_types — Kinds of credential. Aadhaar is never a type, so it cannot be selected or uploaded.
CREATE TABLE `credential_types` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key, AUTO_INCREMENT',
  `issuing_authority_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Default issuing authority.',
  `name_en` VARCHAR(200) NOT NULL COMMENT 'English name.',
  `name_ta` VARCHAR(200) NULL DEFAULT NULL COMMENT 'Tamil name. English is shown (marked lang="en") when NULL.',
  `slug` VARCHAR(220) NOT NULL COMMENT 'URL slug in English letters, lowercase kebab-case, unique globally.',
  `description_en` TEXT NULL DEFAULT NULL COMMENT 'English description.',
  `description_ta` TEXT NULL DEFAULT NULL COMMENT 'Tamil description.',
  `has_expiry` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Whether expiry_date is required; drives the expiry jobs.',
  `requires_document` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Whether a document upload is mandatory.',
  `number_pattern` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Regex to validate the credential number.',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Switch off instead of deleting; inactive rows are hidden from the public site and pickers.',
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Display order (ascending).',
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who created the record. Set by the HasUserAttribution trait from the logged-in user, never from request input. NULL = self sign-up, scheduled job, seeder or system process.',
  `updated_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who last edited the record (also set on soft delete). Same rules as created_by.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Record creation time, stored in UTC, shown in IST.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last modification time, stored in UTC.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `credential_types_slug_unique` (`slug`),
  CONSTRAINT `credential_types_issuing_authority_id_foreign` FOREIGN KEY (`issuing_authority_id`) REFERENCES `issuing_authorities` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `credential_types_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `credential_types_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Kinds of credential. Aadhaar is never a type, so it cannot be selected or uploaded.';

-- 31. verification_requirements — Seeded lookup: category → credential type → authority. Defines what PublishGate requires.
CREATE TABLE `verification_requirements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key, AUTO_INCREMENT',
  `category_id` BIGINT UNSIGNED NOT NULL COMMENT 'Category the rule applies to.',
  `credential_type_id` BIGINT UNSIGNED NOT NULL COMMENT 'Credential needed.',
  `issuing_authority_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Authority it must come from.',
  `is_required` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = blocks publishing until verified; 0 = optional.',
  `notes` VARCHAR(500) NULL DEFAULT NULL COMMENT 'Guidance for verification staff.',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Switch off instead of deleting; inactive rows are hidden from the public site and pickers.',
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who created the record. Set by the HasUserAttribution trait from the logged-in user, never from request input. NULL = self sign-up, scheduled job, seeder or system process.',
  `updated_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who last edited the record (also set on soft delete). Same rules as created_by.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Record creation time, stored in UTC, shown in IST.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last modification time, stored in UTC.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `verification_requirements_category_id_credential_type_id_unique` (`category_id`, `credential_type_id`),
  CONSTRAINT `verification_requirements_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `verification_requirements_credential_type_id_foreign` FOREIGN KEY (`credential_type_id`) REFERENCES `credential_types` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `verification_requirements_issuing_authority_id_foreign` FOREIGN KEY (`issuing_authority_id`) REFERENCES `issuing_authorities` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `verification_requirements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `verification_requirements_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Seeded lookup: category → credential type → authority. Defines what PublishGate requires.';

-- 32. credentials — One row per credential a provider holds — never a single verified flag. The document is a private media row.
CREATE TABLE `credentials` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key, AUTO_INCREMENT',
  `provider_id` BIGINT UNSIGNED NOT NULL COMMENT 'Provider.',
  `credential_type_id` BIGINT UNSIGNED NOT NULL COMMENT 'Credential type.',
  `issuing_authority_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Issuing authority shown on the badge.',
  `credential_number` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Licence or registration number.',
  `holder_name` VARCHAR(200) NULL DEFAULT NULL COMMENT 'Name on the credential.',
  `issue_date` DATE NULL DEFAULT NULL COMMENT 'Issue date.',
  `expiry_date` DATE NULL DEFAULT NULL COMMENT 'Expiry date (DATE, safe past 2038).',
  `status` VARCHAR(20) NOT NULL DEFAULT 'submitted' COMMENT 'submitted, verified, rejected, expired.',
  `verified_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last successful check; badge needs it on every required credential.',
  `verified_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Verification staff member who checked it.',
  `rejection_reason` VARCHAR(500) NULL DEFAULT NULL COMMENT 'Shown to the provider when rejected.',
  `expiry_flagged_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Set by FlagExpiringCredentials 60 days before expiry_date.',
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who created the record. Set by the HasUserAttribution trait from the logged-in user, never from request input. NULL = self sign-up, scheduled job, seeder or system process.',
  `updated_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who last edited the record (also set on soft delete). Same rules as created_by.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Record creation time, stored in UTC, shown in IST.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last modification time, stored in UTC.',
  PRIMARY KEY (`id`),
  KEY `credentials_expiry_date_status_index` (`expiry_date`, `status`),
  KEY `credentials_provider_id_credential_type_id_index` (`provider_id`, `credential_type_id`),
  CONSTRAINT `credentials_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `credentials_credential_type_id_foreign` FOREIGN KEY (`credential_type_id`) REFERENCES `credential_types` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `credentials_issuing_authority_id_foreign` FOREIGN KEY (`issuing_authority_id`) REFERENCES `issuing_authorities` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `credentials_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `credentials_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `credentials_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='One row per credential a provider holds — never a single verified flag. The document is a private media row.';

-- 33. verification_events — Append-only history: every lifecycle transition and every credential check, re-check and expiry.
CREATE TABLE `verification_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key, AUTO_INCREMENT',
  `provider_id` BIGINT UNSIGNED NOT NULL COMMENT 'Provider.',
  `credential_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Credential checked (credential events).',
  `event_type` VARCHAR(40) NOT NULL COMMENT 'status_transition, credential_check, credential_expired, consent_recorded, phone_verified, account_claimed, note.',
  `from_status` VARCHAR(20) NULL DEFAULT NULL COMMENT 'Provider status before (transitions).',
  `to_status` VARCHAR(20) NULL DEFAULT NULL COMMENT 'Provider status after (transitions).',
  `check_method` VARCHAR(30) NULL DEFAULT NULL COMMENT 'How a credential was checked: online_register, document_review, authority_call (PHP enum).',
  `result` VARCHAR(20) NULL DEFAULT NULL COMMENT 'pass or fail (credential checks).',
  `reason` TEXT NULL DEFAULT NULL COMMENT 'Reason recorded (required on several transitions).',
  `metadata` JSON NULL DEFAULT NULL COMMENT 'PublishGate failure list and other details.',
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Who acted. NULL = scheduler (e.g. LapseExpiredCredentials).',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Event time.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Present because the Architecture puts timestamps on every table; never changes after insert.',
  PRIMARY KEY (`id`),
  KEY `verification_events_provider_id_created_at_index` (`provider_id`, `created_at`),
  KEY `verification_events_credential_id_created_at_index` (`credential_id`, `created_at`),
  CONSTRAINT `verification_events_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `verification_events_credential_id_foreign` FOREIGN KEY (`credential_id`) REFERENCES `credentials` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `verification_events_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Append-only history: every lifecycle transition and every credential check, re-check and expiry.';

-- 34. consents — Provider consent: date, method, who recorded it, and what may be published. Required for Found → Consented.
CREATE TABLE `consents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key, AUTO_INCREMENT',
  `provider_id` BIGINT UNSIGNED NOT NULL COMMENT 'Provider.',
  `consented_at` TIMESTAMP NOT NULL COMMENT 'Date and time consent was given.',
  `method` VARCHAR(30) NOT NULL COMMENT 'phone_call, in_person, online_join (PHP enum ConsentMethod).',
  `scope` JSON NOT NULL COMMENT 'What may be published, e.g. {"call":true,"whatsapp":true,"photos":true,"address":false}.',
  `policy_version` VARCHAR(20) NULL DEFAULT NULL COMMENT 'Version of the terms/privacy text agreed to.',
  `given_by_name` VARCHAR(150) NULL DEFAULT NULL COMMENT 'Person who gave consent for the business.',
  `withdrawn_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'When consent was withdrawn.',
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who created the record. Set by the HasUserAttribution trait from the logged-in user, never from request input. NULL = self sign-up, scheduled job, seeder or system process.',
  `updated_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who last edited the record (also set on soft delete). Same rules as created_by.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Record creation time, stored in UTC, shown in IST.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last modification time, stored in UTC.',
  PRIMARY KEY (`id`),
  KEY `consents_provider_id_consented_at_index` (`provider_id`, `consented_at`),
  CONSTRAINT `consents_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `consents_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `consents_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Provider consent: date, method, who recorded it, and what may be published. Required for Found → Consented.';

-- ---------------------------------------------------------------------
-- ANALYTICS
-- ---------------------------------------------------------------------

-- 35. search_logs — One row per search, for the no-result KPI and missing-supply report. Holds no personal data.
CREATE TABLE `search_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key, AUTO_INCREMENT',
  `query` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Free text as typed, with digit runs of 8+ removed so a typed phone number is never stored.',
  `category_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Category searched.',
  `city_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'City searched.',
  `locality_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Locality searched (after pincode resolution).',
  `result_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Results returned; 0 = no-result search.',
  `locale` VARCHAR(5) NOT NULL DEFAULT 'en' COMMENT 'Site language.',
  `used_fallback` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 if the LIKE fallback ran instead of FULLTEXT.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Search time.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Present because the Architecture puts timestamps on every table; never changes.',
  PRIMARY KEY (`id`),
  KEY `search_logs_created_at_index` (`created_at`),
  KEY `search_logs_result_count_created_at_index` (`result_count`, `created_at`),
  KEY `search_logs_category_id_city_id_created_at_index` (`category_id`, `city_id`, `created_at`),
  CONSTRAINT `search_logs_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `search_logs_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `search_logs_locality_id_foreign` FOREIGN KEY (`locality_id`) REFERENCES `localities` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='One row per search, for the no-result KPI and missing-supply report. Holds no personal data.';

-- ---------------------------------------------------------------------
-- ENQUIRIES
-- ---------------------------------------------------------------------

-- 36. enquiries — Fallback enquiries ('Can't find what you need?') from no-result searches and Coming soon categories.
CREATE TABLE `enquiries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key, AUTO_INCREMENT',
  `reference_no` VARCHAR(20) NOT NULL COMMENT 'Human-friendly reference, e.g. KTF-000123. Unique.',
  `user_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Customer (phone is OTP-verified, so a user always exists).',
  `name` VARCHAR(150) NOT NULL COMMENT 'Customer name.',
  `phone` VARCHAR(16) NOT NULL COMMENT 'OTP-verified phone (E.164). Staff only.',
  `category_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Category needed.',
  `city_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'City.',
  `locality_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Locality.',
  `message` TEXT NOT NULL COMMENT 'What the customer needs.',
  `budget` VARCHAR(50) NULL DEFAULT NULL COMMENT 'Optional budget as entered or chosen (e.g. a range).',
  `preferred_date` DATE NULL DEFAULT NULL COMMENT 'Optional preferred start date.',
  `locale` VARCHAR(5) NOT NULL DEFAULT 'en' COMMENT 'Language to reply in.',
  `source` VARCHAR(20) NOT NULL DEFAULT 'no_results' COMMENT 'no_results or coming_soon.',
  `search_log_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'The search that led here (missing-supply signal).',
  `status` VARCHAR(20) NOT NULL DEFAULT 'new' COMMENT 'new, assigned, matched, closed, spam.',
  `assigned_to` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Staff member handling it.',
  `assigned_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'When assigned.',
  `matched_provider_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Provider matched by hand.',
  `outcome` VARCHAR(30) NULL DEFAULT NULL COMMENT 'Recorded outcome: hired, still_looking, no_response, not_interested.',
  `closed_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'When closed.',
  `anonymized_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'When personal fields were erased after retention.',
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who created the record. Set by the HasUserAttribution trait from the logged-in user, never from request input. NULL = self sign-up, scheduled job, seeder or system process.',
  `updated_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who last edited the record (also set on soft delete). Same rules as created_by.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Record creation time, stored in UTC, shown in IST.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last modification time, stored in UTC.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `enquiries_reference_no_unique` (`reference_no`),
  KEY `enquiries_status_created_at_index` (`status`, `created_at`),
  KEY `enquiries_assigned_to_status_index` (`assigned_to`, `status`),
  CONSTRAINT `enquiries_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `enquiries_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `enquiries_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `enquiries_locality_id_foreign` FOREIGN KEY (`locality_id`) REFERENCES `localities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `enquiries_search_log_id_foreign` FOREIGN KEY (`search_log_id`) REFERENCES `search_logs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `enquiries_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `enquiries_matched_provider_id_foreign` FOREIGN KEY (`matched_provider_id`) REFERENCES `providers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `enquiries_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `enquiries_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Fallback enquiries (''Can''t find what you need?'') from no-result searches and Coming soon categories.';

-- 37. contact_events — Every contact action (user, provider, action, locale, time). Feeds every KPI and the reveal limit.
CREATE TABLE `contact_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key, AUTO_INCREMENT',
  `provider_id` BIGINT UNSIGNED NOT NULL COMMENT 'Provider contacted.',
  `user_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Customer who acted. NULL only for profile_view by anonymous visitors (C-10).',
  `action` VARCHAR(20) NOT NULL COMMENT 'call, whatsapp, enquiry; profile_view proposed (C-10).',
  `locale` VARCHAR(5) NOT NULL DEFAULT 'en' COMMENT 'Site language at the time.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Event time.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Present because the Architecture puts timestamps on every table; never changes.',
  PRIMARY KEY (`id`),
  KEY `contact_events_provider_id_created_at_index` (`provider_id`, `created_at`),
  KEY `contact_events_user_id_created_at_index` (`user_id`, `created_at`),
  CONSTRAINT `contact_events_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `contact_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Every contact action (user, provider, action, locale, time). Feeds every KPI and the reveal limit.';

-- 38. customer_followups — Staff follow-up calls to customers after an enquiry or contact.
CREATE TABLE `customer_followups` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key, AUTO_INCREMENT',
  `enquiry_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Related enquiry. One of enquiry_id / contact_event_id is required (Form Request).',
  `contact_event_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Related contact event.',
  `user_id` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Customer being called.',
  `assigned_to` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Staff member making the call.',
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, done, cancelled.',
  `scheduled_for` TIMESTAMP NULL DEFAULT NULL COMMENT 'When to call.',
  `completed_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'When done.',
  `outcome` VARCHAR(30) NULL DEFAULT NULL COMMENT 'hired, still_looking, no_response, not_interested, complaint.',
  `notes` TEXT NULL DEFAULT NULL COMMENT 'Call notes.',
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who created the record. Set by the HasUserAttribution trait from the logged-in user, never from request input. NULL = self sign-up, scheduled job, seeder or system process.',
  `updated_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who last edited the record (also set on soft delete). Same rules as created_by.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Record creation time, stored in UTC, shown in IST.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last modification time, stored in UTC.',
  PRIMARY KEY (`id`),
  KEY `customer_followups_assigned_to_status_scheduled_for_index` (`assigned_to`, `status`, `scheduled_for`),
  CONSTRAINT `customer_followups_enquiry_id_foreign` FOREIGN KEY (`enquiry_id`) REFERENCES `enquiries` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `customer_followups_contact_event_id_foreign` FOREIGN KEY (`contact_event_id`) REFERENCES `contact_events` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `customer_followups_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customer_followups_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customer_followups_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customer_followups_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Staff follow-up calls to customers after an enquiry or contact.';

-- ---------------------------------------------------------------------
-- CONTENT
-- ---------------------------------------------------------------------

-- 39. content_pages — Content pages, guides (/guides/{slug}/), FAQs and legal pages (/privacy/, /terms/, /disclaimer/).
CREATE TABLE `content_pages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key, AUTO_INCREMENT',
  `slug` VARCHAR(150) NOT NULL COMMENT 'URL slug in English letters, kebab-case, unique.',
  `page_type` VARCHAR(20) NOT NULL COMMENT 'page, guide, faq, legal.',
  `title_en` VARCHAR(200) NOT NULL COMMENT 'English title.',
  `title_ta` VARCHAR(200) NULL DEFAULT NULL COMMENT 'Tamil title. English is shown (marked lang="en") when NULL.',
  `body_en` MEDIUMTEXT NULL DEFAULT NULL COMMENT 'English body (sanitized; no raw user HTML).',
  `body_ta` MEDIUMTEXT NULL DEFAULT NULL COMMENT 'Tamil body.',
  `meta_description_en` VARCHAR(170) NULL DEFAULT NULL COMMENT 'SEO meta description (English).',
  `meta_description_ta` VARCHAR(170) NULL DEFAULT NULL COMMENT 'SEO meta description (Tamil).',
  `ta_status` VARCHAR(20) NOT NULL DEFAULT 'empty' COMMENT 'empty, machine_draft, reviewed. /ta/ shows Tamil only when reviewed.',
  `ta_reviewed_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Content staff member who marked it reviewed.',
  `ta_reviewed_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'When marked reviewed.',
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft' COMMENT 'draft, published.',
  `published_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Publish time.',
  `created_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who created the record. Set by the HasUserAttribution trait from the logged-in user, never from request input. NULL = self sign-up, scheduled job, seeder or system process.',
  `updated_by` BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'User who last edited the record (also set on soft delete). Same rules as created_by.',
  `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Record creation time, stored in UTC, shown in IST.',
  `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Last modification time, stored in UTC.',
  `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Soft-delete marker. Soft-deleted rows are hidden from every public query.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `content_pages_slug_unique` (`slug`),
  KEY `content_pages_page_type_status_index` (`page_type`, `status`),
  CONSTRAINT `content_pages_ta_reviewed_by_foreign` FOREIGN KEY (`ta_reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `content_pages_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `content_pages_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Content pages, guides (/guides/{slug}/), FAQs and legal pages (/privacy/, /terms/, /disclaimer/).';

-- End of script: 39 tables.
