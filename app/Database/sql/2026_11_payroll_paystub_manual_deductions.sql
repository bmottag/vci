-- Payroll: CPP, CPP2, EI y Total Tax (Federal + Provincial) del empleado se calculan, pero el usuario puede editarlos a mano.
-- Federal y Provincial tax siempre quedan con el valor de las tablas de la CRA.
-- manual_deductions guarda los campos editados a mano, separados por coma (ee_cpp,ee_cpp2,ee_ei,tax).
-- NULL = todos calculados. Los del employer siguen la logica: ER CPP = EE CPP, ER CPP2 = EE CPP2, ER EI = EE EI x 1.4.
ALTER TABLE payroll_paystub
	ADD COLUMN manual_deductions VARCHAR(100) NULL DEFAULT NULL AFTER provincial_tax;
