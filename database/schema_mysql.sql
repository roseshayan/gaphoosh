CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  mobile VARCHAR(20) NOT NULL,
  email VARCHAR(190) NULL,
  password_hash VARCHAR(255) NOT NULL,
  mobile_verified_at DATETIME NULL,
  status ENUM('active','blocked') NOT NULL DEFAULT 'active',
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY users_mobile_unique (mobile),
  UNIQUE KEY users_email_unique (email),
  KEY users_status_index (status),
  KEY users_admin_index (is_admin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS conversations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(160) NOT NULL DEFAULT 'گفتگوی جدید',
  model VARCHAR(190) NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  deleted_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY conversations_user_updated_index (user_id, updated_at),
  KEY conversations_deleted_index (deleted_at),
  CONSTRAINT conversations_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  conversation_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  role ENUM('system','user','assistant') NOT NULL,
  content LONGTEXT NOT NULL,
  model VARCHAR(190) NULL,
  prompt_tokens INT UNSIGNED NOT NULL DEFAULT 0,
  completion_tokens INT UNSIGNED NOT NULL DEFAULT 0,
  total_tokens INT UNSIGNED NOT NULL DEFAULT 0,
  meta JSON NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY messages_conversation_id_index (conversation_id, id),
  KEY messages_user_day_index (user_id, role, created_at),
  CONSTRAINT messages_conversation_fk FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
  CONSTRAINT messages_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS api_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  conversation_id BIGINT UNSIGNED NULL,
  model VARCHAR(190) NOT NULL,
  success TINYINT(1) NOT NULL DEFAULT 0,
  status_code INT NOT NULL DEFAULT 0,
  error_message TEXT NULL,
  total_tokens INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY api_logs_user_created_index (user_id, created_at),
  KEY api_logs_conversation_index (conversation_id),
  CONSTRAINT api_logs_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT api_logs_conversation_fk FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS otp_codes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  mobile VARCHAR(20) NOT NULL,
  purpose VARCHAR(40) NOT NULL DEFAULT 'register',
  code_hash VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  consumed_at DATETIME NULL,
  ip_address VARCHAR(45) NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY otp_mobile_purpose_index (mobile, purpose, consumed_at),
  KEY otp_expires_index (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  rate_key VARCHAR(190) NOT NULL,
  attempts INT UNSIGNED NOT NULL DEFAULT 0,
  window_start DATETIME NOT NULL,
  blocked_until DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY rate_limits_key_unique (rate_key),
  KEY rate_limits_blocked_index (blocked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS security_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  event_type VARCHAR(80) NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  mobile VARCHAR(20) NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(500) NULL,
  meta JSON NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY security_logs_event_index (event_type, created_at),
  KEY security_logs_mobile_index (mobile, created_at),
  KEY security_logs_user_index (user_id, created_at),
  CONSTRAINT security_logs_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS backup_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  file_path VARCHAR(500) NOT NULL,
  file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('success','failed') NOT NULL DEFAULT 'success',
  error_message TEXT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY backup_logs_created_index (created_at),
  KEY backup_logs_status_index (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
