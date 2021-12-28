-- phpMyAdmin SQL Dump
-- version 4.9.5deb2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 27, 2021 at 07:56 PM
-- Server version: 10.3.32-MariaDB-0ubuntu0.20.04.1
-- PHP Version: 7.4.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `example.cmless`
--

-- --------------------------------------------------------

--
-- Table structure for table `cmless_template_cache`
--

CREATE TABLE `cmless_template_cache` (
  `file` text DEFAULT NULL,
  `template_id` varchar(255) DEFAULT NULL,
  `datetime` datetime NOT NULL,
  `lifetime` int(11) NOT NULL,
  `html` text NOT NULL,
  `vars` varchar(32) DEFAULT NULL,
  `params` varchar(32) DEFAULT NULL,
  `url` text DEFAULT NULL,
  `user` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `cmless_user`
--

CREATE TABLE `cmless_user` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `admin` tinyint(1) NOT NULL DEFAULT 0,
  `banned` tinyint(1) NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `created` datetime NOT NULL,
  `last_login` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `cmless_user`
--

INSERT INTO `cmless_user` (`id`, `email`, `password`, `name`, `admin`, `banned`, `image`, `created`, `last_login`) VALUES
(1, 'admin@admin.com', '$2y$10$VMAPRaWNKS49G703OMaSYOsdWAxDoS6msVPaSZ1VhOjaf3bJdGa5O', 'D00M', 1, 0, NULL, '2021-12-22 20:35:54', '2021-12-22 20:35:54');

-- --------------------------------------------------------

--
-- Table structure for table `news_newsarticle`
--

CREATE TABLE `news_newsarticle` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `short_desc` text NOT NULL,
  `content` text NOT NULL,
  `image` text DEFAULT NULL,
  `datetime` datetime NOT NULL,
  `author` int(11) NOT NULL,
  `category` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `news_newsarticle`
--

INSERT INTO `news_newsarticle` (`id`, `title`, `slug`, `short_desc`, `content`, `image`, `datetime`, `author`, `category`) VALUES
(1, 'It\'s just a test', 'its_just_a_test', 'Don\'t take it seriously...', '<p>\r\nLorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris vitae dapibus magna. Sed condimentum quam quis justo malesuada tempor. Etiam risus tortor, varius non ligula in, egestas eleifend augue. Sed nibh eros, egestas id laoreet in, laoreet ut urna. In et porta leo, non pellentesque libero. Praesent scelerisque ante ex, sed venenatis ipsum ultrices vitae. Aenean mauris ex, semper ac eleifend eu, luctus in augue. Maecenas commodo turpis at mi vehicula lobortis. Curabitur ultrices sem nisl. Sed hendrerit dui vel magna fermentum, vitae consequat magna congue. Sed sagittis faucibus lorem, eu ultricies ipsum pharetra vel. Aenean velit orci, tristique sed scelerisque id, aliquet ut odio. Morbi aliquam vitae nisl at pulvinar. Pellentesque vulputate id augue cursus tincidunt.\r\n</p>\r\n<p>\r\nDonec ac fermentum ex, quis feugiat quam. Fusce sit amet ultricies diam. Nullam sapien mi, elementum non tempus id, posuere vitae massa. Suspendisse eget nibh eget est tincidunt iaculis. In vel sapien nec sapien maximus varius id nec arcu. Ut commodo mattis arcu vitae elementum. In orci nisi, accumsan in diam a, mattis congue mi. Curabitur pretium placerat molestie. Praesent efficitur scelerisque faucibus. Nunc sed felis tristique, accumsan lorem a, aliquam tortor. Aenean a elementum diam, in facilisis metus. Sed porta ante in purus fermentum aliquam. Mauris a sagittis purus.\r\n</p>', 'news/articles/image.jpg', '2014-12-10 00:00:00', 1, 1),
(2, 'Another test baby', 'another_test_baby', 'Yes baby! Just for you!', '<p>\r\nAliquam erat volutpat. Sed et augue quis odio suscipit condimentum interdum vel felis. Donec iaculis euismod augue id imperdiet. Ut hendrerit vehicula aliquam. Cras neque nibh, eleifend sed nulla id, pellentesque iaculis leo. Fusce id fringilla libero, sit amet luctus felis. Vestibulum in auctor justo. Quisque eu aliquet sem, nec feugiat urna. Pellentesque rhoncus, quam vitae pharetra iaculis, ligula neque feugiat tellus, a egestas felis libero et turpis. Curabitur eget finibus ligula. Nullam vitae eleifend libero.\r\n</p>\r\n<p>\r\nDonec varius, tortor et vulputate ultrices, est nisl tempus massa, id consequat dolor tellus ac mauris. Integer efficitur massa ut libero mollis cursus. Duis eget facilisis orci, in lobortis urna. Sed lorem lorem, vulputate finibus sem imperdiet, ornare egestas erat. Donec quis mattis est. Nunc mollis ut felis a finibus. Nam non mauris luctus, feugiat mi vitae, vehicula neque. Nulla facilisi. Quisque ornare aliquam magna ut luctus.\r\n</p>', 'news/articles/image.jpg', '2014-12-12 00:00:00', 1, 2),
(3, 'Never one without three', 'never_one_without_three', 'It\'s still me... doing some test... alone in my room... listening to Disco-House... so sad.', '<p>\r\nDonec varius, tortor et vulputate ultrices, est nisl tempus massa, id consequat dolor tellus ac mauris. Integer efficitur massa ut libero mollis cursus. Duis eget facilisis orci, in lobortis urna. Sed lorem lorem, vulputate finibus sem imperdiet, ornare egestas erat. Donec quis mattis est. Nunc mollis ut felis a finibus. Nam non mauris luctus, feugiat mi vitae, vehicula neque. Nulla facilisi. Quisque ornare aliquam magna ut luctus.\r\n</p>\r\n<p>\r\nMauris lorem tortor, efficitur sed vehicula sed, hendrerit sed ligula. Fusce nec massa a arcu interdum bibendum. Etiam in sem odio. Morbi tincidunt vulputate luctus. Phasellus felis libero, fermentum sit amet sagittis sed, eleifend vel arcu. Nulla in libero vitae nulla accumsan volutpat. Sed turpis massa, ornare eget eros volutpat, lobortis efficitur felis. Suspendisse a lorem volutpat, viverra est ut, finibus nisl.\r\n</p>', 'news/articles/image.jpg', '2014-12-21 00:00:00', 1, 3);

-- --------------------------------------------------------

--
-- Table structure for table `news_newsauthor`
--

CREATE TABLE `news_newsauthor` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `image` text NOT NULL,
  `datetime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `news_newsauthor`
--

INSERT INTO `news_newsauthor` (`id`, `name`, `email`, `image`, `datetime`) VALUES
(1, 'Dominic Roberge', 'dominic.roberge.11@gmail.com', 'news/authors/unknown.jpg', '2014-12-03 13:10:12');

-- --------------------------------------------------------

--
-- Table structure for table `news_newscategory`
--

CREATE TABLE `news_newscategory` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `news_newscategory`
--

INSERT INTO `news_newscategory` (`id`, `name`, `slug`) VALUES
(1, 'Health and fitness', 'health_n_fitness'),
(2, 'Cats and dogs', 'cats_n_dogs'),
(3, 'Party time!', 'party_time');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cmless_user`
--
ALTER TABLE `cmless_user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`) USING BTREE;

--
-- Indexes for table `news_newsarticle`
--
ALTER TABLE `news_newsarticle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `author` (`author`),
  ADD KEY `category` (`category`);

--
-- Indexes for table `news_newsauthor`
--
ALTER TABLE `news_newsauthor`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `news_newscategory`
--
ALTER TABLE `news_newscategory`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cmless_user`
--
ALTER TABLE `cmless_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `news_newsarticle`
--
ALTER TABLE `news_newsarticle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `news_newsauthor`
--
ALTER TABLE `news_newsauthor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `news_newscategory`
--
ALTER TABLE `news_newscategory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `news_newsarticle`
--
ALTER TABLE `news_newsarticle`
  ADD CONSTRAINT `newsarticle_author` FOREIGN KEY (`author`) REFERENCES `news_newsauthor` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `newsarticle_category` FOREIGN KEY (`category`) REFERENCES `news_newscategory` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
