-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 14, 2025 at 04:27 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bookstore_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `book_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `author` varchar(150) NOT NULL,
  `book_number` varchar(20) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `category_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` blob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`book_id`, `title`, `author`, `book_number`, `price`, `stock`, `category_id`, `description`, `image`) VALUES
(26, 'The Adventures of Tom Sawyer', 'Mark Twain', NULL, 100.00, 0, 1, 'Read at your own risk', 0x546f6d205361777965722e61766966),
(27, 'Treasure Island', 'Robert Louis Stevenson', NULL, 50.00, 0, 2, 'There is Gold', 0x54726561737572652049736c616e642e61766966),
(29, 'Hide and Don\'t Seek', 'Anica Mrose Rissi', NULL, 500.00, 8, 4, 'Don\'t Read This', 0x68696465616e64646f6e747365656b2e6a7067),
(30, 'Amityville-Horror', 'Jay Anson', NULL, 150.00, 8, 4, 'Spookyyyyyyy', 0x416d69747976696c6c652d486f72726f722e6a7067),
(32, 'IT', 'Stephen King', NULL, 200.00, 20, 4, 'Freaky ahh book', 0x69742d393738313938323132373739345f68722e6a7067),
(33, 'The Exorcist', 'William Peter Blatty', NULL, 100.00, 0, 4, 'Bloody', 0x7375622d62757a7a2d313637322d313639373538313031392d312e6a7067);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`book_id`),
  ADD UNIQUE KEY `isbn` (`book_number`),
  ADD KEY `category_id` (`category_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `books`
--
ALTER TABLE `books`
  ADD CONSTRAINT `books_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
