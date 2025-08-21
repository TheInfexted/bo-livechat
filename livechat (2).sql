-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 21, 2025 at 09:56 AM
-- Server version: 10.11.10-MariaDB-log
-- PHP Version: 8.3.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `livechat`
--

-- --------------------------------------------------------

--
-- Table structure for table `api_keys`
--

CREATE TABLE `api_keys` (
  `id` int(11) NOT NULL,
  `key_id` varchar(50) NOT NULL,
  `api_key` varchar(64) NOT NULL,
  `client_name` varchar(255) NOT NULL,
  `client_email` varchar(255) NOT NULL,
  `client_domain` varchar(255) DEFAULT NULL,
  `status` enum('active','suspended','revoked') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_used_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `api_keys`
--

INSERT INTO `api_keys` (`id`, `key_id`, `api_key`, `client_name`, `client_email`, `client_domain`, `status`, `created_at`, `updated_at`, `last_used_at`) VALUES
(1, 'key_3af629c2e38bf231e7b5f14f12441f17', 'lc_98b277c9f9c4f2f5b9f0dcb46748ab1a3f6360c6b6490edc', 'Fortune Wheel', 'fortunewheel@example.com', '', 'active', '2025-08-08 09:25:47', '2025-08-20 14:18:58', '2025-08-20 14:18:58');

-- --------------------------------------------------------

--
-- Table structure for table `canned_responses`
--

CREATE TABLE `canned_responses` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `category` varchar(50) DEFAULT 'general',
  `agent_id` int(11) DEFAULT NULL,
  `is_global` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `canned_responses`
--

INSERT INTO `canned_responses` (`id`, `title`, `content`, `category`, `agent_id`, `is_global`, `created_at`) VALUES
(1, 'Welcome Message', 'Hello! Welcome to our support chat. How can I help you today?', 'greeting', NULL, 1, '2025-08-01 11:11:11'),
(2, 'Please Wait', 'Thank you for your patience. Let me look into this for you.', 'general', NULL, 1, '2025-08-01 11:11:11'),
(3, 'Session End', 'Thank you for contacting us today. Have a great day!', 'closing', NULL, 1, '2025-08-01 11:11:11'),
(4, 'Technical Issue', 'I understand you\'re experiencing technical difficulties. Let me help you resolve this.', 'technical', NULL, 1, '2025-08-01 11:11:11'),
(5, 'Provide Information', 'Please provide us your name and e-mail so we can get back to you at a later date', 'general', NULL, 1, '2025-08-01 14:17:12');

-- --------------------------------------------------------

--
-- Table structure for table `chat_sessions`
--

CREATE TABLE `chat_sessions` (
  `id` int(11) NOT NULL,
  `session_id` varchar(64) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_fullname` varchar(100) DEFAULT NULL,
  `chat_topic` text DEFAULT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `user_role` varchar(50) DEFAULT 'anonymous',
  `external_username` varchar(100) DEFAULT NULL,
  `external_fullname` varchar(100) DEFAULT NULL,
  `external_system_id` varchar(100) DEFAULT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `status` enum('waiting','active','closed') DEFAULT 'waiting',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `closed_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `customer_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `chat_sessions`
--

INSERT INTO `chat_sessions` (`id`, `session_id`, `customer_name`, `customer_fullname`, `chat_topic`, `customer_email`, `user_role`, `external_username`, `external_fullname`, `external_system_id`, `agent_id`, `status`, `created_at`, `closed_at`, `updated_at`, `customer_id`) VALUES
(1, '232f4c05bc38f6c44186cfa2a20ebf37e92ac64f4113a9a301a8eae55dbcd97e', 'Anonymous', 'Anonymous', 'ssssssss', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-08 09:52:17', '2025-08-08 10:05:45', '2025-08-08 10:05:45', NULL),
(2, 'e34fbcb33d933f55788e38de6ec857244c13b2bc2c1d3f52e00ec2d279159cfa', 'Anonymous', 'Anonymous', 'adasdda', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-08 10:06:16', '2025-08-08 10:20:00', '2025-08-08 10:20:00', NULL),
(3, 'a51b485c9380a8c0ea415283aab4d39b6174cc462bf3f1c1279ced06506f0c05', 'Anonymous', 'Anonymous', 'ssssss', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-09 07:58:17', '2025-08-09 08:21:05', '2025-08-09 08:21:05', NULL),
(4, 'f93019eeca1c6ba8ddbd461591679c8ab741b122ef6729b4ae63761995e7d968', 'Anonymous', 'Anonymous', 'ssssssss', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-09 08:21:11', '2025-08-09 08:22:14', '2025-08-09 08:22:14', NULL),
(5, 'ae95092f48f3791b468f4555259478fef92b5b35b71ed8465cdfc2edd3b1fc2e', 'Anonymous', 'Anonymous', 'ssssss', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-11 03:43:00', '2025-08-11 03:43:53', '2025-08-11 03:43:53', NULL),
(6, '0ffa7414b7fdb768eb4a2c41627b728821300f289fd459821faf6720bd9526d8', 'Anonymous', 'Anonymous', 'ssssss', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-11 03:47:48', '2025-08-11 07:55:33', '2025-08-11 07:55:33', NULL),
(7, '4b26cd69986fc919581d448b1eed81a6e0c42f690c1a92a43eca84c942181831', 'Anonymous', 'Anonymous', 'ssssss', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-13 06:21:13', '2025-08-13 06:23:14', '2025-08-13 06:23:14', NULL),
(8, '45f87127fc894e027db66ae564847d7b1957b6fdfa5021cb90c9e78d1737fece', 'Anonymous', 'Anonymous', 'sssssssss', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-13 06:23:19', '2025-08-13 06:23:49', '2025-08-13 06:23:49', NULL),
(9, 'caf374050048d8cf201f3f7e3c494706ee7f90a9c1791ca696cd636770b1bd68', 'Anonymous', 'Anonymous', 'sssss', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-13 06:35:30', '2025-08-13 06:39:24', '2025-08-13 06:39:24', NULL),
(10, '0905fd3b0b94625ab6dd84aa08c12db5c37ebed83555dd6d6708478ccb015618', 'Anonymous', 'Anonymous', 'sssssswqwe', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-13 06:42:01', '2025-08-13 06:44:34', '2025-08-13 06:44:34', NULL),
(11, '7ee2620cfccbe747dde03c71b669a05326c059f92ef2f3594321a2432614b0f7', 'Anonymous', 'Anonymous', 'ssss', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-13 06:55:11', '2025-08-13 07:08:30', '2025-08-13 07:08:30', NULL),
(12, 'b09a774a069dbc9e61331c15eb33a00994e97065b475b20bd29acbad9e1d82dd', 'Anonymous', 'Anonymous', 'ssssssss', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-13 07:08:37', '2025-08-13 07:12:16', '2025-08-13 07:12:16', NULL),
(13, '9330471477f36a286e01094e28842cef74334d7348b93dc0bdc6d23fca20f3a0', 'Anonymous', 'Anonymous', 'ssssssssssss', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-13 07:12:21', '2025-08-13 07:13:50', '2025-08-13 07:13:50', NULL),
(14, '4015a4f216648f254c5fae8bdc291d6f94aa97779d2e58070a7d359fcea94e28', 'Anonymous', 'Anonymous', 'ssss', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-13 07:17:01', '2025-08-13 07:18:13', '2025-08-13 07:18:13', NULL),
(15, '24a3baba680c3be3f4df55653a60181b20bb769b28e9ff3e604fbeace4f97cfc', 'Anonymous', 'Anonymous', 'asdadadad', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-13 07:18:17', '2025-08-13 07:26:46', '2025-08-13 07:26:46', NULL),
(16, '585be6472f402e8437cf267f3bf2d96fc2a50309c971925a79e20df79756f75a', 'Anonymous', 'Anonymous', 'ssss', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-13 07:26:31', '2025-08-13 07:26:49', '2025-08-13 07:26:49', NULL),
(17, '88f8ff0c4977341a9bb7884c7c8a39fab394433013a61b999ec94888065d82fa', 'Anonymous', 'Anonymous', 'asdasd', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-13 09:10:00', '2025-08-13 09:18:24', '2025-08-13 09:18:24', NULL),
(18, 'f22052214f8afdeb0b2c34b822c746a362a85298c04e983e36093d288762995d', 'Anonymous', 'Anonymous', 'FInally working?', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-13 09:18:40', '2025-08-13 09:35:45', '2025-08-13 09:35:45', NULL),
(19, 'b9b61d8ef200dc64c98a12f66cd23cd841f390b6342add823c346ef8526c2b02', 'Hehe', 'Hehe', 'loloololol', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-13 09:36:06', '2025-08-13 09:42:39', '2025-08-13 09:42:39', NULL),
(20, 'd0f504a205ef81fde976177b0f1bb095951feb96eb592b83f2455bfb30e647cd', 'John Doe', 'John Doe', 'ty', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-13 09:45:09', '2025-08-13 09:48:58', '2025-08-13 09:48:58', NULL),
(21, '5703eb9b356d02d1330bd3a0daae1a077801c792819b9506fd20062c38e01974', 'John Doe', 'John Doe', 'ty', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-13 09:45:09', '2025-08-13 09:49:00', '2025-08-13 09:49:00', NULL),
(22, '01d39aada60feb4a74c95e0c7376e815e6cf77f8317efae1b81deed7bb7899f8', 'asdasdasd', 'asdasdasd', '@@@@', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-13 09:48:48', '2025-08-13 09:48:55', '2025-08-13 09:48:55', NULL),
(23, '0c8f03e36d0e679695bafe30d745f8aa844578c3d14cf75b26702166f6922c66', 'Anonymous', 'Anonymous', 'hello', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-13 09:52:46', '2025-08-13 09:53:01', '2025-08-13 09:53:01', NULL),
(24, 'db002228a3d84ec0c13e0829e6200067928ac411330d3fd3d39f255149aa6de3', 'Fortune Wheel', 'Fortune Wheel', 'Im here from Fortune Wheel', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-13 10:06:26', '2025-08-13 10:08:16', '2025-08-13 10:08:16', NULL),
(25, 'dacc2a78cb209056e749acd8888a12f648763adde0d66146237677ecf417ff0c', 'Noah', 'Noah', 'Testing', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-14 07:36:46', '2025-08-14 07:53:53', '2025-08-14 07:53:53', NULL),
(26, '17de6c6fe79ef9a79fc38b67a3f53eb6ceb055e2310907ea8e2e978b8e7e769a', 'Sss', 'Sss', 'Sss', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-14 07:37:44', '2025-08-14 07:53:50', '2025-08-14 07:53:50', NULL),
(27, '2e7f1708638c707785b6f145faaeb33efa093c8f6338646a1bf6780966ab69e6', 'Anonymous', 'Anonymous', 'tyyhyh', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-14 07:52:43', '2025-08-14 07:53:48', '2025-08-14 07:53:48', NULL),
(28, '077424d6f6e047c8721e534c853adf70b5b93ebf542b4ba64147758ab89a25e8', 'Anonymous', 'Anonymous', 'lioliuluiu', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-14 07:55:20', '2025-08-14 07:56:48', '2025-08-14 07:56:48', NULL),
(29, 'e640278ecb0322775e084aecdd3c7df01be5aeb5494b07fe04ed04e7ede7cb6e', 'Anonymous', 'Anonymous', 'lol', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-14 07:58:14', '2025-08-14 08:00:56', '2025-08-14 08:00:56', NULL),
(30, '0be2dca647c9fab4b52cf95006034b2c57e41ae90652ac6aab3eae02e122490a', 'Anonymous', 'Anonymous', 'Hello', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-14 07:58:53', '2025-08-14 08:06:16', '2025-08-14 08:06:16', NULL),
(31, '23f51ce6b84e34f25073bfca3095b68a9ebd760aa02e8fd8ec2ebf1af632e7b0', 'Anonymous', 'Anonymous', 'sssss', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-14 08:57:27', '2025-08-14 09:00:18', '2025-08-14 09:00:18', NULL),
(32, '7520744e12e1b6c12fa840493f54faa9fad1bafc38d5930a9360f07db339f957', 'Anonymous', 'Anonymous', 'adstuahybdjk', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-14 08:57:35', '2025-08-14 09:00:28', '2025-08-14 09:00:28', NULL),
(33, '99244ca855f8ab4c784592810104947d1597a73aa223949de6bf7eaa89728518', 'Anonymous', 'Anonymous', 'ssss', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-14 08:58:52', '2025-08-14 09:00:24', '2025-08-14 09:00:24', NULL),
(34, '507544c4d51591979535a67fdb758a46406284c92a5b68537a654ca01d62f33f', 'Anonymous', 'Anonymous', 'uikjnsedw', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-14 08:59:17', '2025-08-14 09:00:21', '2025-08-14 09:00:21', NULL),
(35, '50513c939b76b9e63562d16b7219e518c2005f84b64e5d3322526dfae21c4bd1', 'Anonymous', 'Anonymous', 'qee3123', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-14 08:59:54', '2025-08-14 09:00:12', '2025-08-14 09:00:12', NULL),
(36, '40d6b2e83334c8d9b5fe578b041e51f0484b88160fdbc71d54a37e780ce7d31f', 'Anonymous', 'Anonymous', 'asdasdasdad', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-14 09:04:56', '2025-08-18 05:03:56', '2025-08-18 05:03:56', NULL),
(37, '4cf30839fdee286e13d55a636494233802df4a6e52047f2a81d8f17512987814', 'Noah', 'Noah', 'Something new', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-16 09:04:28', '2025-08-18 05:03:53', '2025-08-18 05:03:53', NULL),
(38, '83ab73a58e71c685bdb1f95f7e67f17a1c88b8aba175d8593251e88c1236236f', 'Koko', 'Koko', 'Jjj', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-16 09:05:34', '2025-08-18 05:03:17', '2025-08-18 05:03:17', NULL),
(39, 'da1280606935810045e3e6b8a333c87e694f7ed469ff2ebd9aebd24d48b11894', 'Sanny', 'Sanny', 'Nothing', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-18 04:50:05', '2025-08-18 05:03:14', '2025-08-18 05:03:14', NULL),
(40, '34963507c373460c914912c507ae0150b95a59c03271426ca4a68a7b392d4349', 'Danny', 'Danny', 'Nothing2', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-18 04:51:12', '2025-08-18 05:03:11', '2025-08-18 05:03:11', NULL),
(41, 'e796a85757c113ef80d118ab76ba12f91a99ae9ff341fcca7f355ff7d9338dfc', 'Noah', 'Noah', 'Nothing3', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-18 04:51:48', '2025-08-18 05:03:09', '2025-08-18 05:03:09', NULL),
(42, '9be1afb9ef8bb90fc255300086c9d344bd2bbba16181702d102eb44b04c500c3', 'Mama loo', 'Mama loo', 'Pki', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-18 04:54:18', '2025-08-18 05:03:07', '2025-08-18 05:03:07', NULL),
(43, 'e601b2f7a91ce5beaadfa9a6bce3ed7637670835a0dfc2f6fa7b9d564244e707', 'ssssss', 'ssssss', 'ssssssss', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-18 05:08:22', '2025-08-18 07:13:57', '2025-08-18 07:13:57', NULL),
(44, '3317d5a5bfb6f0a8ba40c7fd51702a39a9dd97dae98ac59df1b66cd590f4ab7c', 'lolll', 'lolll', 'sdasd', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-18 07:14:05', '2025-08-18 07:24:44', '2025-08-18 07:24:44', NULL),
(45, 'c71ad283ad61b4ced4e927a24030cbf77b9c6263460f5903b0aedb37feb35102', 'Anonymous', 'Anonymous', 'ssssss', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-18 07:14:37', '2025-08-18 08:05:04', '2025-08-18 08:05:04', NULL),
(46, '6944da0edb35b74d2fbbb426d9967668b1804a0783091d9925c5a4a2f7fc5e0d', 'Anonymous', 'Anonymous', 'sdasd', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-18 08:08:34', '2025-08-18 08:41:30', '2025-08-18 08:41:30', NULL),
(47, '58fa6bf54e7b0bfa848ede79c30f2566542cc1fc46e4c612567af2fc7e64026f', 'Anonymous', 'Anonymous', 'asdasdads', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-18 08:43:05', '2025-08-18 08:43:17', '2025-08-18 08:43:17', NULL),
(48, '64f540880e719738077b16cf39d2ca3039e780b0940fc666fa1c51b477b7c6a2', 'Anonymous', 'Anonymous', 'asdqweqwe', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-18 08:44:41', '2025-08-18 08:45:45', '2025-08-18 08:45:45', NULL),
(49, '55016860f152c54872e556f75a98b2b1d5667e6b34720fee35946ecb1975dd0d', 'John Doe', 'John Doe', 'olaspodmq', '', 'anonymous', '', '', '', NULL, 'closed', '2025-08-18 08:52:47', '2025-08-18 08:55:56', '2025-08-18 08:55:56', NULL),
(50, '986326119a52b25ca0e11e8d8536e78b1cd150c0974033c3e596d5f540ae9b46', 'Mary Jane', 'Mary Jane', 'qwe12', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-18 08:56:04', '2025-08-18 09:38:09', '2025-08-18 09:38:09', NULL),
(51, '17ad725944cf43eeb3e44e31949aeb2c1e57966aad61d683093ffb5e0db4c70a', 'John Doe', 'John Doe', 'Hehe', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-18 09:38:19', '2025-08-18 09:38:38', '2025-08-18 09:38:38', NULL),
(52, 'e972b75a28129c6f3196d8b8133b291bcb25ab708a765badd4c96690d042e165', 'Mary Jane', 'Mary Jane', 'Testing', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-18 09:43:39', '2025-08-19 06:27:28', '2025-08-19 06:27:28', NULL),
(53, '91621f1c6b114f488cc1f96e9c7bf59cb484ff5fb46d6814f47d60f95f4e5c8a', 'Mary Jane', 'Mary Jane', 'adasdasda', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-19 06:27:35', '2025-08-19 08:24:56', '2025-08-19 08:24:56', NULL),
(54, 'eee9a47f5945fe0fd3dfcd6cb462c6b17c5d9e6e682817d06b3ac1de1c313e0f', 'John Doe', 'John Doe', 'FortuneWheel test', 'adsddwqeq@gmail.com', 'anonymous', '', '', '', 1, 'closed', '2025-08-19 08:25:18', '2025-08-19 08:54:16', '2025-08-19 08:54:16', NULL),
(55, '011ddef8f4c4c40c5afa9b1b2884890763cc813a82e7c9b755c79526aa1db941', 'BY', 'BY', 'Lol', '', 'anonymous', '', '', '', 1, 'active', '2025-08-19 10:00:13', NULL, '2025-08-19 10:00:16', NULL),
(56, 'a9fb9dfb8fda3ddaa2ee5b055c599ae13e50190ad32fd7cebad352c836ddebd1', 'Anonymous', 'Anonymous', 'asdasdasd', '', 'anonymous', '', '', '', NULL, 'closed', '2025-08-20 08:16:08', '2025-08-20 08:23:28', '2025-08-20 08:23:28', NULL),
(57, '09cfae7f6d15baf4c042654dfb1c574c2e1ceb251a531814b37efce8aff997ce', 'Anonymous', 'Anonymous', 'test date', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-20 08:23:35', '2025-08-21 01:44:17', '2025-08-21 01:44:17', NULL),
(58, 'ef30718a33c3b42605ee04604b655a14c2e526c0b5af8911c32378aa736c50b3', 'Non', 'Non', 'asdasd', '', 'anonymous', '', '', '', 1, 'closed', '2025-08-20 13:27:48', '2025-08-21 01:44:20', '2025-08-21 01:44:20', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `keywords_responses`
--

CREATE TABLE `keywords_responses` (
  `id` int(11) NOT NULL,
  `keyword` varchar(255) NOT NULL,
  `response` text NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `keywords_responses`
--

INSERT INTO `keywords_responses` (`id`, `keyword`, `response`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Service', 'Our service mainly focuses on software solution and development!', 1, '2025-08-03 15:44:32', '2025-08-03 16:00:13'),
(2, 'Refund', 'To request a refund, please wait for an agent to join the chat.', 1, '2025-08-03 15:44:32', '2025-08-18 09:33:41');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `sender_type` enum('customer','agent') NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `message_type` enum('text','file','image','system') DEFAULT 'text',
  `file_path` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `session_id`, `sender_type`, `sender_id`, `message`, `is_read`, `created_at`, `message_type`, `file_path`, `file_name`) VALUES
(1, 18, 'customer', NULL, 'Refund', 0, '2025-08-13 09:18:56', 'text', NULL, NULL),
(2, 18, 'agent', 1, 'Hello! Welcome to our support chat. How can I help you today?', 0, '2025-08-13 09:19:02', 'text', NULL, NULL),
(3, 18, 'agent', 1, 'Hello! Welcome to our support chat. How can I help you today?', 0, '2025-08-13 09:25:03', 'text', NULL, NULL),
(4, 18, 'agent', 1, 'Thank you for your patience. Let me look into this for you.', 0, '2025-08-13 09:25:03', 'text', NULL, NULL),
(5, 18, 'customer', NULL, 'Service', 0, '2025-08-13 09:29:59', 'text', NULL, NULL),
(6, 18, 'agent', 1, 'gay', 0, '2025-08-13 09:30:25', 'text', NULL, NULL),
(7, 18, 'agent', 1, 'Lol', 0, '2025-08-13 09:30:36', 'text', NULL, NULL),
(8, 18, 'agent', 1, 'testing', 0, '2025-08-13 09:35:30', 'text', NULL, NULL),
(9, 18, 'agent', 1, 'ye boi', 0, '2025-08-13 09:35:33', 'text', NULL, NULL),
(10, 18, 'customer', NULL, 'Tq', 0, '2025-08-13 09:35:38', 'text', NULL, NULL),
(11, 18, 'customer', NULL, 'u cibai', 0, '2025-08-13 09:35:40', 'text', NULL, NULL),
(12, 19, 'agent', 1, 'Hello! Welcome to our support chat. How can I help you today?', 0, '2025-08-13 09:37:46', 'text', NULL, NULL),
(13, 19, 'agent', NULL, 'Customer left the chat', 0, '2025-08-13 09:41:54', 'system', NULL, NULL),
(14, 21, 'agent', NULL, 'Customer left the chat', 0, '2025-08-13 09:48:42', 'system', NULL, NULL),
(15, 23, 'agent', NULL, 'Customer left the chat', 0, '2025-08-13 09:52:50', 'system', NULL, NULL),
(16, 24, 'customer', NULL, 'Service', 0, '2025-08-13 10:06:30', 'text', NULL, NULL),
(17, 24, 'agent', NULL, 'Our service mainly focuses on software solution and development!', 0, '2025-08-13 10:06:30', 'text', NULL, NULL),
(18, 24, 'customer', NULL, 'Refund', 0, '2025-08-13 10:06:31', 'text', NULL, NULL),
(19, 24, 'agent', NULL, 'To request a refund, please click on the \"Chat with Agent\" button', 0, '2025-08-13 10:06:31', 'text', NULL, NULL),
(20, 24, 'agent', 1, 'Thank you for your patience. Let me look into this for you.', 0, '2025-08-13 10:06:43', 'text', NULL, NULL),
(21, 24, 'agent', 1, 'Hello! Welcome to our support chat. How can I help you today?', 0, '2025-08-13 10:06:44', 'text', NULL, NULL),
(22, 24, 'agent', NULL, 'Customer left the chat', 0, '2025-08-13 10:06:50', 'system', NULL, NULL),
(23, 25, 'customer', NULL, 'Ddddsdd', 0, '2025-08-14 07:37:18', 'text', NULL, NULL),
(24, 25, 'agent', NULL, 'Customer left the chat', 0, '2025-08-14 07:37:33', 'system', NULL, NULL),
(25, 26, 'customer', NULL, 'Dd', 0, '2025-08-14 07:40:01', 'text', NULL, NULL),
(26, 27, 'agent', NULL, 'Customer left the chat', 0, '2025-08-14 07:53:39', 'system', NULL, NULL),
(27, 28, 'customer', NULL, 'Service', 0, '2025-08-14 07:55:31', 'text', NULL, NULL),
(28, 28, 'agent', NULL, 'Our service mainly focuses on software solution and development!', 0, '2025-08-14 07:55:31', 'text', NULL, NULL),
(29, 28, 'customer', NULL, 'Refund', 0, '2025-08-14 07:55:33', 'text', NULL, NULL),
(30, 28, 'agent', NULL, 'To request a refund, please click on the \"Chat with Agent\" button', 0, '2025-08-14 07:55:33', 'text', NULL, NULL),
(31, 28, 'agent', 1, 'Hello! Welcome to our support chat. How can I help you today?', 0, '2025-08-14 07:55:44', 'text', NULL, NULL),
(32, 28, 'customer', NULL, 'Refund', 0, '2025-08-14 07:55:52', 'text', NULL, NULL),
(33, 28, 'agent', 1, 'Thank you for your patience. Let me look into this for you.', 0, '2025-08-14 07:55:55', 'text', NULL, NULL),
(34, 28, 'agent', 1, 'Thank you for contacting us today. Have a great day!', 0, '2025-08-14 07:56:18', 'text', NULL, NULL),
(35, 30, 'customer', NULL, 'Need', 0, '2025-08-14 07:58:56', 'text', NULL, NULL),
(36, 30, 'customer', NULL, 'Help', 0, '2025-08-14 07:58:57', 'text', NULL, NULL),
(37, 30, 'customer', NULL, 'But like', 0, '2025-08-14 07:59:00', 'text', NULL, NULL),
(38, 30, 'customer', NULL, 'How do i fix this', 0, '2025-08-14 07:59:03', 'text', NULL, NULL),
(39, 30, 'customer', NULL, 'Ayo', 0, '2025-08-14 07:59:07', 'text', NULL, NULL),
(40, 30, 'customer', NULL, 'Lol', 0, '2025-08-14 07:59:13', 'text', NULL, NULL),
(41, 29, 'agent', NULL, 'Customer left the chat', 0, '2025-08-14 07:59:27', 'system', NULL, NULL),
(42, 30, 'agent', 1, 'Hello! Welcome to our support chat. How can I help you today?', 0, '2025-08-14 08:02:12', 'text', NULL, NULL),
(43, 30, 'agent', NULL, 'Customer left the chat', 0, '2025-08-14 08:04:57', 'system', NULL, NULL),
(44, 31, 'agent', NULL, 'Customer left the chat', 0, '2025-08-14 08:57:29', 'system', NULL, NULL),
(45, 32, 'agent', NULL, 'Customer left the chat', 0, '2025-08-14 08:58:45', 'system', NULL, NULL),
(46, 33, 'agent', NULL, 'Customer left the chat', 0, '2025-08-14 08:59:13', 'system', NULL, NULL),
(47, 34, 'agent', NULL, 'Customer left the chat', 0, '2025-08-14 08:59:30', 'system', NULL, NULL),
(48, 37, 'agent', NULL, 'Customer left the chat', 0, '2025-08-16 09:05:15', 'system', NULL, NULL),
(49, 38, 'customer', NULL, 'Djjdjsjjsksk', 0, '2025-08-16 09:05:43', 'text', NULL, NULL),
(50, 38, 'agent', 1, 'jkn', 0, '2025-08-16 09:05:46', 'text', NULL, NULL),
(51, 38, 'agent', 1, 'Please provide us your name and e-mail so we can get back to you at a later date', 0, '2025-08-16 09:05:48', 'text', NULL, NULL),
(52, 38, 'customer', NULL, 'Service', 0, '2025-08-16 09:05:50', 'text', NULL, NULL),
(53, 38, 'customer', NULL, 'Refund', 0, '2025-08-16 09:05:51', 'text', NULL, NULL),
(54, 38, 'agent', NULL, 'Customer left the chat', 0, '2025-08-16 09:06:43', 'system', NULL, NULL),
(55, 39, 'agent', NULL, 'Customer left the chat', 0, '2025-08-18 04:50:16', 'system', NULL, NULL),
(56, 40, 'agent', NULL, 'Customer left the chat', 0, '2025-08-18 04:51:21', 'system', NULL, NULL),
(57, 41, 'agent', NULL, 'Customer left the chat', 0, '2025-08-18 04:51:52', 'system', NULL, NULL),
(58, 41, 'agent', 1, 'jjj\'', 0, '2025-08-18 04:52:17', 'text', NULL, NULL),
(59, 38, 'agent', 2, 'lll', 0, '2025-08-18 04:54:32', 'text', NULL, NULL),
(60, 38, 'agent', 2, 'lll', 0, '2025-08-18 04:55:14', 'text', NULL, NULL),
(61, 42, 'agent', NULL, 'Customer left the chat', 0, '2025-08-18 05:01:04', 'system', NULL, NULL),
(62, 43, 'agent', 1, 'Hello! Welcome to our support chat. How can I help you today?', 0, '2025-08-18 05:08:28', 'text', NULL, NULL),
(63, 43, 'customer', NULL, 'Service', 0, '2025-08-18 05:08:30', 'text', NULL, NULL),
(64, 43, 'agent', 1, 'Hello! Welcome to our support chat. How can I help you today?', 0, '2025-08-18 07:12:10', 'text', NULL, NULL),
(65, 43, 'agent', NULL, 'Customer left the chat', 0, '2025-08-18 07:13:15', 'system', NULL, NULL),
(66, 44, 'agent', NULL, 'Customer left the chat', 0, '2025-08-18 07:14:33', 'system', NULL, NULL),
(67, 44, 'agent', 1, 'Hello! Welcome to our support chat. How can I help you today?', 0, '2025-08-18 07:21:48', 'text', NULL, NULL),
(68, 45, 'agent', 1, 'Hello! Welcome to our support chat. How can I help you today?', 0, '2025-08-18 07:25:41', 'text', NULL, NULL),
(69, 45, 'customer', NULL, 'Refund', 0, '2025-08-18 07:29:16', 'text', NULL, NULL),
(70, 45, 'agent', NULL, 'Customer left the chat - Session closed', 0, '2025-08-18 08:05:04', 'system', NULL, NULL),
(71, 46, 'customer', NULL, 'Service', 0, '2025-08-18 08:08:42', 'text', NULL, NULL),
(72, 46, 'agent', NULL, 'Our service mainly focuses on software solution and development!', 0, '2025-08-18 08:08:42', 'text', NULL, NULL),
(73, 46, 'customer', NULL, 'Hello', 0, '2025-08-18 08:08:51', 'text', NULL, NULL),
(74, 46, 'agent', 1, 'Hi', 0, '2025-08-18 08:08:55', 'text', NULL, NULL),
(75, 46, 'agent', NULL, 'Customer left the chat - Session closed', 0, '2025-08-18 08:41:30', 'system', NULL, NULL),
(76, 47, 'customer', NULL, 'hi', 0, '2025-08-18 08:43:07', 'text', NULL, NULL),
(77, 47, 'agent', 1, 'Hello! Welcome to our support chat. How can I help you today?', 0, '2025-08-18 08:43:14', 'text', NULL, NULL),
(78, 47, 'agent', NULL, 'Customer left the chat - Session closed', 0, '2025-08-18 08:43:17', 'system', NULL, NULL),
(79, 48, 'agent', 1, 'Hello! Welcome to our support chat. How can I help you today?', 0, '2025-08-18 08:44:46', 'text', NULL, NULL),
(80, 48, 'customer', NULL, 'Refund', 0, '2025-08-18 08:44:49', 'text', NULL, NULL),
(81, 48, 'agent', NULL, 'Customer left the chat - Session closed', 0, '2025-08-18 08:45:45', 'system', NULL, NULL),
(82, 49, 'customer', NULL, 'Service', 0, '2025-08-18 08:52:50', 'text', NULL, NULL),
(83, 49, 'agent', NULL, 'Our service mainly focuses on software solution and development!', 0, '2025-08-18 08:52:50', 'text', NULL, NULL),
(84, 49, 'customer', NULL, 'Refund', 0, '2025-08-18 08:55:43', 'text', NULL, NULL),
(85, 49, 'agent', NULL, 'To request a refund, please click on the \"Chat with Agent\" button', 0, '2025-08-18 08:55:43', 'text', NULL, NULL),
(86, 49, 'agent', NULL, 'Customer left the chat - Session closed', 0, '2025-08-18 08:55:56', 'system', NULL, NULL),
(87, 50, 'customer', NULL, 'Hi', 0, '2025-08-18 08:56:07', 'text', NULL, NULL),
(88, 50, 'customer', NULL, 'Service', 0, '2025-08-18 09:01:44', 'text', NULL, NULL),
(89, 50, 'agent', NULL, 'Our service mainly focuses on software solution and development!', 0, '2025-08-18 09:01:44', 'text', NULL, NULL),
(90, 50, 'agent', 1, 'Hello', 0, '2025-08-18 09:01:53', 'text', NULL, NULL),
(91, 50, 'customer', NULL, 'Hi', 0, '2025-08-18 09:02:09', 'text', NULL, NULL),
(92, 50, 'customer', NULL, 'Lol', 0, '2025-08-18 09:02:15', 'text', NULL, NULL),
(93, 50, 'agent', 1, 'Thank you for your patience. Let me look into this for you.', 0, '2025-08-18 09:02:41', 'text', NULL, NULL),
(94, 50, 'customer', NULL, 'Lol', 0, '2025-08-18 09:06:18', 'text', NULL, NULL),
(95, 50, 'customer', NULL, 'hi', 0, '2025-08-18 09:06:20', 'text', NULL, NULL),
(96, 50, 'agent', 1, 'Thank you for your patience. Let me look into this for you.', 0, '2025-08-18 09:06:23', 'text', NULL, NULL),
(97, 50, 'agent', 1, 'Nigga', 0, '2025-08-18 09:06:28', 'text', NULL, NULL),
(98, 50, 'agent', 3, 'Thank you for your patience. Let me look into this for you.', 0, '2025-08-18 09:08:35', 'text', NULL, NULL),
(99, 50, 'agent', 3, 'Thank you for contacting us today. Have a great day!', 0, '2025-08-18 09:12:37', 'text', NULL, NULL),
(100, 50, 'agent', 3, 'lol', 0, '2025-08-18 09:17:45', 'text', NULL, NULL),
(101, 50, 'agent', 3, 'damn', 0, '2025-08-18 09:17:49', 'text', NULL, NULL),
(102, 50, 'agent', 1, 'What', 0, '2025-08-18 09:18:20', 'text', NULL, NULL),
(103, 50, 'customer', NULL, 'Refund', 0, '2025-08-18 09:18:39', 'text', NULL, NULL),
(104, 50, 'customer', NULL, 'Service', 0, '2025-08-18 09:18:42', 'text', NULL, NULL),
(105, 50, 'customer', NULL, 'Service', 0, '2025-08-18 09:23:08', 'text', NULL, NULL),
(106, 50, 'customer', NULL, 'Refund', 0, '2025-08-18 09:26:25', 'text', NULL, NULL),
(107, 50, 'customer', NULL, 'Service', 0, '2025-08-18 09:26:31', 'text', NULL, NULL),
(108, 50, 'customer', NULL, 'Service', 0, '2025-08-18 09:28:59', 'text', NULL, NULL),
(109, 50, 'customer', NULL, 'Refund', 0, '2025-08-18 09:29:31', 'text', NULL, NULL),
(110, 50, 'agent', 1, 'Thank you for your patience. Let me look into this for you.', 0, '2025-08-18 09:29:33', 'text', NULL, NULL),
(111, 50, 'customer', NULL, 'Service', 0, '2025-08-18 09:30:47', 'text', NULL, NULL),
(112, 50, 'customer', NULL, 'asdqwedqwe', 0, '2025-08-18 09:30:48', 'text', NULL, NULL),
(113, 50, 'agent', 1, 'Thank you for your patience. Let me look into this for you.', 0, '2025-08-18 09:30:56', 'text', NULL, NULL),
(114, 50, 'customer', NULL, 'Service', 0, '2025-08-18 09:38:00', 'text', NULL, NULL),
(115, 51, 'customer', NULL, 'Service', 0, '2025-08-18 09:38:22', 'text', NULL, NULL),
(116, 51, 'agent', NULL, 'Our service mainly focuses on software solution and development!', 0, '2025-08-18 09:38:22', 'text', NULL, NULL),
(117, 51, 'agent', 1, 'Hello! Welcome to our support chat. How can I help you today?', 0, '2025-08-18 09:38:27', 'text', NULL, NULL),
(118, 51, 'customer', NULL, 'Hi\'', 0, '2025-08-18 09:38:31', 'text', NULL, NULL),
(119, 52, 'agent', 1, 'Hello! Welcome to our support chat. How can I help you today?', 0, '2025-08-18 09:43:44', 'text', NULL, NULL),
(120, 52, 'customer', NULL, 'Hello my g', 0, '2025-08-18 09:43:49', 'text', NULL, NULL),
(121, 52, 'agent', 1, 'Whaddup', 0, '2025-08-18 09:43:54', 'text', NULL, NULL),
(122, 52, 'agent', 1, 'Hello! Welcome to our support chat. How can I help you today?', 0, '2025-08-19 06:25:20', 'text', NULL, NULL),
(123, 53, 'customer', NULL, 'Hi', 0, '2025-08-19 06:27:37', 'text', NULL, NULL),
(124, 53, 'customer', NULL, 'Service', 0, '2025-08-19 06:27:38', 'text', NULL, NULL),
(125, 53, 'agent', NULL, 'Our service mainly focuses on software solution and development!', 0, '2025-08-19 06:27:38', 'text', NULL, NULL),
(126, 53, 'customer', NULL, 'Refund', 0, '2025-08-19 06:27:39', 'text', NULL, NULL),
(127, 53, 'agent', NULL, 'To request a refund, please wait for an agent to join the chat.', 0, '2025-08-19 06:27:39', 'text', NULL, NULL),
(128, 53, 'agent', 1, 'Hello! Welcome to our support chat. How can I help you today?', 0, '2025-08-19 06:27:43', 'text', NULL, NULL),
(129, 53, 'customer', NULL, 'Hi', 0, '2025-08-19 06:27:47', 'text', NULL, NULL),
(130, 53, 'agent', 1, 'Please provide us your name and e-mail so we can get back to you at a later date', 0, '2025-08-19 06:32:21', 'text', NULL, NULL),
(131, 53, 'agent', 3, 'Hi', 0, '2025-08-19 06:54:45', 'text', NULL, NULL),
(132, 53, 'customer', NULL, 'Refund', 0, '2025-08-19 06:54:57', 'text', NULL, NULL),
(133, 53, 'agent', 3, 'Thank you for your patience. Let me look into this for you.', 0, '2025-08-19 07:15:55', 'text', NULL, NULL),
(134, 53, 'customer', NULL, 'NIgga', 0, '2025-08-19 07:16:02', 'text', NULL, NULL),
(135, 53, 'customer', NULL, 'sdasdadadqaihdqydegqiedqowheiqge iq qhweiqiehq eq', 0, '2025-08-19 07:16:16', 'text', NULL, NULL),
(136, 53, 'agent', 3, 'Hello! Welcome to our support chat. How can I help you today?', 0, '2025-08-19 07:22:17', 'text', NULL, NULL),
(137, 53, 'customer', NULL, 'dog', 0, '2025-08-19 07:22:24', 'text', NULL, NULL),
(138, 53, 'agent', NULL, 'Customer left the chat - Session closed', 0, '2025-08-19 08:24:56', 'system', NULL, NULL),
(139, 54, 'customer', NULL, 'hi', 0, '2025-08-19 08:25:22', 'text', NULL, NULL),
(140, 54, 'customer', NULL, 'What', 0, '2025-08-19 08:25:24', 'text', NULL, NULL),
(141, 54, 'customer', NULL, 'r u doing', 0, '2025-08-19 08:25:26', 'text', NULL, NULL),
(142, 54, 'customer', NULL, 'Service', 0, '2025-08-19 08:25:27', 'text', NULL, NULL),
(143, 54, 'agent', NULL, 'Our service mainly focuses on software solution and development!', 0, '2025-08-19 08:25:27', 'text', NULL, NULL),
(144, 54, 'customer', NULL, 'Refund', 0, '2025-08-19 08:25:29', 'text', NULL, NULL),
(145, 54, 'agent', NULL, 'To request a refund, please wait for an agent to join the chat.', 0, '2025-08-19 08:25:29', 'text', NULL, NULL),
(146, 54, 'agent', 1, 'Wot', 0, '2025-08-19 08:25:40', 'text', NULL, NULL),
(147, 54, 'agent', 1, 'Hello! Welcome to our support chat. How can I help you today?', 0, '2025-08-19 08:25:42', 'text', NULL, NULL),
(148, 54, 'customer', NULL, 'Refund', 0, '2025-08-19 08:26:56', 'text', NULL, NULL),
(149, 54, 'agent', NULL, 'Customer left the chat - Session closed', 0, '2025-08-19 08:54:16', 'system', NULL, NULL),
(150, 55, 'agent', 1, 'Hello! Welcome to our support chat. How can I help you today?', 0, '2025-08-19 10:00:18', 'text', NULL, NULL),
(151, 55, 'agent', 1, 'Hello', 0, '2025-08-19 10:04:39', 'text', NULL, NULL),
(152, 55, 'agent', 1, 'What up', 0, '2025-08-19 10:04:42', 'text', NULL, NULL),
(153, 55, 'agent', 1, 'Do u', 0, '2025-08-19 10:04:44', 'text', NULL, NULL),
(154, 55, 'agent', 1, 'Miss me', 0, '2025-08-19 10:04:46', 'text', NULL, NULL),
(155, 55, 'agent', 1, 'lolollol', 0, '2025-08-19 10:04:57', 'text', NULL, NULL),
(156, 55, 'agent', 1, 'asdasd', 0, '2025-08-19 10:06:12', 'text', NULL, NULL),
(157, 55, 'customer', NULL, 'CIBAI', 0, '2025-08-19 11:27:12', 'text', NULL, NULL),
(158, 55, 'agent', 1, 'Wtf do u want nigga', 0, '2025-08-19 11:27:18', 'text', NULL, NULL),
(159, 55, 'customer', NULL, 'Diao', 0, '2025-08-19 11:27:21', 'text', NULL, NULL),
(160, 55, 'customer', NULL, 'nia lin', 0, '2025-08-19 11:27:23', 'text', NULL, NULL),
(161, 55, 'agent', 1, 'Hello! Welcome to our support chat. How can I help you today?', 0, '2025-08-20 08:08:45', 'text', NULL, NULL),
(162, 56, 'customer', NULL, 'Helo', 0, '2025-08-20 08:16:17', 'text', NULL, NULL),
(163, 56, 'agent', NULL, 'Customer left the chat - Session closed', 0, '2025-08-20 08:23:28', 'system', NULL, NULL),
(164, 57, 'customer', NULL, 'Hi', 0, '2025-08-20 08:23:38', 'text', NULL, NULL),
(165, 57, 'agent', 1, 'Hello! Welcome to our support chat. How can I help you today?', 0, '2025-08-20 08:24:56', 'text', NULL, NULL),
(166, 58, 'customer', NULL, 'dd', 0, '2025-08-20 13:27:53', 'text', NULL, NULL),
(167, 58, 'agent', 1, 'dfgdggf', 0, '2025-08-20 13:28:05', 'text', NULL, NULL),
(168, 55, 'agent', 1, 'Thank you for your patience. Let me look into this for you.', 0, '2025-08-21 01:41:50', 'text', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','support') DEFAULT 'support',
  `is_online` tinyint(1) DEFAULT 0,
  `last_seen` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('available','busy','away') DEFAULT 'available',
  `max_concurrent_chats` int(11) DEFAULT 5,
  `current_chats` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `is_online`, `last_seen`, `created_at`, `status`, `max_concurrent_chats`, `current_chats`) VALUES
(1, 'TopAdmin', '$2a$12$oLCF9qB.hm5heXP5y6Z7heEm3j0Dts0x/xyJalxm4sRYIi3ZfzxwG', 'admin@example.com', 'admin', 0, NULL, '2025-07-31 14:31:59', 'available', 5, 0),
(2, 'funnyadmin', '$2y$10$w1ypO0OWUd4mmAOK6SlqseqrvWOajYiX6vKsq.PbuB2Nh2thl62zO', 'qwewdas@adasd.com', 'support', 0, NULL, '2025-08-18 04:53:42', 'available', 5, 0),
(3, 'support1', '$2y$10$mEkyxcnFm7qhHLi0cZNaW.uwkWAxvxEIUCLuveyXTDKufTTARgXEK', 'support@example.com', 'support', 0, NULL, '2025-08-18 09:08:03', 'available', 5, 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `role_description` varchar(255) DEFAULT NULL,
  `can_access_chat` tinyint(1) DEFAULT 1,
  `can_see_chat_history` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`id`, `role_name`, `role_description`, `can_access_chat`, `can_see_chat_history`, `created_at`) VALUES
(1, 'anonymous', 'Anonymous chat user', 1, 0, '2025-08-07 07:06:28'),
(2, 'loggedUser', 'Authenticated user from external system', 1, 1, '2025-08-07 07:06:28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `api_keys`
--
ALTER TABLE `api_keys`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_key_id` (`key_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `canned_responses`
--
ALTER TABLE `canned_responses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `chat_sessions`
--
ALTER TABLE `chat_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_agent_id` (`agent_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `keywords_responses`
--
ALTER TABLE `keywords_responses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_sender_type` (`sender_type`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `api_keys`
--
ALTER TABLE `api_keys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `canned_responses`
--
ALTER TABLE `canned_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `chat_sessions`
--
ALTER TABLE `chat_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `keywords_responses`
--
ALTER TABLE `keywords_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=169;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
