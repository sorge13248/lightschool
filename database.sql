-- phpMyAdmin SQL Dump
-- version 4.8.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 09, 2019 at 05:10 PM
-- Server version: 10.1.31-MariaDB
-- PHP Version: 7.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lightschool`
--

-- --------------------------------------------------------

--
-- Table structure for table `access`
--

CREATE TABLE `access` (
  `id` int(25) NOT NULL,
  `user` int(10) UNSIGNED NOT NULL,
  `date` varchar(19) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `allow` tinyint(1) NOT NULL DEFAULT '1',
  `logged_in` tinyint(1) NOT NULL DEFAULT '1',
  `agent` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'User agent',
  `type` varchar(24) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `all_users`
-- (See below for the actual view)
--
CREATE TABLE `all_users` (
`id` int(10) unsigned
,`email` varchar(249)
,`password` varchar(255)
,`username` varchar(100)
,`status` tinyint(2) unsigned
,`verified` tinyint(1) unsigned
,`resettable` tinyint(1) unsigned
,`roles_mask` int(10) unsigned
,`registered` int(10) unsigned
,`last_login` int(10) unsigned
,`force_logout` mediumint(7) unsigned
,`name` varchar(128)
,`surname` varchar(128)
,`profile_picture` int(11)
,`wallpaper` varchar(55)
,`taskbar` longtext
,`type` varchar(11)
,`accent` varchar(6)
,`theme` varchar(64)
,`plan` tinyint(2) unsigned
,`twofa` blob
,`privacy_search_visible` tinyint(1)
,`privacy_show_email` tinyint(1)
,`privacy_show_username` tinyint(1)
,`privacy_send_messages` tinyint(1) unsigned
,`privacy_ms_office` tinyint(1) unsigned
,`privacy_share_documents` tinyint(1) unsigned
,`password_last_change` timestamp
,`blocked` varchar(1204)
,`taskbar_size` tinyint(4)
);

-- --------------------------------------------------------

--
-- Table structure for table `app_catalog`
--

CREATE TABLE `app_catalog` (
  `unique_name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` float UNSIGNED NOT NULL DEFAULT '1',
  `category` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `author` int(10) UNSIGNED DEFAULT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT '1',
  `icon` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `system` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `settings` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'App has settings page',
  `name_en` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_it` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `detail_en` longtext COLLATE utf8mb4_unicode_ci,
  `detail_it` longtext COLLATE utf8mb4_unicode_ci,
  `features` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preview` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `t_icon` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `app_catalog`
--

INSERT INTO `app_catalog` (`unique_name`, `version`, `category`, `author`, `visible`, `icon`, `system`, `settings`, `name_en`, `name_it`, `detail_en`, `detail_it`, `features`, `preview`, `timestamp`, `t_icon`) VALUES
('contact', 1, 'system', NULL, 1, 1, 0, 1, 'Contact', 'Contatti', NULL, NULL, NULL, 0, '2019-01-01 07:00:00', NULL),
('desktop', 1, 'system', NULL, 1, 1, 0, 0, 'Desktop', 'Desktop', NULL, NULL, NULL, 0, '2019-01-01 07:00:00', NULL),
('diary', 1, 'system', NULL, 1, 1, 0, 0, 'Diary', 'Diario', NULL, NULL, NULL, 0, '2019-01-01 07:00:00', NULL),
('file-manager', 1, 'system', NULL, 1, 1, 0, 0, 'File Manager', 'Gestore file', '<p>Take notes, upload files and organize them in folders</p>', '<p>Prendi appunti, carica file e organizzali in cartelle</p>', NULL, 0, '2019-01-01 07:00:00', NULL),
('message', 1, 'system', NULL, 1, 1, 0, 0, 'Message', 'Messaggi', NULL, NULL, NULL, 0, '2019-01-01 07:00:00', NULL),
('quiz', 0.1, 'system', NULL, 0, 1, 0, 0, 'Quiz', 'Quiz', NULL, NULL, NULL, 1, '2019-01-01 07:00:00', NULL),
('reader', 1, 'system', NULL, 1, 1, 0, 0, 'Reader', 'Lettore', NULL, NULL, NULL, 0, '2019-01-01 07:00:00', NULL),
('register', 0.1, 'system', NULL, 0, 1, 0, 0, 'Register', 'Registro', NULL, NULL, NULL, 1, '2019-01-01 07:00:00', NULL),
('settings', 1, 'system', NULL, 1, 1, 1, 0, 'Settings', 'Impostazioni', NULL, NULL, NULL, 0, '2019-01-01 07:00:00', NULL),
('share', 1, 'system', NULL, 1, 1, 0, 0, 'Share', 'Condivisioni', NULL, NULL, NULL, 0, '2019-01-01 07:00:00', NULL),
('social', 0.1, 'system', NULL, 0, 1, 0, 0, 'Social', 'Social', NULL, NULL, NULL, 1, '2019-08-06 06:00:00', NULL),
('store', 1, 'system', NULL, 1, 1, 1, 0, 'LightStore', 'LightStore', NULL, NULL, NULL, 0, '2019-01-01 07:00:00', NULL),
('t-dark', 1, 'themes', NULL, 1, 0, 0, 0, 'Default', 'Scuro', NULL, NULL, NULL, 0, '2019-01-01 07:00:00', 'white'),
('t-default', 1, 'themes', NULL, 1, 0, 1, 0, 'Default', 'Predefinito', NULL, NULL, NULL, 0, '2019-01-01 07:00:00', 'black'),
('timetable', 1, 'system', NULL, 1, 1, 0, 0, 'Timetable', 'Orario', NULL, NULL, NULL, 0, '2019-01-01 07:00:00', NULL),
('trash', 1, 'system', NULL, 1, 1, 0, 0, 'Trash', 'Cestino', NULL, NULL, NULL, 0, '2019-01-01 07:00:00', NULL),
('writer', 1, 'system', NULL, 1, 1, 0, 0, 'Writer', 'Writer', NULL, NULL, NULL, 0, '2019-01-01 07:00:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `app_category`
--

CREATE TABLE `app_category` (
  `name` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sub` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT '1',
  `icon` tinyint(1) NOT NULL DEFAULT '1',
  `name_en` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_it` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `app_category`
--

INSERT INTO `app_category` (`name`, `sub`, `visible`, `icon`, `name_en`, `name_it`) VALUES
('dark-themes', 'themes', 1, 0, 'Dark themes', 'Temi scuri'),
('light-themes', 'themes', 1, 0, 'Light themes', 'Temi chiari'),
('system', NULL, 1, 1, 'LightSchool System', 'Sistema LightSchool'),
('themes', NULL, 1, 0, 'Themes', 'Temi');

-- --------------------------------------------------------

--
-- Table structure for table `app_purchase`
--

CREATE TABLE `app_purchase` (
  `id` int(10) UNSIGNED NOT NULL,
  `user` int(10) UNSIGNED NOT NULL,
  `app` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `application_launcher` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `data` longtext COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact`
--

CREATE TABLE `contact` (
  `id` int(25) NOT NULL COMMENT 'ID univoco',
  `user_id` int(10) UNSIGNED NOT NULL COMMENT 'ID utente di chi salva il contatto',
  `name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nome del contatto',
  `surname` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Cognome del contatto',
  `contact_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'ID del contatto',
  `fav` tinyint(1) NOT NULL DEFAULT '0',
  `trash` tinyint(1) NOT NULL DEFAULT '0',
  `deleted` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `country`
--

CREATE TABLE `country` (
  `code` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `country`
--

INSERT INTO `country` (`code`, `name`) VALUES
('AD', 'Andorra'),
('AE', 'United Arab Emirates'),
('AF', 'Afghanistan'),
('AG', 'Antigua and Barbuda'),
('AI', 'Anguilla'),
('AL', 'Albania'),
('AM', 'Armenia'),
('AN', 'Netherlands Antilles'),
('AO', 'Angola'),
('AQ', 'Antarctica'),
('AR', 'Argentina'),
('AT', 'Austria'),
('AU', 'Australia'),
('AW', 'Aruba'),
('AZ', 'Azerbaijan'),
('BA', 'Bosnia and Herzegovina'),
('BB', 'Barbados'),
('BD', 'Bangladesh'),
('BE', 'Belgium'),
('BF', 'Burkina Faso'),
('BG', 'Bulgaria'),
('BH', 'Bahrain'),
('BI', 'Burundi'),
('BJ', 'Benin'),
('BM', 'Bermuda'),
('BN', 'Brunei Darussalam'),
('BO', 'Bolivia'),
('BR', 'Brazil'),
('BS', 'Bahamas'),
('BT', 'Bhutan'),
('BV', 'Bouvet Island'),
('BW', 'Botswana'),
('BY', 'Belarus'),
('BZ', 'Belize'),
('CA', 'Canada'),
('CC', 'Cocos (Keeling) Islands'),
('CF', 'Central African Republic'),
('CG', 'Congo'),
('CH', 'Switzerland'),
('CI', 'Ivory Coast'),
('CK', 'Cook Islands'),
('CL', 'Chile'),
('CM', 'Cameroon'),
('CN', 'China'),
('CO', 'Colombia'),
('CR', 'Costa Rica'),
('CU', 'Cuba'),
('CV', 'Cape Verde'),
('CX', 'Christmas Island'),
('CY', 'Cyprus'),
('CZ', 'Czech Republic'),
('DE', 'Germany'),
('DJ', 'Djibouti'),
('DK', 'Denmark'),
('DM', 'Dominica'),
('DO', 'Dominican Republic'),
('DS', 'American Samoa'),
('DZ', 'Algeria'),
('EC', 'Ecuador'),
('EE', 'Estonia'),
('EG', 'Egypt'),
('EH', 'Western Sahara'),
('ER', 'Eritrea'),
('ES', 'Spain'),
('ET', 'Ethiopia'),
('FI', 'Finland'),
('FJ', 'Fiji'),
('FK', 'Falkland Islands (Malvinas)'),
('FM', 'Micronesia, Federated States of'),
('FO', 'Faroe Islands'),
('FR', 'France'),
('FX', 'France, Metropolitan'),
('GA', 'Gabon'),
('GB', 'United Kingdom'),
('GD', 'Grenada'),
('GE', 'Georgia'),
('GF', 'French Guiana'),
('GH', 'Ghana'),
('GI', 'Gibraltar'),
('GK', 'Guernsey'),
('GL', 'Greenland'),
('GM', 'Gambia'),
('GN', 'Guinea'),
('GP', 'Guadeloupe'),
('GQ', 'Equatorial Guinea'),
('GR', 'Greece'),
('GS', 'South Georgia South Sandwich Islands'),
('GT', 'Guatemala'),
('GU', 'Guam'),
('GW', 'Guinea-Bissau'),
('GY', 'Guyana'),
('HK', 'Hong Kong'),
('HM', 'Heard and Mc Donald Islands'),
('HN', 'Honduras'),
('HR', 'Croatia (Hrvatska)'),
('HT', 'Haiti'),
('HU', 'Hungary'),
('ID', 'Indonesia'),
('IE', 'Ireland'),
('IL', 'Israel'),
('IM', 'Isle of Man'),
('IN', 'India'),
('IO', 'British Indian Ocean Territory'),
('IQ', 'Iraq'),
('IR', 'Iran (Islamic Republic of)'),
('IS', 'Iceland'),
('IT', 'Italy'),
('JE', 'Jersey'),
('JM', 'Jamaica'),
('JO', 'Jordan'),
('JP', 'Japan'),
('KE', 'Kenya'),
('KG', 'Kyrgyzstan'),
('KH', 'Cambodia'),
('KI', 'Kiribati'),
('KM', 'Comoros'),
('KN', 'Saint Kitts and Nevis'),
('KP', 'Korea, Democratic People\'s Republic of'),
('KR', 'Korea, Republic of'),
('KW', 'Kuwait'),
('KY', 'Cayman Islands'),
('KZ', 'Kazakhstan'),
('LA', 'Lao People\'s Democratic Republic'),
('LB', 'Lebanon'),
('LC', 'Saint Lucia'),
('LI', 'Liechtenstein'),
('LK', 'Sri Lanka'),
('LR', 'Liberia'),
('LS', 'Lesotho'),
('LT', 'Lithuania'),
('LU', 'Luxembourg'),
('LV', 'Latvia'),
('LY', 'Libyan Arab Jamahiriya'),
('MA', 'Morocco'),
('MC', 'Monaco'),
('MD', 'Moldova, Republic of'),
('ME', 'Montenegro'),
('MG', 'Madagascar'),
('MH', 'Marshall Islands'),
('MK', 'Macedonia'),
('ML', 'Mali'),
('MM', 'Myanmar'),
('MN', 'Mongolia'),
('MO', 'Macau'),
('MP', 'Northern Mariana Islands'),
('MQ', 'Martinique'),
('MR', 'Mauritania'),
('MS', 'Montserrat'),
('MT', 'Malta'),
('MU', 'Mauritius'),
('MV', 'Maldives'),
('MW', 'Malawi'),
('MX', 'Mexico'),
('MY', 'Malaysia'),
('MZ', 'Mozambique'),
('NA', 'Namibia'),
('NC', 'New Caledonia'),
('NE', 'Niger'),
('NF', 'Norfolk Island'),
('NG', 'Nigeria'),
('NI', 'Nicaragua'),
('NL', 'Netherlands'),
('NO', 'Norway'),
('NP', 'Nepal'),
('NR', 'Nauru'),
('NU', 'Niue'),
('NZ', 'New Zealand'),
('OM', 'Oman'),
('PA', 'Panama'),
('PE', 'Peru'),
('PF', 'French Polynesia'),
('PG', 'Papua New Guinea'),
('PH', 'Philippines'),
('PK', 'Pakistan'),
('PL', 'Poland'),
('PM', 'St. Pierre and Miquelon'),
('PN', 'Pitcairn'),
('PR', 'Puerto Rico'),
('PS', 'Palestine'),
('PT', 'Portugal'),
('PW', 'Palau'),
('PY', 'Paraguay'),
('QA', 'Qatar'),
('RE', 'Reunion'),
('RO', 'Romania'),
('RS', 'Serbia'),
('RU', 'Russian Federation'),
('RW', 'Rwanda'),
('SA', 'Saudi Arabia'),
('SB', 'Solomon Islands'),
('SC', 'Seychelles'),
('SD', 'Sudan'),
('SE', 'Sweden'),
('SG', 'Singapore'),
('SH', 'St. Helena'),
('SI', 'Slovenia'),
('SJ', 'Svalbard and Jan Mayen Islands'),
('SK', 'Slovakia'),
('SL', 'Sierra Leone'),
('SM', 'San Marino'),
('SN', 'Senegal'),
('SO', 'Somalia'),
('SR', 'Suriname'),
('SS', 'South Sudan'),
('ST', 'Sao Tome and Principe'),
('SV', 'El Salvador'),
('SY', 'Syrian Arab Republic'),
('SZ', 'Swaziland'),
('TC', 'Turks and Caicos Islands'),
('TD', 'Chad'),
('TF', 'French Southern Territories'),
('TG', 'Togo'),
('TH', 'Thailand'),
('TJ', 'Tajikistan'),
('TK', 'Tokelau'),
('TM', 'Turkmenistan'),
('TN', 'Tunisia'),
('TO', 'Tonga'),
('TP', 'East Timor'),
('TR', 'Turkey'),
('TT', 'Trinidad and Tobago'),
('TV', 'Tuvalu'),
('TW', 'Taiwan'),
('TY', 'Mayotte'),
('TZ', 'Tanzania, United Republic of'),
('UA', 'Ukraine'),
('UG', 'Uganda'),
('UM', 'United States minor outlying islands'),
('US', 'United States'),
('UY', 'Uruguay'),
('UZ', 'Uzbekistan'),
('VA', 'Vatican City State'),
('VC', 'Saint Vincent and the Grenadines'),
('VE', 'Venezuela'),
('VG', 'Virgin Islands (British)'),
('VI', 'Virgin Islands (U.S.)'),
('VN', 'Vietnam'),
('VU', 'Vanuatu'),
('WF', 'Wallis and Futuna Islands'),
('WS', 'Samoa'),
('XK', 'Kosovo'),
('YE', 'Yemen'),
('ZA', 'South Africa'),
('ZM', 'Zambia'),
('ZR', 'Zaire'),
('ZW', 'Zimbabwe');

-- --------------------------------------------------------

--
-- Stand-in structure for view `desktop`
-- (See below for the actual view)
--
CREATE TABLE `desktop` (
`user_id` int(11) unsigned
,`id` int(11) unsigned
,`name` varchar(128)
,`type` varchar(64)
,`surname` varchar(128)
,`diary_type` varchar(24)
,`diary_priority` tinyint(4)
,`diary_date` date
,`diary_color` varchar(6)
,`icon` varchar(24)
,`username` varchar(100)
,`file_url` longtext
,`file_type` varchar(255)
,`deleted` varchar(19)
);

-- --------------------------------------------------------

--
-- Table structure for table `error_report`
--

CREATE TABLE `error_report` (
  `id` int(11) NOT NULL,
  `type` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `detail` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `file`
--

CREATE TABLE `file` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT 'Nome utente',
  `type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Cartella, quaderno, diario o file',
  `name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Titolo cartella, quaderno o file',
  `diary_type` varchar(24) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Tipo diario',
  `diary_date` date DEFAULT NULL,
  `diary_reminder` date DEFAULT NULL,
  `diary_priority` tinyint(1) DEFAULT '0' COMMENT 'Priorità diario',
  `diary_color` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `n_ver` tinyint(4) NOT NULL DEFAULT '1',
  `header` longtext COLLATE utf8mb4_unicode_ci,
  `html` blob,
  `cypher` blob,
  `html_new` longtext COLLATE utf8mb4_unicode_ci,
  `footer` longtext COLLATE utf8mb4_unicode_ci,
  `file_url` longtext COLLATE utf8mb4_unicode_ci COMMENT 'Indirizzo URL file',
  `file_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Tipo file',
  `file_size` int(16) UNSIGNED DEFAULT NULL COMMENT 'Dimensione file',
  `fav` tinyint(1) NOT NULL DEFAULT '0',
  `icon` varchar(24) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Icona',
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data di creazione',
  `last_view` varchar(19) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ultima vista',
  `last_edit` varchar(19) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ultima modifica',
  `folder` int(16) UNSIGNED DEFAULT NULL,
  `trash` tinyint(1) NOT NULL DEFAULT '0',
  `history` int(11) UNSIGNED DEFAULT NULL,
  `bypass` timestamp NULL DEFAULT NULL,
  `deleted` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_actors`
--

CREATE TABLE `message_actors` (
  `id` int(10) UNSIGNED NOT NULL,
  `list_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_chat`
--

CREATE TABLE `message_chat` (
  `id` int(25) UNSIGNED NOT NULL,
  `message_list_id` int(10) UNSIGNED NOT NULL,
  `sender` int(10) UNSIGNED NOT NULL COMMENT 'Chi invia',
  `cypher` blob,
  `body` blob,
  `attachment` blob,
  `date` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data',
  `is_read` timestamp NULL DEFAULT NULL COMMENT 'Letto o no'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_list`
--

CREATE TABLE `message_list` (
  `id` int(10) UNSIGNED NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plan`
--

CREATE TABLE `plan` (
  `id` tinyint(2) UNSIGNED NOT NULL,
  `name` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `disk_space` int(9) UNSIGNED NOT NULL COMMENT 'in MB'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `plan`
--

INSERT INTO `plan` (`id`, `name`, `disk_space`) VALUES
(1, 'basic', 10),
(2, 'admin', 100);

-- --------------------------------------------------------

--
-- Table structure for table `school`
--

CREATE TABLE `school` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `country` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `emergency` tinyint(1) NOT NULL DEFAULT '0',
  `emergency_text` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `share`
--

CREATE TABLE `share` (
  `id` int(11) UNSIGNED NOT NULL COMMENT 'ID univoco',
  `sender` int(10) UNSIGNED NOT NULL COMMENT 'Chi condivide',
  `receiving` int(10) UNSIGNED NOT NULL COMMENT 'Chi riceve',
  `file` int(11) UNSIGNED NOT NULL COMMENT 'ID del file condiviso',
  `comment` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Commento di chi condivide',
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data di condivisione',
  `edit` tinyint(1) NOT NULL DEFAULT '0',
  `deleted` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `theme`
--

CREATE TABLE `theme` (
  `id` int(10) UNSIGNED NOT NULL,
  `author` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unique_name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'black' COMMENT 'Allowed values: white/black'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `theme`
--

INSERT INTO `theme` (`id`, `author`, `name`, `unique_name`, `icon`) VALUES
(1, NULL, 'Default', 'default', 'black'),
(2, NULL, 'Dark', 'dark', 'white'),
(3, NULL, 'Stupid', 'stupid', 'black');

-- --------------------------------------------------------

--
-- Table structure for table `timetable`
--

CREATE TABLE `timetable` (
  `id` int(11) NOT NULL,
  `user` int(10) UNSIGNED NOT NULL,
  `year` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `day` tinyint(1) UNSIGNED NOT NULL,
  `slot` tinyint(1) UNSIGNED NOT NULL,
  `subject` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `book` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fore` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT 'black',
  `deleted` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(249) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(2) UNSIGNED NOT NULL DEFAULT '0',
  `verified` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `resettable` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `roles_mask` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `registered` int(10) UNSIGNED NOT NULL,
  `last_login` int(10) UNSIGNED DEFAULT NULL,
  `force_logout` mediumint(7) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users_confirmations`
--

CREATE TABLE `users_confirmations` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `email` varchar(249) COLLATE utf8mb4_unicode_ci NOT NULL,
  `selector` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users_expanded`
--

CREATE TABLE `users_expanded` (
  `id` int(11) NOT NULL,
  `name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `surname` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `profile_picture` int(11) DEFAULT NULL,
  `wallpaper` varchar(55) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `taskbar` longtext COLLATE utf8mb4_unicode_ci,
  `taskbar_size` tinyint(4) DEFAULT NULL,
  `type` varchar(11) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'student',
  `accent` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `theme` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `plan` tinyint(2) UNSIGNED NOT NULL DEFAULT '1',
  `twofa` blob,
  `deac_twofa` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `privacy_search_visible` tinyint(1) NOT NULL DEFAULT '1',
  `privacy_show_email` tinyint(1) NOT NULL DEFAULT '0',
  `privacy_show_username` tinyint(1) NOT NULL DEFAULT '0',
  `privacy_send_messages` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `privacy_share_documents` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `privacy_ms_office` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `password_last_change` timestamp NULL DEFAULT NULL,
  `blocked` varchar(1204) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users_remembered`
--

CREATE TABLE `users_remembered` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user` int(10) UNSIGNED NOT NULL,
  `selector` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users_resets`
--

CREATE TABLE `users_resets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user` int(10) UNSIGNED NOT NULL,
  `selector` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users_school`
--

CREATE TABLE `users_school` (
  `id` int(10) UNSIGNED NOT NULL,
  `user` int(10) UNSIGNED NOT NULL,
  `school` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users_throttling`
--

CREATE TABLE `users_throttling` (
  `bucket` varchar(44) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokens` float UNSIGNED NOT NULL,
  `replenished_at` int(10) UNSIGNED NOT NULL,
  `expires_at` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure for view `all_users`
--
DROP TABLE IF EXISTS `all_users`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `all_users`  AS  select `users`.`id` AS `id`,`users`.`email` AS `email`,`users`.`password` AS `password`,`users`.`username` AS `username`,`users`.`status` AS `status`,`users`.`verified` AS `verified`,`users`.`resettable` AS `resettable`,`users`.`roles_mask` AS `roles_mask`,`users`.`registered` AS `registered`,`users`.`last_login` AS `last_login`,`users`.`force_logout` AS `force_logout`,`users_expanded`.`name` AS `name`,`users_expanded`.`surname` AS `surname`,`users_expanded`.`profile_picture` AS `profile_picture`,`users_expanded`.`wallpaper` AS `wallpaper`,`users_expanded`.`taskbar` AS `taskbar`,`users_expanded`.`type` AS `type`,`users_expanded`.`accent` AS `accent`,`users_expanded`.`theme` AS `theme`,`users_expanded`.`plan` AS `plan`,`users_expanded`.`twofa` AS `twofa`,`users_expanded`.`privacy_search_visible` AS `privacy_search_visible`,`users_expanded`.`privacy_show_email` AS `privacy_show_email`,`users_expanded`.`privacy_show_username` AS `privacy_show_username`,`users_expanded`.`privacy_send_messages` AS `privacy_send_messages`,`users_expanded`.`privacy_ms_office` AS `privacy_ms_office`,`users_expanded`.`privacy_share_documents` AS `privacy_share_documents`,`users_expanded`.`password_last_change` AS `password_last_change`,`users_expanded`.`blocked` AS `blocked`,`users_expanded`.`taskbar_size` AS `taskbar_size` from (`users` join `users_expanded` on((`users`.`id` = `users_expanded`.`id`))) ;

-- --------------------------------------------------------

--
-- Structure for view `desktop`
--
DROP TABLE IF EXISTS `desktop`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `desktop`  AS  select `file`.`user_id` AS `user_id`,`file`.`id` AS `id`,`file`.`name` AS `name`,`file`.`type` AS `type`,NULL AS `surname`,`file`.`diary_type` AS `diary_type`,`file`.`diary_priority` AS `diary_priority`,`file`.`diary_date` AS `diary_date`,`file`.`diary_color` AS `diary_color`,`file`.`icon` AS `icon`,NULL AS `username`,`file`.`file_url` AS `file_url`,`file`.`file_type` AS `file_type`,`file`.`deleted` AS `deleted` from `file` where ((`file`.`fav` = 1) and isnull(`file`.`history`) and (`file`.`trash` = 0)) union select `contact`.`user_id` AS `user_id`,`contact`.`id` AS `id`,`contact`.`name` AS `name`,'contact' AS `type`,`contact`.`surname` AS `surname`,NULL AS `diary_type`,NULL AS `diary_priority`,NULL AS `diary_date`,NULL AS `diary_color`,`users_expanded`.`profile_picture` AS `icon`,`users`.`username` AS `username`,NULL AS `file_url`,NULL AS `file_type`,`contact`.`deleted` AS `deleted` from ((`contact` join `users_expanded`) join `users`) where ((`contact`.`fav` = 1) and (`contact`.`contact_id` = `users_expanded`.`id`) and (`users_expanded`.`id` = `users`.`id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `access`
--
ALTER TABLE `access`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user`);

--
-- Indexes for table `app_catalog`
--
ALTER TABLE `app_catalog`
  ADD PRIMARY KEY (`unique_name`),
  ADD KEY `category` (`category`),
  ADD KEY `author` (`author`),
  ADD KEY `visible` (`visible`);

--
-- Indexes for table `app_category`
--
ALTER TABLE `app_category`
  ADD PRIMARY KEY (`name`),
  ADD KEY `sub` (`sub`);

--
-- Indexes for table `app_purchase`
--
ALTER TABLE `app_purchase`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user` (`user`,`app`);

--
-- Indexes for table `contact`
--
ALTER TABLE `contact`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contact_id` (`contact_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `country`
--
ALTER TABLE `country`
  ADD PRIMARY KEY (`code`);

--
-- Indexes for table `error_report`
--
ALTER TABLE `error_report`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `file`
--
ALTER TABLE `file`
  ADD PRIMARY KEY (`id`),
  ADD KEY `Username` (`user_id`),
  ADD KEY `history` (`history`),
  ADD KEY `deleted` (`deleted`),
  ADD KEY `trash` (`trash`),
  ADD KEY `folder` (`folder`),
  ADD KEY `fav` (`fav`),
  ADD KEY `type` (`type`);

--
-- Indexes for table `message_actors`
--
ALTER TABLE `message_actors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `message_chat`
--
ALTER TABLE `message_chat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender` (`sender`);

--
-- Indexes for table `message_list`
--
ALTER TABLE `message_list`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `plan`
--
ALTER TABLE `plan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `school`
--
ALTER TABLE `school`
  ADD PRIMARY KEY (`id`),
  ADD KEY `country` (`country`);

--
-- Indexes for table `share`
--
ALTER TABLE `share`
  ADD PRIMARY KEY (`id`),
  ADD KEY `receiving` (`receiving`),
  ADD KEY `sender` (`sender`);

--
-- Indexes for table `theme`
--
ALTER TABLE `theme`
  ADD PRIMARY KEY (`id`),
  ADD KEY `author` (`author`);

--
-- Indexes for table `timetable`
--
ALTER TABLE `timetable`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user` (`user`,`year`,`day`,`slot`,`deleted`),
  ADD KEY `year` (`year`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `users_confirmations`
--
ALTER TABLE `users_confirmations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `selector` (`selector`),
  ADD KEY `email_expires` (`email`(191),`expires`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users_expanded`
--
ALTER TABLE `users_expanded`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users_remembered`
--
ALTER TABLE `users_remembered`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `selector` (`selector`),
  ADD KEY `user` (`user`);

--
-- Indexes for table `users_resets`
--
ALTER TABLE `users_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `selector` (`selector`),
  ADD KEY `user_expires` (`user`,`expires`);

--
-- Indexes for table `users_school`
--
ALTER TABLE `users_school`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users_throttling`
--
ALTER TABLE `users_throttling`
  ADD PRIMARY KEY (`bucket`),
  ADD KEY `expires_at` (`expires_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `access`
--
ALTER TABLE `access`
  MODIFY `id` int(25) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `app_purchase`
--
ALTER TABLE `app_purchase`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact`
--
ALTER TABLE `contact`
  MODIFY `id` int(25) NOT NULL AUTO_INCREMENT COMMENT 'ID univoco';

--
-- AUTO_INCREMENT for table `error_report`
--
ALTER TABLE `error_report`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `file`
--
ALTER TABLE `file`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `message_actors`
--
ALTER TABLE `message_actors`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `message_chat`
--
ALTER TABLE `message_chat`
  MODIFY `id` int(25) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `message_list`
--
ALTER TABLE `message_list`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `plan`
--
ALTER TABLE `plan`
  MODIFY `id` tinyint(2) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `school`
--
ALTER TABLE `school`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `share`
--
ALTER TABLE `share`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID univoco';

--
-- AUTO_INCREMENT for table `theme`
--
ALTER TABLE `theme`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timetable`
--
ALTER TABLE `timetable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users_confirmations`
--
ALTER TABLE `users_confirmations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users_expanded`
--
ALTER TABLE `users_expanded`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users_remembered`
--
ALTER TABLE `users_remembered`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users_resets`
--
ALTER TABLE `users_resets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users_school`
--
ALTER TABLE `users_school`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
