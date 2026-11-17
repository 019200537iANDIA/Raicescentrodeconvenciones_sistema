-- Raíces — esquema lógico-físico inicial para MySQL 8
-- Revisión: v1, reglas confirmadas por el investigador
-- Este archivo es la referencia para las futuras migraciones Laravel.
-- No contiene datos reales ni debe ejecutarse contra una base productiva.

CREATE DATABASE IF NOT EXISTS raices_v1
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE raices_v1;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('propietario','gerente') NOT NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  email_verified_at TIMESTAMP NULL,
  remember_token VARCHAR(100) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE event_types (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(500) NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE clients (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(180) NOT NULL,
  mobile VARCHAR(30) NOT NULL,
  dni_encrypted TEXT NULL COMMENT 'Cifrar en Laravel; no almacenar DNI plano',
  address_encrypted TEXT NULL COMMENT 'Cifrar en Laravel; finalidad de validación/descuento',
  district VARCHAR(120) NULL COMMENT 'Usado para reglas de residencia/descuento',
  client_type ENUM('nuevo','recurrente') NOT NULL DEFAULT 'nuevo',
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  deleted_at TIMESTAMP NULL,
  deleted_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX ix_clients_mobile (mobile),
  INDEX ix_clients_name (full_name),
  CONSTRAINT fk_clients_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_clients_deleted_by FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE venues (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  venue_type ENUM('area_verde','salon_principal','medio_salon','mezanine','escenario','otro') NOT NULL,
  capacity INT UNSIGNED NULL,
  access_restrictions TEXT NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE venue_configurations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(140) NOT NULL UNIQUE,
  description TEXT NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE venue_configuration_items (
  venue_configuration_id BIGINT UNSIGNED NOT NULL,
  venue_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (venue_configuration_id, venue_id),
  CONSTRAINT fk_vci_configuration FOREIGN KEY (venue_configuration_id) REFERENCES venue_configurations(id) ON DELETE CASCADE,
  CONSTRAINT fk_vci_venue FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE services (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  category VARCHAR(100) NOT NULL,
  description TEXT NULL,
  unit_label VARCHAR(50) NOT NULL DEFAULT 'unidad',
  is_optional BOOLEAN NOT NULL DEFAULT TRUE,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX ix_services_name (name)
) ENGINE=InnoDB;

CREATE TABLE packages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name ENUM('basico','estandar','premium') NOT NULL UNIQUE,
  description TEXT NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE package_services (
  package_id BIGINT UNSIGNED NOT NULL,
  service_id BIGINT UNSIGNED NOT NULL,
  default_quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
  notes VARCHAR(500) NULL,
  PRIMARY KEY (package_id, service_id),
  CONSTRAINT fk_package_services_package FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
  CONSTRAINT fk_package_services_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT,
  CONSTRAINT chk_package_service_qty CHECK (default_quantity >= 0)
) ENGINE=InnoDB;

CREATE TABLE service_rates (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  service_id BIGINT UNSIGNED NOT NULL,
  currency_code CHAR(3) NOT NULL COMMENT 'Código ISO; confirmar moneda al cargar catálogo',
  amount DECIMAL(12,2) NOT NULL,
  valid_from DATE NOT NULL,
  valid_until DATE NULL,
  approved_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_service_rates_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT,
  CONSTRAINT fk_service_rates_approver FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT chk_service_rate_amount CHECK (amount >= 0),
  INDEX ix_service_rates_validity (service_id, valid_from, valid_until)
) ENGINE=InnoDB;

CREATE TABLE pricing_rules (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  rule_type ENUM('temporada','dia_semana','horario','cantidad_personas','grupo_edad','residencia','promocion','otro') NOT NULL,
  criteria JSON NOT NULL COMMENT 'Criterios de aplicación; no codificar las reglas en la aplicación',
  adjustment_type ENUM('percentage','fixed_amount') NOT NULL,
  adjustment_value DECIMAL(8,2) NOT NULL,
  priority SMALLINT NOT NULL DEFAULT 100,
  valid_from DATE NULL,
  valid_until DATE NULL,
  approved_by BIGINT UNSIGNED NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_pricing_rules_approver FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX ix_pricing_rules_active (is_active, rule_type, priority)
) ENGINE=InnoDB;

CREATE TABLE inquiries (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id BIGINT UNSIGNED NOT NULL,
  event_type_id BIGINT UNSIGNED NULL,
  source_channel ENUM('whatsapp','facebook','instagram','tiktok','llamada','presencial','otro') NOT NULL,
  event_date_requested DATE NULL,
  budget_reference DECIMAL(12,2) NULL,
  summary TEXT NULL,
  status ENUM('nueva','en_atencion','cotizada','reservada','ganada','perdida','cerrada') NOT NULL DEFAULT 'nueva',
  assigned_user_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  deleted_at TIMESTAMP NULL,
  deleted_by BIGINT UNSIGNED NULL,
  CONSTRAINT fk_inquiries_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
  CONSTRAINT fk_inquiries_event_type FOREIGN KEY (event_type_id) REFERENCES event_types(id) ON DELETE SET NULL,
  CONSTRAINT fk_inquiries_assigned_user FOREIGN KEY (assigned_user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_inquiries_deleted_by FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX ix_inquiries_status_date (status, created_at),
  INDEX ix_inquiries_event_date (event_date_requested)
) ENGINE=InnoDB;

CREATE TABLE interactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  inquiry_id BIGINT UNSIGNED NOT NULL,
  quote_id BIGINT UNSIGNED NULL,
  user_id BIGINT UNSIGNED NULL,
  occurred_at DATETIME NOT NULL,
  channel VARCHAR(40) NOT NULL,
  outcome ENUM('sin_respuesta','pendiente','interesado','acepta','rechaza','solicita_cambios','otro') NOT NULL,
  notes TEXT NULL,
  next_action VARCHAR(255) NULL,
  next_action_at DATETIME NULL,
  reminder_sent_at DATETIME NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_interactions_inquiry FOREIGN KEY (inquiry_id) REFERENCES inquiries(id) ON DELETE RESTRICT,
  CONSTRAINT fk_interactions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX ix_interactions_next_action (next_action_at, reminder_sent_at)
) ENGINE=InnoDB;

CREATE TABLE quotes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  inquiry_id BIGINT UNSIGNED NOT NULL,
  parent_quote_id BIGINT UNSIGNED NULL COMMENT 'Agrupa alternativas de paquete o alcance para una misma oportunidad',
  quote_number VARCHAR(40) NOT NULL UNIQUE,
  event_type_id BIGINT UNSIGNED NULL,
  event_date DATE NOT NULL,
  venue_configuration_id BIGINT UNSIGNED NULL,
  adult_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  child_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  quote_status ENUM('borrador','enviada','aceptada','rechazada','vencida','convertida','cancelada') NOT NULL DEFAULT 'borrador',
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  deleted_at TIMESTAMP NULL,
  deleted_by BIGINT UNSIGNED NULL,
  CONSTRAINT fk_quotes_inquiry FOREIGN KEY (inquiry_id) REFERENCES inquiries(id) ON DELETE RESTRICT,
  CONSTRAINT fk_quotes_parent FOREIGN KEY (parent_quote_id) REFERENCES quotes(id) ON DELETE SET NULL,
  CONSTRAINT fk_quotes_event_type FOREIGN KEY (event_type_id) REFERENCES event_types(id) ON DELETE SET NULL,
  CONSTRAINT fk_quotes_venue_config FOREIGN KEY (venue_configuration_id) REFERENCES venue_configurations(id) ON DELETE SET NULL,
  CONSTRAINT fk_quotes_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_quotes_deleted_by FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT chk_quotes_attendees CHECK (adult_count + child_count > 0),
  INDEX ix_quotes_event_date (event_date),
  INDEX ix_quotes_status (quote_status)
) ENGINE=InnoDB;

ALTER TABLE interactions
  ADD CONSTRAINT fk_interactions_quote FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE SET NULL;

CREATE TABLE quote_versions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  quote_id BIGINT UNSIGNED NOT NULL,
  version_number SMALLINT UNSIGNED NOT NULL,
  package_id BIGINT UNSIGNED NULL,
  created_by BIGINT UNSIGNED NULL,
  reason VARCHAR(500) NULL,
  currency_code CHAR(3) NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
  discount_total DECIMAL(12,2) NOT NULL DEFAULT 0,
  total DECIMAL(12,2) NOT NULL DEFAULT 0,
  price_per_adult DECIMAL(12,2) NULL,
  price_per_child DECIMAL(12,2) NULL,
  advance_percent DECIMAL(5,2) NOT NULL,
  advance_amount DECIMAL(12,2) NOT NULL,
  sent_at DATETIME NULL,
  valid_until DATETIME NOT NULL COMMENT '15 días desde emisión en v1',
  customer_accepted_at DATETIME NULL,
  pdf_path VARCHAR(500) NULL,
  is_current BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_quote_versions_quote FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE RESTRICT,
  CONSTRAINT fk_quote_versions_package FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE SET NULL,
  CONSTRAINT fk_quote_versions_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT chk_quote_versions_advance_percent CHECK (advance_percent BETWEEN 10 AND 30),
  CONSTRAINT chk_quote_versions_amounts CHECK (subtotal >= 0 AND discount_total >= 0 AND total >= 0 AND advance_amount >= 0),
  UNIQUE KEY uq_quote_version (quote_id, version_number),
  INDEX ix_quote_versions_expiration (valid_until, is_current)
) ENGINE=InnoDB;

CREATE TABLE quote_lines (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  quote_version_id BIGINT UNSIGNED NOT NULL,
  service_id BIGINT UNSIGNED NULL,
  description_snapshot VARCHAR(500) NOT NULL,
  quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
  internal_unit_price DECIMAL(12,2) NOT NULL,
  line_discount DECIMAL(12,2) NOT NULL DEFAULT 0,
  line_total DECIMAL(12,2) NOT NULL,
  display_quantity_to_customer BOOLEAN NOT NULL DEFAULT TRUE,
  sort_order SMALLINT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_quote_lines_version FOREIGN KEY (quote_version_id) REFERENCES quote_versions(id) ON DELETE RESTRICT,
  CONSTRAINT fk_quote_lines_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
  CONSTRAINT chk_quote_lines_values CHECK (quantity >= 0 AND internal_unit_price >= 0 AND line_discount >= 0 AND line_total >= 0),
  INDEX ix_quote_lines_version (quote_version_id, sort_order)
) ENGINE=InnoDB;

CREATE TABLE quote_adjustments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  quote_version_id BIGINT UNSIGNED NOT NULL,
  pricing_rule_id BIGINT UNSIGNED NULL,
  adjustment_type ENUM('descuento','recargo','correccion') NOT NULL,
  reason VARCHAR(500) NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  authorized_by BIGINT UNSIGNED NOT NULL,
  applied_at DATETIME NOT NULL,
  created_at TIMESTAMP NULL,
  CONSTRAINT fk_quote_adjustments_version FOREIGN KEY (quote_version_id) REFERENCES quote_versions(id) ON DELETE RESTRICT,
  CONSTRAINT fk_quote_adjustments_rule FOREIGN KEY (pricing_rule_id) REFERENCES pricing_rules(id) ON DELETE SET NULL,
  CONSTRAINT fk_quote_adjustments_authorizer FOREIGN KEY (authorized_by) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT chk_quote_adjustments_amount CHECK (amount >= 0)
) ENGINE=InnoDB;

CREATE TABLE events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id BIGINT UNSIGNED NOT NULL,
  quote_id BIGINT UNSIGNED NULL COMMENT 'NULL si se registra reserva directa',
  quote_version_id BIGINT UNSIGNED NULL,
  event_type_id BIGINT UNSIGNED NULL,
  venue_configuration_id BIGINT UNSIGNED NULL,
  event_date DATE NOT NULL,
  start_time TIME NULL,
  end_time TIME NULL,
  adult_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  child_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('pending_advance','reserved','in_preparation','completed','cancelled','released') NOT NULL DEFAULT 'pending_advance',
  hold_expires_at DATETIME NULL COMMENT 'Vence a los 15 días si no se registra adelanto',
  advance_paid_at DATETIME NULL,
  created_by BIGINT UNSIGNED NULL,
  cancelled_at DATETIME NULL,
  cancellation_reason TEXT NULL,
  released_at DATETIME NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  deleted_at TIMESTAMP NULL,
  deleted_by BIGINT UNSIGNED NULL,
  active_event_date DATE GENERATED ALWAYS AS (
    CASE WHEN status IN ('cancelled','released','completed') THEN NULL ELSE event_date END
  ) STORED,
  UNIQUE KEY uq_one_active_event_per_date_v1 (active_event_date),
  CONSTRAINT fk_events_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
  CONSTRAINT fk_events_quote FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE SET NULL,
  CONSTRAINT fk_events_quote_version FOREIGN KEY (quote_version_id) REFERENCES quote_versions(id) ON DELETE SET NULL,
  CONSTRAINT fk_events_event_type FOREIGN KEY (event_type_id) REFERENCES event_types(id) ON DELETE SET NULL,
  CONSTRAINT fk_events_venue_config FOREIGN KEY (venue_configuration_id) REFERENCES venue_configurations(id) ON DELETE SET NULL,
  CONSTRAINT fk_events_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_events_deleted_by FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT chk_events_attendees CHECK (adult_count + child_count >= 0),
  CONSTRAINT chk_events_times CHECK (end_time IS NULL OR start_time IS NULL OR end_time > start_time),
  INDEX ix_events_status_date (status, event_date),
  INDEX ix_events_expiring_hold (status, hold_expires_at, advance_paid_at)
) ENGINE=InnoDB;

CREATE TABLE contracts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id BIGINT UNSIGNED NOT NULL,
  version_number SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  status ENUM('borrador','vigente','reemplazado','cancelado') NOT NULL DEFAULT 'borrador',
  terms_snapshot JSON NOT NULL,
  total_amount DECIMAL(12,2) NOT NULL,
  document_path VARCHAR(500) NULL,
  issued_at DATETIME NULL,
  signed_at DATETIME NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_contracts_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE RESTRICT,
  CONSTRAINT fk_contracts_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  UNIQUE KEY uq_contract_version (event_id, version_number),
  CONSTRAINT chk_contract_total CHECK (total_amount >= 0)
) ENGINE=InnoDB;

CREATE TABLE installments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id BIGINT UNSIGNED NOT NULL,
  due_date DATE NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  status ENUM('pendiente','parcial','pagada','renegociada','cancelada') NOT NULL DEFAULT 'pendiente',
  notes VARCHAR(500) NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_installments_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE RESTRICT,
  CONSTRAINT fk_installments_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT chk_installment_amount CHECK (amount > 0),
  INDEX ix_installments_due (due_date, status)
) ENGINE=InnoDB;

CREATE TABLE payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id BIGINT UNSIGNED NOT NULL,
  installment_id BIGINT UNSIGNED NULL,
  payment_type ENUM('adelanto','cuota','saldo','otro') NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  currency_code CHAR(3) NOT NULL,
  method VARCHAR(40) NOT NULL COMMENT 'Método real de pago a configurar',
  receipt_path VARCHAR(500) NULL,
  paid_at DATETIME NOT NULL,
  recorded_by BIGINT UNSIGNED NULL,
  status ENUM('registrado','anulado','reversado') NOT NULL DEFAULT 'registrado',
  notes VARCHAR(500) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_payments_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE RESTRICT,
  CONSTRAINT fk_payments_installment FOREIGN KEY (installment_id) REFERENCES installments(id) ON DELETE SET NULL,
  CONSTRAINT fk_payments_recorder FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT chk_payment_amount CHECK (amount > 0),
  INDEX ix_payments_event_date (event_id, paid_at)
) ENGINE=InnoDB;

CREATE TABLE refunds (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id BIGINT UNSIGNED NOT NULL,
  source_payment_id BIGINT UNSIGNED NULL,
  amount DECIMAL(12,2) NOT NULL,
  reason TEXT NOT NULL,
  status ENUM('solicitada','aprobada','rechazada','pagada') NOT NULL DEFAULT 'solicitada',
  requested_by BIGINT UNSIGNED NULL,
  approved_by BIGINT UNSIGNED NULL,
  refunded_at DATETIME NULL,
  receipt_path VARCHAR(500) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_refunds_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE RESTRICT,
  CONSTRAINT fk_refunds_payment FOREIGN KEY (source_payment_id) REFERENCES payments(id) ON DELETE SET NULL,
  CONSTRAINT fk_refunds_requester FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_refunds_approver FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT chk_refund_amount CHECK (amount > 0)
) ENGINE=InnoDB;

CREATE TABLE suppliers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  service_type VARCHAR(120) NOT NULL,
  contact_name VARCHAR(160) NULL,
  contact_phone VARCHAR(30) NULL,
  notes TEXT NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  deleted_at TIMESTAMP NULL,
  deleted_by BIGINT UNSIGNED NULL,
  CONSTRAINT fk_suppliers_deleted_by FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX ix_suppliers_name (name)
) ENGINE=InnoDB;

CREATE TABLE supplier_assignments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id BIGINT UNSIGNED NOT NULL,
  supplier_id BIGINT UNSIGNED NOT NULL,
  item_or_service VARCHAR(200) NOT NULL,
  specification TEXT NULL COMMENT 'Ej.: color de servilleta, letras iluminadas, cantidad y modelo',
  quantity DECIMAL(10,2) NULL,
  order_deadline DATETIME NULL,
  delivery_at DATETIME NULL,
  work_schedule VARCHAR(250) NULL,
  status ENUM('pendiente','solicitado','confirmado','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
  change_notes TEXT NULL,
  assigned_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_supplier_assignments_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE RESTRICT,
  CONSTRAINT fk_supplier_assignments_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT,
  CONSTRAINT fk_supplier_assignments_user FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX ix_supplier_assignment_deadline (order_deadline, status)
) ENGINE=InnoDB;

CREATE TABLE staff (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(160) NOT NULL,
  function_name VARCHAR(120) NULL,
  phone VARCHAR(30) NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  deleted_at TIMESTAMP NULL,
  deleted_by BIGINT UNSIGNED NULL,
  CONSTRAINT fk_staff_deleted_by FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE staff_assignments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id BIGINT UNSIGNED NOT NULL,
  staff_id BIGINT UNSIGNED NOT NULL,
  event_role VARCHAR(120) NOT NULL,
  notes VARCHAR(500) NULL,
  status ENUM('propuesto','confirmado','reemplazado','cancelado','completado') NOT NULL DEFAULT 'propuesto',
  replacement_for_assignment_id BIGINT UNSIGNED NULL,
  assigned_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_staff_assignments_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE RESTRICT,
  CONSTRAINT fk_staff_assignments_staff FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE RESTRICT,
  CONSTRAINT fk_staff_assignments_replacement FOREIGN KEY (replacement_for_assignment_id) REFERENCES staff_assignments(id) ON DELETE SET NULL,
  CONSTRAINT fk_staff_assignments_user FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE event_tasks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id BIGINT UNSIGNED NOT NULL,
  task_type ENUM('pedido_proveedor','especificacion','preparacion','cambio_cliente','otro') NOT NULL,
  title VARCHAR(180) NOT NULL,
  description TEXT NULL,
  responsible_user_id BIGINT UNSIGNED NULL,
  due_at DATETIME NULL,
  status ENUM('pendiente','en_proceso','completada','cancelada') NOT NULL DEFAULT 'pendiente',
  completed_at DATETIME NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_event_tasks_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE RESTRICT,
  CONSTRAINT fk_event_tasks_user FOREIGN KEY (responsible_user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX ix_event_tasks_due (due_at, status)
) ENGINE=InnoDB;

CREATE TABLE system_settings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(120) NOT NULL,
  setting_value JSON NOT NULL,
  valid_from DATETIME NOT NULL,
  valid_until DATETIME NULL,
  approved_by BIGINT UNSIGNED NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_system_settings_approver FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_system_settings_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX ix_system_settings_key_valid (setting_key, valid_from, valid_until)
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  action ENUM('create','update','soft_delete','restore','status_change','price_adjustment','login','other') NOT NULL,
  entity_type VARCHAR(120) NOT NULL,
  entity_id BIGINT UNSIGNED NOT NULL,
  before_data JSON NULL,
  after_data JSON NULL,
  reason VARCHAR(500) NULL,
  occurred_at DATETIME NOT NULL,
  CONSTRAINT fk_audit_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX ix_audit_entity (entity_type, entity_id, occurred_at),
  INDEX ix_audit_user_date (user_id, occurred_at)
) ENGINE=InnoDB;

-- Semillas de catálogos; cargar usuarios, reglas tarifarias y precios reales de forma controlada.
INSERT INTO event_types (name, description) VALUES
 ('Matrimonio',NULL),('Quince años',NULL),('Cumpleaños',NULL),('Aniversario corporativo',NULL),
 ('Promoción',NULL),('Graduación',NULL),('Otro',NULL);

INSERT INTO packages (name, description) VALUES
 ('basico','Paquete comercial básico'),('estandar','Paquete comercial estándar'),('premium','Paquete comercial premium');

INSERT INTO system_settings (setting_key, setting_value, valid_from) VALUES
 ('quote_validity_days', JSON_OBJECT('days',15), NOW()),
 ('advance_percent_range', JSON_OBJECT('minimum',10,'maximum',30), NOW()),
 ('maximum_active_events_per_date', JSON_OBJECT('count',1), NOW());

-- V1: el sistema reemplaza el generador HTML. La importación histórica desde Drive/Excel se define aparte.
-- V1: no incluir inventario ni control de turnos, horas, reemplazos o pagos de personal.
-- V1: crear evento en estado pending_advance solo cuando se solicite separar/reservar una cotización;
--     si no se registra adelanto al vencer el plazo aplicable, cambiar a released y liberar la fecha.
