-- Payroll: enlace "Remittance" en el menu Accounting, solo para Super Admin (99) y Accounting (3)
INSERT INTO param_menu_links (fk_id_menu, link_name, link_url, link_icon, `order`, date_issue, link_state, link_type)
VALUES (29, 'Remittance', 'payroll/remittance', 'fa-university', 25, NOW(), 1, 1);

SET @idLink = LAST_INSERT_ID();

INSERT INTO param_menu_permisos (fk_id_menu, fk_id_link, fk_id_rol) VALUES
(29, @idLink, 99),
(29, @idLink, 3);
