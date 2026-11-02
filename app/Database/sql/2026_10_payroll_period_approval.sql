-- Payroll: aprobacion de periodos semimensuales (24 por ano)
-- Los periodos existentes quedan aprobados (approved = 1); los que se generen desde la app nacen pendientes (0).
ALTER TABLE payroll_period
	ADD COLUMN approved TINYINT(1) NOT NULL DEFAULT 1 AFTER year_period,
	ADD COLUMN approved_by INT NULL AFTER approved,
	ADD COLUMN approved_date DATETIME NULL AFTER approved_by;
