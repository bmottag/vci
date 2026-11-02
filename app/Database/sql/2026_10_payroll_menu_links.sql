-- Payroll: reactivar enlaces de paystubs (Accounting) y nombres de los enlaces nuevos (Settings)
UPDATE param_menu_links SET link_state = 1 WHERE id_link IN (79, 82, 83);
UPDATE param_menu_links SET link_name = 'CPP Settings' WHERE id_link = 114;
UPDATE param_menu_links SET link_name = 'Payroll Periods' WHERE id_link = 115;
