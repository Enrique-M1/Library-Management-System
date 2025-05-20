CREATE DATABASE IF NOT EXISTS LibraryDB;

USE LibraryDB;

-- 1. Users Table
CREATE TABLE users (
   user_id INT AUTO_INCREMENT PRIMARY KEY,
   username VARCHAR(50) NOT NULL,
   password VARCHAR(255) NOT NULL,  -- Store hashed passwords
   email VARCHAR(100) NOT NULL,
   phone VARCHAR(20),
   role ENUM('User', 'Faculty', 'Admin', 'Student') DEFAULT 'User'
);

-- 2. Books Table
CREATE TABLE books (
   book_id INT AUTO_INCREMENT PRIMARY KEY,
   title VARCHAR(255) NOT NULL,
   author VARCHAR(255) NOT NULL,
   ISBN VARCHAR(20) NOT NULL,
   category VARCHAR(100),
   image_url VARCHAR(255),
   total_copies INT DEFAULT 1,
   available_copies INT DEFAULT 1
);

-- 3. Reservations Table
CREATE TABLE reservations (
   reservation_id INT AUTO_INCREMENT PRIMARY KEY,
   user_id INT NOT NULL,
   book_id INT NOT NULL,
   reservation_date DATETIME DEFAULT CURRENT_TIMESTAMP,
   status ENUM('Avaliable', 'Approved', 'Reserved', 'Denied', 'Checked-out', 'Notified') DEFAULT 'Reserved',
   FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
   FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE
);

-- 4. checkouts Table (for tracking book check-outs/returns)
CREATE TABLE checkouts (
   checkout_id INT AUTO_INCREMENT PRIMARY KEY,
   user_id INT NOT NULL,
   book_id INT NOT NULL,
   borrowed_date DATE NOT NULL,
   due_date DATE NOT NULL,
   returned_date DATE,  -- Will be NULL until the book is returned
   FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
   FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE
);


-- 5. Reviews Table
CREATE TABLE reviews (
   review_id INT AUTO_INCREMENT PRIMARY KEY,
   user_id INT NOT NULL,
   book_id INT NOT NULL,
   rating INT CHECK (rating >= 1 AND rating <= 5),
   review_text TEXT,
   review_date DATETIME DEFAULT CURRENT_TIMESTAMP,
   FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
   FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE
);

-- 6. Notifications Table
CREATE TABLE notifications (
   notification_id INT AUTO_INCREMENT PRIMARY KEY,
   user_id INT NOT NULL,
   book_id INT NOT NULL,
   message TEXT NOT NULL,
   notification_date DATETIME DEFAULT CURRENT_TIMESTAMP,
   FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE FAVORITE (
   favorite_id INT AUTO_INCREMENT PRIMARY KEY,
   user_id INT NOT NULL,
   book_id INT NOT NULL,
   favorite_date DATETIME DEFAULT CURRENT_TIMESTAMP,
   FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
   FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE

)
