-- Payroll: configuracion anual de CPP y CPP2 (valores de la CRA "CPP contribution rates, maximums and exemptions")
CREATE TABLE payroll_cpp_settings (
	id_cpp_setting INT NOT NULL AUTO_INCREMENT,
	year INT NOT NULL,
	basic_exemption DECIMAL(10,2) NOT NULL,
	contribution_rate DECIMAL(6,3) NOT NULL COMMENT 'Employee and employer contribution rate (%)',
	max_contribution DECIMAL(10,2) NOT NULL COMMENT 'Maximum annual employee and employer contribution',
	ympe DECIMAL(10,2) NOT NULL COMMENT 'Year''s maximum pensionable earnings (first ceiling)',
	yampe DECIMAL(10,2) NOT NULL COMMENT 'Year''s additional maximum pensionable earnings (second ceiling)',
	cpp2_rate DECIMAL(6,3) NOT NULL COMMENT 'CPP2 employee and employer contribution rate (%)',
	cpp2_max_contribution DECIMAL(10,2) NOT NULL COMMENT 'CPP2 maximum annual employee and employer contribution',
	fk_id_user INT NOT NULL,
	date_issue DATETIME NOT NULL,
	PRIMARY KEY (id_cpp_setting),
	UNIQUE KEY uk_payroll_cpp_settings_year (year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE payroll_paystub
	ADD COLUMN ee_cpp2 FLOAT NOT NULL DEFAULT 0 AFTER er_cpp,
	ADD COLUMN er_cpp2 FLOAT NOT NULL DEFAULT 0 AFTER ee_cpp2;

ALTER TABLE payroll_total_yearly
	ADD COLUMN total_year_ee_cpp2 FLOAT NOT NULL DEFAULT 0 AFTER total_year_er_cpp,
	ADD COLUMN total_year_er_cpp2 FLOAT NOT NULL DEFAULT 0 AFTER total_year_ee_cpp2;
