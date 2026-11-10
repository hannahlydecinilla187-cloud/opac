-- ============================================================
-- OPAC — Online Public Access Catalog
-- MySQL / MariaDB schema + seed data
--
-- Import via phpMyAdmin, or from a terminal:
--   mysql -u root < database.sql
--
-- Default admin account:
--   username: admin
--   password: admin123   (change it after first login — see README)
-- ============================================================

CREATE DATABASE IF NOT EXISTS `opac`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `opac`;

-- ------------------------------------------------------------
-- Admin users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(50)  NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admins_username` (`username`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Books
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `books` (
  `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `title`            VARCHAR(255)    NOT NULL,
  `author`           VARCHAR(255)    NOT NULL,
  `isbn`            VARCHAR(20)     DEFAULT NULL,
  `publisher`       VARCHAR(255)    DEFAULT NULL,
  `category`        VARCHAR(100)    DEFAULT NULL,
  `year`            SMALLINT UNSIGNED DEFAULT NULL,
  `copies`          INT UNSIGNED    NOT NULL DEFAULT 1,
  `available_copies` INT UNSIGNED   NOT NULL DEFAULT 1,
  `description`     TEXT            DEFAULT NULL,
  `cover_url`       VARCHAR(500)    DEFAULT NULL,
  `created_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_books_author`   (`author`),
  KEY `idx_books_category` (`category`),
  KEY `idx_books_title`    (`title`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Seed: admin user (admin / admin123)
-- ------------------------------------------------------------
INSERT INTO `admins` (`username`, `password_hash`) VALUES
  ('admin', '$2y$10$Ha29Nl2a5TB7dBkbPuVPD.w7BhgeF9iN4FS3WTzZ1UVcy8cf9O02u');

-- ------------------------------------------------------------
-- Seed: sample catalog
-- ------------------------------------------------------------
INSERT INTO `books`
  (`title`, `author`, `isbn`, `publisher`, `category`, `year`, `copies`, `available_copies`, `description`, `cover_url`) VALUES
  ('Pride and Prejudice', 'Jane Austen', '9780141439518', 'Penguin Classics', 'Romance', 1813, 4, 4,
   'Austen''s beloved comedy of manners follows the spirited Elizabeth Bennet and the proud Mr. Darcy through misunderstandings, wit, and romance in Regency England.',
   'https://covers.openlibrary.org/b/isbn/9780141439518-M.jpg'),

  ('1984', 'George Orwell', '9780451524935', 'Signet Classics', 'Dystopian Fiction', 1949, 3, 2,
   'Winston Smith struggles to preserve his individuality under the all-seeing eye of Big Brother in Orwell''s chilling portrait of totalitarianism.',
   'https://covers.openlibrary.org/b/isbn/9780451524935-M.jpg'),

  ('To Kill a Mockingbird', 'Harper Lee', '9780061120084', 'Harper Perennial', 'Fiction', 1960, 3, 1,
   'In a small Alabama town in the 1930s, Scout Finch watches her father Atticus defend a Black man falsely accused of a terrible crime.',
   'https://covers.openlibrary.org/b/isbn/9780061120084-M.jpg'),

  ('The Great Gatsby', 'F. Scott Fitzgerald', '9780743273565', 'Scribner', 'Fiction', 1925, 2, 2,
   'Jay Gatsby''s lavish parties and doomed pursuit of Daisy Buchanan capture the glitter and hollow heart of the Jazz Age.',
   'https://covers.openlibrary.org/b/isbn/9780743273565-M.jpg'),

  ('The Hobbit', 'J. R. R. Tolkien', '9780547928227', 'Houghton Mifflin Harcourt', 'Fantasy', 1937, 5, 5,
   'Bilbo Baggins is swept from his cozy hobbit-hole into an epic quest to reclaim the dwarves'' mountain home from the dragon Smaug.',
   'https://covers.openlibrary.org/b/isbn/9780547928227-M.jpg'),

  ('Moby-Dick', 'Herman Melville', '9780142437086', 'Penguin Classics', 'Classic Fiction', 1851, 2, 0,
   'Ishmael signs aboard the whaler Pequod, whose captain, Ahab, is consumed by his vendetta against the great white whale.',
   NULL),

  ('Frankenstein', 'Mary Shelley', '9780141439471', 'Penguin Classics', 'Gothic Fiction', 1818, 2, 2,
   'Victor Frankenstein''s experiment to create life unleashes a creature whose need for compassion turns to revenge.',
   'https://covers.openlibrary.org/b/isbn/9780141439471-M.jpg'),

  ('A Brief History of Time', 'Stephen Hawking', '9780553380163', 'Bantam Books', 'Science', 1988, 3, 3,
   'Hawking explores space and time, black holes, the Big Bang, and the search for a unified theory of the universe.',
   'https://covers.openlibrary.org/b/isbn/9780553380163-M.jpg'),

  ('Sapiens: A Brief History of Humankind', 'Yuval Noah Harari', '9780062316097', 'Harper', 'History', 2011, 4, 4,
   'A sweeping narrative of how an insignificant ape came to dominate Earth, from cognitive revolution to the age of biotechnology.',
   'https://covers.openlibrary.org/b/isbn/9780062316097-M.jpg'),

  ('Clean Code', 'Robert C. Martin', '9780132350884', 'Prentice Hall', 'Computers', 2008, 2, 2,
   'A handbook of agile software craftsmanship: meaningful names, small functions, tests, and the discipline of writing code people can read.',
   'https://covers.openlibrary.org/b/isbn/9780132350884-M.jpg'),

  ('The Pragmatic Programmer', 'Andrew Hunt & David Thomas', '9780201616224', 'Addison-Wesley', 'Computers', 1999, 2, 1,
   'The classic guide to becoming a better developer — from pragmatic philosophy to daily habits like DRY, automation, and orthogonality.',
   'https://covers.openlibrary.org/b/isbn/9780201616224-M.jpg'),

  ('Introduction to Algorithms', 'Thomas H. Cormen et al.', '9780262033848', 'MIT Press', 'Computers', 2009, 1, 1,
   'The definitive algorithms textbook: sorting, data structures, graph theory, dynamic programming, and complexity analysis.',
   'https://covers.openlibrary.org/b/isbn/9780262033848-M.jpg');
