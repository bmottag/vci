-- Payroll: configuracion anual de EI (valores de la CRA "Federal EI premium rates and maximums")
CREATE TABLE payroll_ei_settings (
	id_ei_setting INT NOT NULL AUTO_INCREMENT,
	year INT NOT NULL,
	max_insurable_earnings DECIMAL(10,2) NOT NULL COMMENT 'Maximum annual insurable earnings',
	rate DECIMAL(6,3) NOT NULL COMMENT 'Employee premium rate (%), the employer pays 1.4 times',
	fk_id_user INT NOT NULL,
	date_issue DATETIME NOT NULL,
	PRIMARY KEY (id_ei_setting),
	UNIQUE KEY uk_payroll_ei_settings_year (year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Enlace en el menu Settings (8) junto a CPP Settings, con acceso para el super admin (99)
INSERT INTO param_menu_links (fk_id_menu, link_name, link_url, link_icon, `order`, date_issue, link_state, link_type)
VALUES (8, 'EI Settings', 'payroll/eiSettings', 'fa-money', 24, NOW(), 1, 1);

INSERT INTO param_menu_permisos (fk_id_menu, fk_id_link, fk_id_rol)
VALUES (8, LAST_INSERT_ID(), 99);
