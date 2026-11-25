-- Payroll: Bank Time
-- 1) Horas con decimales exactos (FLOAT acumula errores de redondeo en los saldos)
ALTER TABLE payroll_bank_time
	MODIFY time_in  DECIMAL(10,3) NOT NULL DEFAULT 0,
	MODIFY time_out DECIMAL(10,3) NOT NULL DEFAULT 0,
	MODIFY balance  DECIMAL(10,3) NOT NULL DEFAULT 0;

ALTER TABLE payroll_paystub
	MODIFY actual_bank_time_balance DECIMAL(10,3) NOT NULL DEFAULT 0;

-- 2) Enlace "Bank Time" en el menu Accounting, solo para Super Admin (99) y Accounting (3)
INSERT INTO param_menu_links (fk_id_menu, link_name, link_url, link_icon, `order`, date_issue, link_state, link_type)
VALUES (29, 'Bank Time', 'payroll/bankTime', 'fa-clock-o', 23, NOW(), 1, 1);

SET @idLink = LAST_INSERT_ID();

INSERT INTO param_menu_permisos (fk_id_menu, fk_id_link, fk_id_rol) VALUES
(29, @idLink, 99),
(29, @idLink, 3);
