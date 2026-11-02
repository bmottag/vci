-- Payroll: tablas de retencion de impuestos de la CRA (Federal y Provincial, semi-monthly 24 periodos) por ano
CREATE TABLE payroll_tax_tables (
	id_tax_table INT NOT NULL AUTO_INCREMENT,
	year INT NOT NULL,
	table_type VARCHAR(10) NOT NULL COMMENT 'federal | provincial',
	file_name VARCHAR(255) NOT NULL,
	total_rows INT NOT NULL,
	fk_id_user INT NOT NULL,
	date_issue DATETIME NOT NULL,
	PRIMARY KEY (id_tax_table),
	UNIQUE KEY uk_payroll_tax_tables_year_type (year, table_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Una fila por rango de pago: pay_from <= gross < pay_less_than. cc0..cc10 = impuesto por claim code (NULL = "*", no aplica)
CREATE TABLE payroll_tax_table_rows (
	id_tax_table_row INT NOT NULL AUTO_INCREMENT,
	fk_id_tax_table INT NOT NULL,
	pay_from DECIMAL(10,2) NOT NULL,
	pay_less_than DECIMAL(10,2) NOT NULL,
	cc0 DECIMAL(10,2) NULL,
	cc1 DECIMAL(10,2) NULL,
	cc2 DECIMAL(10,2) NULL,
	cc3 DECIMAL(10,2) NULL,
	cc4 DECIMAL(10,2) NULL,
	cc5 DECIMAL(10,2) NULL,
	cc6 DECIMAL(10,2) NULL,
	cc7 DECIMAL(10,2) NULL,
	cc8 DECIMAL(10,2) NULL,
	cc9 DECIMAL(10,2) NULL,
	cc10 DECIMAL(10,2) NULL,
	PRIMARY KEY (id_tax_table_row),
	KEY idx_payroll_tax_table_rows_range (fk_id_tax_table, pay_from),
	CONSTRAINT fk_payroll_tax_table_rows_table FOREIGN KEY (fk_id_tax_table) REFERENCES payroll_tax_tables (id_tax_table) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Paystub: claim code usado y el TAX dividido en Federal y Provincial (tax sigue siendo la suma)
ALTER TABLE payroll_paystub
	ADD COLUMN claim_code TINYINT NULL AFTER tax,
	ADD COLUMN federal_tax FLOAT NOT NULL DEFAULT 0 AFTER claim_code,
	ADD COLUMN provincial_tax FLOAT NOT NULL DEFAULT 0 AFTER federal_tax;

-- Enlace en el menu Settings (8) junto a CPP/EI Settings, con acceso para el super admin (99)
INSERT INTO param_menu_links (fk_id_menu, link_name, link_url, link_icon, `order`, date_issue, link_state, link_type)
VALUES (8, 'Tax Tables', 'payroll/taxTables', 'fa-money', 24, NOW(), 1, 1);

INSERT INTO param_menu_permisos (fk_id_menu, fk_id_link, fk_id_rol)
VALUES (8, LAST_INSERT_ID(), 99);
