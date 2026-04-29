-- AGRISORT-AI database schema
-- MySQL 8+

-- Shared-hosting note:
-- 1) Create/select your database in cPanel/phpMyAdmin first.
-- 2) Import this file while that database is selected.
-- Optional for local root setup:
-- CREATE DATABASE IF NOT EXISTS agrisort_ai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE agrisort_ai;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS trace_event_attachments;
DROP TABLE IF EXISTS trace_events;
DROP TABLE IF EXISTS trace_assignments;
DROP TABLE IF EXISTS lot_packages;
DROP TABLE IF EXISTS user_api_tokens;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS translation_cache;
DROP TABLE IF EXISTS api_keys;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS admin_settings;
DROP TABLE IF EXISTS cms_sections;
DROP TABLE IF EXISTS blog_posts;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS demo_requests;
DROP TABLE IF EXISTS lot_import_jobs;
DROP TABLE IF EXISTS lots;
DROP TABLE IF EXISTS partner_profiles;
DROP TABLE IF EXISTS rate_limits;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','partner','farmer','transporter','warehouse','seller','visitor') NOT NULL DEFAULT 'partner',
  status ENUM('pending','active','suspended') NOT NULL DEFAULT 'pending',
  vip_until DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role_status (role, status),
  KEY idx_users_vip_until (vip_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rate_limits (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  key_name VARCHAR(255) NOT NULL,
  attempts INT UNSIGNED NOT NULL DEFAULT 0,
  window_start INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_rate_limits_key_name (key_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE partner_profiles (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  organization_name VARCHAR(200) NOT NULL,
  representative_name VARCHAR(150) DEFAULT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  region VARCHAR(120) DEFAULT NULL,
  tax_code VARCHAR(120) DEFAULT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_partner_profiles_user_id (user_id),
  KEY idx_partner_profiles_org (organization_name),
  CONSTRAINT fk_partner_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE lots (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  partner_id INT UNSIGNED NOT NULL,
  lot_code VARCHAR(120) NOT NULL,
  produce_type VARCHAR(120) NOT NULL,
  origin_region VARCHAR(200) NOT NULL,
  harvest_date DATE NOT NULL,
  grade1_count INT UNSIGNED NOT NULL DEFAULT 0,
  grade2_count INT UNSIGNED NOT NULL DEFAULT 0,
  defect_count INT UNSIGNED NOT NULL DEFAULT 0,
  notes TEXT,
  image_path VARCHAR(255) DEFAULT NULL,
  publish_status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  qr_token VARCHAR(120) NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_lots_qr_token (qr_token),
  UNIQUE KEY uq_lots_partner_lot_code (partner_id, lot_code),
  KEY idx_lots_status (publish_status),
  KEY idx_lots_partner (partner_id),
  CONSTRAINT fk_lots_partner FOREIGN KEY (partner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE lot_packages (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  lot_id BIGINT UNSIGNED NOT NULL,
  package_code VARCHAR(120) NOT NULL,
  package_label VARCHAR(180) DEFAULT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  net_weight_kg DECIMAL(10,3) DEFAULT NULL,
  qr_token VARCHAR(120) NOT NULL,
  publish_status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  created_by_user_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_lot_packages_lot_package_code (lot_id, package_code),
  UNIQUE KEY uq_lot_packages_qr_token (qr_token),
  KEY idx_lot_packages_lot (lot_id),
  KEY idx_lot_packages_status (publish_status),
  CONSTRAINT fk_lot_packages_lot FOREIGN KEY (lot_id) REFERENCES lots(id) ON DELETE CASCADE,
  CONSTRAINT fk_lot_packages_creator FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE lot_import_jobs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  partner_id INT UNSIGNED NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  total_rows INT UNSIGNED NOT NULL DEFAULT 0,
  success_rows INT UNSIGNED NOT NULL DEFAULT 0,
  fail_rows INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_lot_import_partner (partner_id),
  CONSTRAINT fk_lot_import_partner FOREIGN KEY (partner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE demo_requests (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(150) NOT NULL,
  organization VARCHAR(200) NOT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  email VARCHAR(150) NOT NULL,
  province VARCHAR(120) DEFAULT NULL,
  message TEXT,
  status VARCHAR(30) NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_demo_requests_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contact_messages (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  subject VARCHAR(255) DEFAULT NULL,
  message TEXT NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_contact_messages_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE blog_posts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug VARCHAR(180) NOT NULL,
  title_vi VARCHAR(255) NOT NULL,
  title_en VARCHAR(255) DEFAULT NULL,
  content_vi MEDIUMTEXT NOT NULL,
  content_en MEDIUMTEXT,
  excerpt_vi TEXT,
  excerpt_en TEXT,
  thumbnail VARCHAR(255) DEFAULT NULL,
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  seo_title VARCHAR(255) DEFAULT NULL,
  seo_description TEXT,
  published_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_blog_posts_slug (slug),
  KEY idx_blog_posts_status (status, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cms_sections (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  section_key VARCHAR(80) NOT NULL,
  content_json_vi JSON NOT NULL,
  content_json_en JSON NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cms_sections_key (section_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_settings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  key_name VARCHAR(120) NOT NULL,
  key_value TEXT NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_admin_settings_key (key_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_resets (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_password_resets_user_id (user_id),
  KEY idx_password_resets_token_hash (token_hash),
  CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_api_tokens (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  token_name VARCHAR(120) NOT NULL DEFAULT 'android-app',
  last_used_at DATETIME DEFAULT NULL,
  expires_at DATETIME NOT NULL,
  revoked_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_user_api_tokens_hash (token_hash),
  KEY idx_user_api_tokens_user (user_id),
  KEY idx_user_api_tokens_expires (expires_at),
  KEY idx_user_api_tokens_revoked (revoked_at),
  CONSTRAINT fk_user_api_tokens_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE api_keys (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(180) NOT NULL,
  api_key_hash CHAR(64) NOT NULL,
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  last_used_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_api_keys_hash (api_key_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE translation_cache (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  source_text_hash CHAR(40) NOT NULL,
  source_lang VARCHAR(12) NOT NULL,
  target_lang VARCHAR(12) NOT NULL,
  translated_text MEDIUMTEXT NOT NULL,
  provider VARCHAR(50) NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_translation_cache_hash (source_text_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  actor_user_id INT UNSIGNED DEFAULT NULL,
  action VARCHAR(120) NOT NULL,
  entity VARCHAR(120) NOT NULL,
  entity_id BIGINT DEFAULT NULL,
  meta_json JSON DEFAULT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_audit_logs_actor (actor_user_id),
  KEY idx_audit_logs_entity (entity, entity_id),
  CONSTRAINT fk_audit_logs_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE trace_assignments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  entity_type ENUM('lot','package') NOT NULL,
  entity_id BIGINT UNSIGNED NOT NULL,
  stage_code VARCHAR(60) NOT NULL,
  actor_user_id INT UNSIGNED NOT NULL,
  assigned_by_user_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_trace_assignments_unique (entity_type, entity_id, stage_code, actor_user_id),
  KEY idx_trace_assignments_actor (actor_user_id),
  KEY idx_trace_assignments_entity (entity_type, entity_id),
  CONSTRAINT fk_trace_assignments_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_trace_assignments_assigned_by FOREIGN KEY (assigned_by_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE trace_events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  entity_type ENUM('lot','package') NOT NULL,
  entity_id BIGINT UNSIGNED NOT NULL,
  stage_code VARCHAR(60) NOT NULL,
  event_time DATETIME NOT NULL,
  location_name VARCHAR(255) NOT NULL,
  note TEXT,
  actor_user_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_trace_events_entity_time (entity_type, entity_id, event_time),
  KEY idx_trace_events_actor_time (actor_user_id, event_time),
  CONSTRAINT fk_trace_events_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE trace_event_attachments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  event_id BIGINT UNSIGNED NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  mime_type VARCHAR(120) NOT NULL,
  file_size INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_trace_event_attachments_event (event_id),
  CONSTRAINT fk_trace_event_attachments_event FOREIGN KEY (event_id) REFERENCES trace_events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO users (id, full_name, email, password_hash, role, status, created_at, updated_at)
VALUES
(1, 'System Admin', 'admin@gmail.com', '$2y$10$1.Ezl2FuMKpb.2xy9qfOXOQS7gJAvhbGhpubbSgkHMFRiOL6O1Afm', 'admin', 'active', NOW(), NOW())
ON DUPLICATE KEY UPDATE email = VALUES(email);

INSERT INTO admin_settings (key_name, key_value, updated_at) VALUES
('auto_publish_lot', '1', NOW()),
('smtp_host', 'smtp.gmail.com', NOW()),
('smtp_port', '587', NOW()),
('smtp_user', '', NOW()),
('smtp_pass', '', NOW()),
('smtp_from', 'admin@gmail.com', NOW()),
('qr_base_url', '', NOW()),
('google_translate_api_key', '', NOW())
ON DUPLICATE KEY UPDATE key_value = VALUES(key_value), updated_at = NOW();

INSERT INTO cms_sections (section_key, content_json_vi, content_json_en, updated_at) VALUES
('hero', JSON_OBJECT(
    'title','AGRISORT-AI: Hệ thống phân loại nông sản thông minh tích hợp truy xuất nguồn gốc QR',
    'body','Máy phân loại nông sản ứng dụng camera, thị giác máy tính và AI để đánh giá màu sắc, kích thước, độ chín và khuyết tật bề mặt.\nKết quả phân loại được lưu theo mã lô và gắn QR truy xuất nguồn gốc ngay trên cùng dây chuyền.\nPhù hợp với hợp tác xã, trạm thu mua, cơ sở sơ chế và doanh nghiệp nông sản quy mô nhỏ-vừa.'
), JSON_OBJECT(
    'title','AGRISORT-AI: Smart agricultural sorting system with integrated QR traceability',
    'body','A smart produce-sorting machine that combines industrial cameras, computer vision, and AI to assess color, size, ripeness, and surface defects.\nSorting results are stored by lot code and linked to QR traceability within the same processing line.\nDesigned for cooperatives, collection stations, post-harvest facilities, and small-to-medium agribusinesses.'
), NOW()),
('solution', JSON_OBJECT(
    'title','Giải pháp chuyên biệt cho bài toán nông sản Việt Nam',
    'body','Giảm phân loại thủ công chủ quan, tốn nhân công và thiếu đồng đều theo lô sau thu hoạch.\nPhù hợp phân khúc HTX, trạm thu mua và cơ sở sơ chế nhỏ-vừa khó đầu tư dây chuyền nhập khẩu.\nThiết kế gọn, dễ lắp đặt, dễ bảo trì và có thể tinh chỉnh theo từng loại nông sản theo mùa vụ.\nSố hóa kết quả phân loại theo mã lô để thống kê chất lượng, truy vết và hỗ trợ quyết định thu mua.'
), JSON_OBJECT(
    'title','A specialized solution for Vietnamese post-harvest operations',
    'body','Reduce subjective manual sorting, labor intensity, and inconsistent quality between lots.\nFit the budget and operating model of cooperatives, buying stations, and small-to-medium processing facilities.\nCompact, maintainable, and adaptable to different produce categories across seasonal harvest cycles.\nDigitize grading outcomes by lot code for quality statistics, traceability, and purchasing decisions.'
), NOW()),
('technology', JSON_OBJECT(
    'title','Pipeline xử lý và điều khiển thời gian thực',
    'body','Pipeline xử lý: Camera -> Xử lý ảnh -> Mô hình AI -> Quyết định phân loại -> Cơ cấu gạt.\nĐồng bộ quyết định AI với vị trí sản phẩm trên băng tải để gạt đúng Loại 1, Loại 2 và Lỗi.\nMục tiêu thời gian ra quyết định <= 200 ms để đáp ứng vận hành liên tục trong mùa vụ cao điểm.'
), JSON_OBJECT(
    'title','Processing pipeline and real-time control',
    'body','Processing flow: Camera -> Image Processing -> AI Model -> Sorting Decision -> Diverter Actuator.\nAI output is synchronized with conveyor position so each fruit is diverted into the correct grade channel.\nTarget decision latency is <= 200 ms to sustain continuous operation during peak harvest periods.'
), NOW()),
('impact', JSON_OBJECT(
    'title','Hiệu quả kinh tế - xã hội',
    'body','Độ chính xác thử nghiệm: 93-96% với mục tiêu kỹ thuật tối thiểu >= 90%.\nNăng suất định hướng: 1.000-2.000 quả/giờ tùy loại nông sản và cấu hình triển khai.\nGiảm 50-70% lao động phân loại trực tiếp, tăng độ đồng đều đầu ra và giảm sai sót chủ quan.\nTăng minh bạch chuỗi cung ứng nhờ dữ liệu theo mã lô, QR truy xuất và thông tin chất lượng đi kèm.'
), JSON_OBJECT(
    'title','Economic and social impact',
    'body','Pilot accuracy reaches 93-96%, with a baseline technical target of >= 90%.\nExpected throughput is 1,000-2,000 fruits per hour depending on crop type and deployment configuration.\nReduce direct sorting labor by 50-70% while improving consistency and reducing human subjectivity.\nStrengthen supply-chain transparency through lot-level data, QR traceability, and quality-linked records.'
), NOW()),
('swot', JSON_OBJECT(
    'title','Hệ sinh thái và chuỗi liên kết giá trị',
    'body','Hộ nông dân và nhà vườn có dữ liệu phân hạng rõ ràng để tăng giá trị nông sản sau thu hoạch.\nHTX và tổ hợp tác chuẩn hóa chất lượng theo mã lô để xây dựng thương hiệu vùng trồng và dễ thương lượng hơn.\nTrạm thu mua, cơ sở sơ chế, doanh nghiệp và siêu thị có đầu vào đồng đều kèm dữ liệu minh bạch để giảm rủi ro trả hàng.\nChuỗi dữ liệu vận hành xuyên suốt từ băng tải -> AI phân loại -> mã lô -> QR -> phân phối -> người mua cuối.'
), JSON_OBJECT(
    'title','Ecosystem and value-chain integration',
    'body','Farmers and growers receive clear grading records that support better post-harvest value capture.\nCooperatives can standardize lot quality, strengthen regional branding, and negotiate with more confidence.\nBuying stations, processors, distributors, and retailers receive consistent inputs with transparent supporting data.\nOperational data flows from conveyor sorting to lot code, QR traceability, distribution, and the end buyer.'
), NOW()),
('roadmap', JSON_OBJECT(
    'title','Lộ trình triển khai và phát triển thị trường',
    'body','0-6 tháng: pilot tại HTX, trạm thu mua và cơ sở sơ chế để kiểm chứng năng lực vận hành thực tế.\n6-24 tháng: mở rộng danh mục nông sản, chuẩn hóa quy trình triển khai và tiếp cận doanh nghiệp phân phối.\n2-5 năm: tích hợp IoT, phân tích dữ liệu chất lượng theo mùa vụ và mở rộng hợp tác sang thị trường ASEAN.'
), JSON_OBJECT(
    'title','Deployment and market roadmap',
    'body','0-6 months: run pilots with cooperatives, buying stations, and post-harvest facilities.\n6-24 months: expand supported produce categories, standardize rollout practices, and approach distribution partners.\n2-5 years: integrate IoT, seasonal quality analytics, and expand partnerships across ASEAN markets.'
), NOW()),
('team', JSON_OBJECT(
    'title','Đội ngũ thực hiện',
    'body','Nguyễn Khắc Tùng Lâm - Team Lead / System Integration\nNguyễn Đăng Quang - AI & Data Pipeline\nVõ Thị Mỹ - Business & Market Analysis\nTrần Đăng Khoa - Embedded & Control\nLê Hữu Trăng - Mechanical & Automation\nKS. Vũ Văn Dũng - Advisor\nThS. Nguyễn Thị Thành - Advisor'
), JSON_OBJECT(
    'title','Project team',
    'body','Nguyen Khac Tung Lam - Team Lead / System Integration\nNguyen Dang Quang - AI & Data Pipeline\nVo Thi My - Business & Market Analysis\nTran Dang Khoa - Embedded & Control\nLe Huu Trang - Mechanical & Automation\nVu Van Dung, B.Eng. - Advisor\nNguyen Thi Thanh, M.A. - Advisor'
), NOW()),
('feasibility', JSON_OBJECT(
    'title','Tính khả thi kỹ thuật',
    'body','Mục tiêu độ chính xác phân loại >= 90%, kết quả thử nghiệm hiện tại đạt 93-96%.\nThời gian ra quyết định mục tiêu <= 200 ms để kịp gạt đúng vị trí trên băng tải.\nNăng suất thiết kế hướng tới 1.000-2.000 quả/giờ tùy loại nông sản và cấu hình máy.\nQR được tạo theo mã lô trong <= 5 giây; dữ liệu lưu tối thiểu 12-24 tháng để tra cứu và hậu kiểm.\nHệ thống hướng tới vận hành an toàn với tủ điện, nút dừng khẩn và cơ chế báo trạng thái hoặc báo lỗi.'
), JSON_OBJECT(
    'title','Technical feasibility',
    'body','The classification target is >= 90% accuracy, while current pilot results reach 93-96%.\nTarget decision latency is <= 200 ms so the actuator can divert each item at the correct conveyor position.\nThe intended capacity is 1,000-2,000 fruits per hour depending on produce type and machine configuration.\nLot-level QR codes are generated within <= 5 seconds, with data retained for at least 12-24 months.\nThe system is designed for safe operation with an electrical cabinet, emergency stop, and status or fault alerts.'
), NOW()),
('contact', JSON_OBJECT(
    'title','Liên hệ dự án',
    'body','Email dự án: agrisort.ai.team@gmail.com\nWebsite chỉ công khai đầu mối dự án; số điện thoại và email cá nhân của thành viên hoặc giảng viên hướng dẫn không hiển thị công khai.'
), JSON_OBJECT(
    'title','Project contact',
    'body','Project email: agrisort.ai.team@gmail.com\nOnly the project mailbox is shown publicly; private phone numbers and personal email addresses are not displayed.'
), NOW())
ON DUPLICATE KEY UPDATE content_json_vi = VALUES(content_json_vi), content_json_en = VALUES(content_json_en), updated_at = NOW();

INSERT INTO blog_posts (slug, title_vi, title_en, content_vi, content_en, excerpt_vi, excerpt_en, status, published_at, created_at, updated_at)
VALUES
('agrisort-ai-launch',
 'AGRISORT-AI ra mắt hệ thống phân loại nông sản thông minh',
 'AGRISORT-AI launches smart agricultural grading system',
 'AGRISORT-AI tích hợp phân loại AI đa tiêu chí với truy xuất QR theo lô, hướng tới bài toán sau thu hoạch cho HTX và doanh nghiệp nông sản.',
 'AGRISORT-AI combines multi-criteria AI grading with lot-level QR traceability for post-harvest operations in cooperatives and agribusinesses.',
 'Giải pháp AI + QR cho phân loại và truy xuất lô nông sản.',
 'AI + QR solution for grading and lot traceability.',
 'published', NOW(), NOW(), NOW()),
('agrisort-ai-technical-profile',
 'Hồ sơ kỹ thuật AGRISORT-AI: AI, điều khiển thời gian thực và QR truy xuất',
 'AGRISORT-AI technical profile: AI, real-time control, and QR traceability',
 'Hệ thống vận hành theo pipeline Camera -> Xử lý ảnh -> AI -> Cơ cấu gạt. Mục tiêu kỹ thuật gồm độ chính xác 93-96%, thời gian quyết định <= 200 ms và năng suất 1.000-2.000 quả/giờ.',
 'The system runs on a Camera -> Vision -> AI -> Actuator pipeline. Target metrics include 93-96% accuracy, <= 200 ms decision latency, and 1,000-2,000 fruits/hour throughput.',
 'Thông số kỹ thuật cốt lõi cho triển khai thực tế.',
 'Core technical metrics for practical deployment.',
 'published', NOW(), NOW(), NOW()),
('agrisort-ai-market-roadmap',
 'Lộ trình thị trường AGRISORT-AI cho HTX và cơ sở sơ chế',
 'AGRISORT-AI market roadmap for cooperatives and post-harvest stations',
 'Giai đoạn 0-6 tháng tập trung pilot tại HTX/cơ sở sơ chế, 6-24 tháng mở rộng quy mô và chuẩn hóa dịch vụ, 2-5 năm tích hợp IoT và mở rộng thị trường ASEAN.',
 'The 0-6 month phase focuses on pilots at cooperatives and post-harvest stations, 6-24 months scales deployment and service standardization, and 2-5 years integrates IoT for ASEAN expansion.',
 'Lộ trình tăng trưởng từ pilot đến mở rộng khu vực.',
 'Growth roadmap from pilot rollout to regional expansion.',
 'published', NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE
title_vi = VALUES(title_vi),
title_en = VALUES(title_en),
content_vi = VALUES(content_vi),
content_en = VALUES(content_en),
excerpt_vi = VALUES(excerpt_vi),
excerpt_en = VALUES(excerpt_en),
status = VALUES(status),
published_at = VALUES(published_at),
updated_at = NOW();

-- Sample partner + sample lots for QR showcase
INSERT INTO users (full_name, email, password_hash, role, status, created_at, updated_at)
VALUES
('AGRISORT Sample Partner', 'partner.sample@agrisort.local', '$2y$10$1.Ezl2FuMKpb.2xy9qfOXOQS7gJAvhbGhpubbSgkHMFRiOL6O1Afm', 'partner', 'active', NOW(), NOW())
ON DUPLICATE KEY UPDATE
full_name = VALUES(full_name),
role = 'partner',
status = 'active',
updated_at = NOW();

INSERT INTO partner_profiles
(user_id, organization_name, representative_name, phone, address, region, tax_code, created_at, updated_at)
SELECT u.id, 'HTX Nông Sản Số Hóa UNETI', 'Đại diện dự án AGRISORT-AI', '0900000000', 'Hà Nội', 'Đồng bằng sông Hồng', '0109999999', NOW(), NOW()
FROM users u
WHERE u.email = 'partner.sample@agrisort.local'
ON DUPLICATE KEY UPDATE
organization_name = VALUES(organization_name),
representative_name = VALUES(representative_name),
phone = VALUES(phone),
address = VALUES(address),
region = VALUES(region),
tax_code = VALUES(tax_code),
updated_at = NOW();

INSERT INTO lots
(partner_id, lot_code, produce_type, origin_region, harvest_date, grade1_count, grade2_count, defect_count, notes, image_path, publish_status, qr_token, created_at, updated_at)
SELECT u.id, 'LOT-MANGO-001', 'Xoài Cát Chu', 'Đồng Tháp', '2026-02-15', 420, 110, 18, 'Lô mẫu truy xuất QR cho đối tác thu mua và siêu thị.', NULL, 'published', 'agrisort-lot-001', NOW(), NOW()
FROM users u
WHERE u.email = 'partner.sample@agrisort.local'
ON DUPLICATE KEY UPDATE
produce_type = VALUES(produce_type),
origin_region = VALUES(origin_region),
harvest_date = VALUES(harvest_date),
grade1_count = VALUES(grade1_count),
grade2_count = VALUES(grade2_count),
defect_count = VALUES(defect_count),
notes = VALUES(notes),
publish_status = 'published',
updated_at = NOW();

INSERT INTO lots
(partner_id, lot_code, produce_type, origin_region, harvest_date, grade1_count, grade2_count, defect_count, notes, image_path, publish_status, qr_token, created_at, updated_at)
SELECT u.id, 'LOT-AVOCADO-001', 'Bơ 034', 'Lâm Đồng', '2026-02-14', 300, 92, 11, 'Lô mẫu bơ 034 cho kênh phân phối nội địa.', NULL, 'published', 'agrisort-avocado-001', NOW(), NOW()
FROM users u
WHERE u.email = 'partner.sample@agrisort.local'
ON DUPLICATE KEY UPDATE
produce_type = VALUES(produce_type),
origin_region = VALUES(origin_region),
harvest_date = VALUES(harvest_date),
grade1_count = VALUES(grade1_count),
grade2_count = VALUES(grade2_count),
defect_count = VALUES(defect_count),
notes = VALUES(notes),
publish_status = 'published',
updated_at = NOW();

INSERT INTO lots
(partner_id, lot_code, produce_type, origin_region, harvest_date, grade1_count, grade2_count, defect_count, notes, image_path, publish_status, qr_token, created_at, updated_at)
SELECT u.id, 'LOT-DRAGON-001', 'Thanh long ruột đỏ', 'Bình Thuận', '2026-02-13', 510, 135, 27, 'Lô mẫu thanh long có độ chín đồng đều và tỷ lệ lỗi thấp.', NULL, 'published', 'agrisort-dragonfruit-001', NOW(), NOW()
FROM users u
WHERE u.email = 'partner.sample@agrisort.local'
ON DUPLICATE KEY UPDATE
produce_type = VALUES(produce_type),
origin_region = VALUES(origin_region),
harvest_date = VALUES(harvest_date),
grade1_count = VALUES(grade1_count),
grade2_count = VALUES(grade2_count),
defect_count = VALUES(defect_count),
notes = VALUES(notes),
publish_status = 'published',
updated_at = NOW();

INSERT INTO lots
(partner_id, lot_code, produce_type, origin_region, harvest_date, grade1_count, grade2_count, defect_count, notes, image_path, publish_status, qr_token, created_at, updated_at)
SELECT u.id, 'LOT-ORANGE-001', 'Cam sành', 'Vĩnh Long', '2026-02-12', 390, 101, 16, 'Lô mẫu cam sành cho hệ thống bán lẻ trong nước.', NULL, 'published', 'agrisort-orange-001', NOW(), NOW()
FROM users u
WHERE u.email = 'partner.sample@agrisort.local'
ON DUPLICATE KEY UPDATE
produce_type = VALUES(produce_type),
origin_region = VALUES(origin_region),
harvest_date = VALUES(harvest_date),
grade1_count = VALUES(grade1_count),
grade2_count = VALUES(grade2_count),
defect_count = VALUES(defect_count),
notes = VALUES(notes),
publish_status = 'published',
updated_at = NOW();

INSERT INTO lots
(partner_id, lot_code, produce_type, origin_region, harvest_date, grade1_count, grade2_count, defect_count, notes, image_path, publish_status, qr_token, created_at, updated_at)
SELECT u.id, 'LOT-BANANA-001', 'Chuối già Nam Mỹ', 'Tây Ninh', '2026-02-11', 610, 148, 22, 'Lô mẫu theo tiêu chuẩn đóng gói trước xuất khẩu.', NULL, 'published', 'agrisort-banana-001', NOW(), NOW()
FROM users u
WHERE u.email = 'partner.sample@agrisort.local'
ON DUPLICATE KEY UPDATE
produce_type = VALUES(produce_type),
origin_region = VALUES(origin_region),
harvest_date = VALUES(harvest_date),
grade1_count = VALUES(grade1_count),
grade2_count = VALUES(grade2_count),
defect_count = VALUES(defect_count),
notes = VALUES(notes),
publish_status = 'published',
updated_at = NOW();


