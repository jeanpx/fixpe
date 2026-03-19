CREATE TABLE IF NOT EXISTS direct_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_user_id INT NOT NULL,
  provider_user_id INT NOT NULL,
  subject VARCHAR(160) NOT NULL,
  message TEXT NOT NULL,
  budget DECIMAL(10,2) NULL,
  status ENUM('pending','reviewed','accepted','rejected','closed') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_direct_requests_client FOREIGN KEY (client_user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_direct_requests_provider FOREIGN KEY (provider_user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS quotes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  request_id INT NOT NULL,
  provider_user_id INT NOT NULL,
  client_user_id INT NOT NULL,
  message TEXT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  delivery_days INT NULL,
  status ENUM('pending','accepted','rejected','withdrawn') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_quotes_request FOREIGN KEY (request_id) REFERENCES client_requests(id) ON DELETE CASCADE,
  CONSTRAINT fk_quotes_provider FOREIGN KEY (provider_user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_quotes_client FOREIGN KEY (client_user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT uq_quotes_provider_request UNIQUE (request_id, provider_user_id)
);
