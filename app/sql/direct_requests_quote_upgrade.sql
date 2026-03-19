ALTER TABLE direct_requests
  ADD COLUMN quoted_amount DECIMAL(10,2) NULL AFTER budget,
  ADD COLUMN quoted_delivery_days INT NULL AFTER quoted_amount,
  ADD COLUMN provider_response TEXT NULL AFTER quoted_delivery_days;
