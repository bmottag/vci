-- Payroll: confirmacion del Gross Salary con QuickBooks
-- gross_salary queda con el valor usado para los taxes (el calculado si coincide con QuickBooks, o el ingresado de QuickBooks).
-- gross_salary_calculated guarda el valor calculado por el sistema.
ALTER TABLE payroll_paystub
	ADD COLUMN gross_salary_calculated FLOAT NOT NULL DEFAULT 0 AFTER gross_salary,
	ADD COLUMN quickbooks_check TINYINT NOT NULL DEFAULT 0 COMMENT '0 = not confirmed, 1 = matches QuickBooks, 2 = QuickBooks value entered' AFTER gross_salary_calculated;
