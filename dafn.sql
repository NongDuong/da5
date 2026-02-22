-- Database: `dafn`
-- --------------------------------------------------------

--
-- Table structure for table `hopdong`
--

CREATE TABLE `hopdong` (
  `ma_hd` int(11) NOT NULL,
  `ngay_vay` date NOT NULL,
  `so_ngay` int(11) DEFAULT 0,
  `khach_hang` varchar(255) DEFAULT NULL,
  `sdt_kh` varchar(50) DEFAULT NULL,
  `tien_vay` float DEFAULT 0,
  `tien_theo_ky` float DEFAULT 0,
  `tong_ghi` float DEFAULT 0,
  `dong_lai` float DEFAULT 0,
  `gia_han` date DEFAULT NULL,
  `tat_toan` float DEFAULT 0,
  `ghi_chu` varchar(255) DEFAULT NULL,
  `nguoi_gioi_thieu` varchar(255) DEFAULT NULL,
  `sdt_gt` varchar(50) DEFAULT NULL,
  `trang_thai` enum('da_hoan_tat','dang_vay','no_xau') DEFAULT 'dang_vay',
  `is_deleted` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`ma_hd`),
  KEY `idx_hopdong_trangthai` (`trang_thai`),
  KEY `idx_hopdong_deleted` (`is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Note: Replace AUTO_INCREMENT with random ID generation logic in the application.

-- --------------------------------------------------------

--
-- Table structure for table `chitiet`
--

CREATE TABLE `chitiet` (
  `mact` int(11) NOT NULL AUTO_INCREMENT,
  `ma_hd` int(11) NOT NULL,
  `thang` date NOT NULL,
  `trang_thai` enum('chua_thu','da_thu','qua_han') DEFAULT 'chua_thu',
  `ghi_chu` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`mact`),
  KEY `idx_chitiet_mahd` (`ma_hd`),
  KEY `idx_chitiet_thang` (`thang`),
  CONSTRAINT `chitiet_ibfk_1` FOREIGN KEY (`ma_hd`) REFERENCES `hopdong` (`ma_hd`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
