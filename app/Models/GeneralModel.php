<?php
namespace App\Models;

use CodeIgniter\Model;

class GeneralModel extends Model
{

	/**
	 * Consulta BASICA A UNA TABLA
	 * @param $TABLA: nombre de la tabla
	 * @param $ORDEN: orden por el que se quiere organizar los datos
	 * @param $COLUMNA: nombre de la columna en la tabla para realizar un filtro (NO ES OBLIGATORIO)
	 * @param $VALOR: valor de la columna para realizar un filtro (NO ES OBLIGATORIO)
	 * @since 8/11/2016
	 */
    public function get_basic_search($arrData)
    {
        $builder = $this->db->table($arrData["table"]);

        // Filtro opcional
        if (isset($arrData["id"]) && $arrData["id"] != 'x') {
            $builder->where($arrData["column"], $arrData["id"]);
        }

        // Orden
        $builder->orderBy($arrData["order"], "ASC");

        $query = $builder->get();

        $result = $query->getResultArray();

        return !empty($result) ? $result : false;
    }

	/**
	 * Update field in a table
	 * @since 11/12/2016
	 */
	public function updateRecord(array $arrDatos)
	{
		$builder = $this->db->table($arrDatos["table"]);
		$data = [
			$arrDatos["column"] => $arrDatos["value"]
		];
		$builder->where($arrDatos["primaryKey"], $arrDatos["id"]);

		return $builder->update($data);
	}

	/**
	 * Delete Record
	 * @since 5/12/2016
	 */
	public function deleteRecord($arrDatos)
	{
		return $this->db->table($arrDatos["table"])
						->where($arrDatos["primaryKey"], $arrDatos["id"])
						->delete();
	}

	/**
	 * Verify if the user already exist by specific column
	 * @author BMOTTAG
	 * @since  8/11/2016
	 * @review 31/01/2022
	 */
	public function verifyUser($arrData)
	{
		$builder = $this->db->table("user");
		$builder->where($arrData["column"], $arrData["value"]);

		return $builder->countAllResults() > 0;
	}

	/**
	 * Lista de roles
	 * Modules: ROL
	 * @since 30/3/2020
	 */
    public function get_roles(array $arrData)
    {
        $builder = $this->db->table('param_rol');

        // Filtro opcional
        if (isset($arrData['filtro'])) {
            $builder->where('id_rol !=', 99);
        }

        if (isset($arrData['idRol'])) {
            $builder->where('id_rol', $arrData['idRol']);
        }

        $builder->orderBy('rol_name', 'ASC');

        $query = $builder->get();
        $result = $query->getResultArray();

        return !empty($result) ? $result : false;
    }

	/**
	 * Get vehicle list -> Se usa en el Login y en Inspection
	 * Param varchar $encryption -> dato que viene del QR code
	 * Param varchar $idVehicle -> identificador del vehiculo
	 * @since 3/3/2016
	 */
    public function get_vehicle_by($arrData)
    {
        $builder = $this->db->table('param_vehicle V');

        $builder->select('*');
        $builder->join('param_vehicle_type_2 T', 'T.id_type_2 = V.type_level_2', 'inner');

        if (array_key_exists("encryption", $arrData)) {
            $builder->where('V.encryption', $arrData["encryption"]);
        }

        if (array_key_exists("idVehicle", $arrData)) {
            $builder->where('V.id_vehicle', $arrData["idVehicle"]);
        }

        if (array_key_exists("vehicleState", $arrData)) {
            $builder->where('V.state', $arrData["vehicleState"]);
        }

        if (array_key_exists("vinNumber", $arrData)) {
            if ($arrData["vinNumber"] != "false") {
                $builder->like('V.vin_number', $arrData["vinNumber"]);
            }
        }

        if (array_key_exists("vehicleType", $arrData)) {
            if ($arrData["vehicleType"] != "false") {
                $builder->where('V.fk_id_company', 1);
                $builder->where('T.inspection_type', $arrData["vehicleType"]);
            }
        }

        $builder->orderBy('V.unit_number', 'ASC');

        $query = $builder->get();

        $result = $query->getResultArray();

        return !empty($result) ? $result : false;
    }


	/**
	 * Info Planning for the Employee
	 * @since 27/8/2023
     * @review 20/03/2026 - new CI4 version
	 */
    public function get_planning_for_employee(array $arrData)
    {
        // Conectar al builder
        $builder = $this->db->table('programming P');

        $builder->select("P.date_programming, P.observation, X.job_description, W.*, CONCAT(V.unit_number,' -----> ', V.description) as unit_description, H.hora");
        $builder->join('programming_worker W', 'W.fk_id_programming = P.id_programming', 'INNER');
        $builder->join('param_jobs X', 'X.id_job = P.fk_id_job', 'INNER');
        $builder->join('param_vehicle V', 'V.id_vehicle = W.fk_id_machine', 'LEFT'); 
        $builder->join('param_horas H', 'H.id_hora = W.fk_id_hour', 'LEFT');

        // Filtros condicionales
        if (isset($arrData['idUser'])) {
            $builder->where('W.fk_id_programming_user', $arrData['idUser']);
        }
        if (isset($arrData['fecha'])) {
            $builder->where('P.date_programming', $arrData['fecha']);
        }
        if (isset($arrData['nextPlanning']) && $arrData['nextPlanning']) {
            $builder->where('P.date_programming >=', date('Y-m-d'));
        }

        $builder->where('P.state !=', 3);
        $builder->orderBy('P.date_programming', 'ASC');

        $query = $builder->get();

        if ($query->getNumRows() >= 1) {
            return $query->getResultArray(); // CI4
        } else {
            return false;
        }
    }

	/**
	 * Lista de programacion
	 * @since 21/12/2020
     * @review 20/03/2026 - new CI4 version
	 */
	public function get_programming_info($arrData)
	{
        // Conectar al builder
        $builder = $this->db->table('programming P');

		$builder->select("P.*, X.id_job, X.job_description, U.id_user, CONCAT(U.first_name, ' ', U.last_name) name");
		$builder->join('user U', 'U.id_user = P.fk_id_user', 'INNER');
		$builder->join('param_jobs X', 'X.id_job = P.fk_id_job', 'INNER');

		if (isset($arrData["nextPlanning"])) {
			$currentDate = date('Y-m-d');
			$plus2 = date('Y-m-d', strtotime($currentDate . ' +2 days'));

			$builder->where('P.date_programming >', $currentDate);
			$builder->where('P.date_programming <', $plus2);
			$builder->where('P.state !=', 3);
		}
		if (isset($arrData["idProgramming"])) {
			$builder->where('P.id_programming', $arrData["idProgramming"]);
		}
		if (isset($arrData["fecha"])) {
			$builder->where('P.date_programming', $arrData["fecha"]);
		}
		if (isset($arrData["estado"])) {
			if ($arrData["estado"] == "ACTIVAS") {
				$builder->where('P.state !=', 3);
			} else {
				$builder->where('P.state', $arrData["estado"]);
			}
		}
		if (isset($arrData["from"]) && $arrData["from"] != '') {
			$builder->where('P.date_programming >=', $arrData["from"]);
		}
		if (isset($arrData["to"]) && $arrData["to"] != '' && $arrData["from"] != '') {
			$builder->where('P.date_programming <', $arrData["to"]);
		}

		$builder->orderBy("P.date_programming DESC");
		$query = $builder->get();

        $result = $query->getResultArray();

        return !empty($result) ? $result : false;
	}

	/**
	 * Maintenance Check list
	 * @since 13/3/2020
     * @review 20/03/2026 - new CI4 version
	 */
	public function get_maintenance_check()
	{
        $builder = $this->db->table('maintenance_check C');

		$builder->select();
		$builder->join('preventive_maintenance M', 'M.id_preventive_maintenance = C.fk_id_maintenance', 'INNER');
		$builder->join('maintenance_type T', 'T.id_maintenance_type = M.fk_id_maintenance_type', 'INNER');
		$builder->join('param_vehicle V', 'V.id_vehicle = M.fk_id_equipment', 'INNER');

		$builder->orderBy('V.unit_number', 'asc');
		$query = $builder->get();

        $result = $query->getResultArray();

        return !empty($result) ? $result : false;
	}

    /**
     * Contar registros del modulo VEHICLES
     * @author BMOTTAG
     * @review 20/03/2026 - new CI4 version
     */
    public function get_without_work_order()
    {
        $sql = "SELECT u.first_name, u.last_name, j.job_description, t.*
                FROM task t
                JOIN user u ON t.fk_id_user = u.id_user
                JOIN param_jobs j ON t.fk_id_job_finish = j.id_job
                WHERE t.wo_end_project IS NULL
                    AND t.hours_end_project IS NOT NULL
                    AND t.hours_end_project <> 0
                    AND t.start >= '2025-01-01'
                UNION ALL
                SELECT u.first_name, u.last_name, j.job_description, t.*
                FROM task t
                JOIN user u ON t.fk_id_user = u.id_user
                JOIN param_jobs j ON t.fk_id_job = j.id_job
                WHERE t.fk_id_programming IS NULL
                    AND t.start >= '2025-01-01'
                ORDER BY id_task DESC;";

        $query = $this->db->query($sql);

        if ($query->getNumRows() > 0) {
            return $query->getResultArray();
        } else {
            return false;
        }
    }

	/**
	 * Task list
	 * Modules: Dashboard - Payroll
	 * @since 10/11/2016
     * @review 20/03/2026 - new CI4 version
	 */
	public function get_task($arrData)
	{
        $builder = $this->db->table('task T');
		$builder->select('T.*, id_user, first_name, last_name, log_user, J.job_description job_start, H.job_description job_finish, O.task');
		$builder->join('user U', 'U.id_user = T.fk_id_user', 'INNER');
		$builder->join('param_jobs J', 'J.id_job = T.fk_id_job', 'INNER');
		$builder->join('param_jobs H', 'H.id_job = T.fk_id_job_finish', 'LEFT');
		$builder->join('param_operation O', 'O.id_operation = T.fk_id_operation', 'INNER');

		if (isset($arrData["idTask"])) {
			$builder->where('id_task', $arrData["idTask"]);
		}
		if (isset($arrData["idEmployee"])) {
			$builder->where('U.id_user', $arrData["idEmployee"]);
		}
		if (isset($arrData["from"]) && $arrData["from"] != '') {
			$builder->where('T.start >=', $arrData["from"]);
		}
		if (isset($arrData["to"]) && $arrData["to"] != '' && $arrData["from"] != '') {
			$builder->where('T.start <', $arrData["to"]);
		}
		if (isset($arrData["toLimit"]) && $arrData["toLimit"] != '') {
			$builder->where('T.start <', $arrData["toLimit"]);
		}
		if (isset($arrData["fecha"])) {
			$builder->like('T.start', $arrData["fecha"]);
		}
		if (isset($arrData["idWorkOrder"])) {
			$builder->where($arrData["column"], $arrData["idWorkOrder"]);
		}

        $builder->orderBy('id_task', 'desc');

		if (isset($arrData["limit"])) {
            $builder->limit($arrData["limit"]);
		}
		$query = $builder->get();

        $result = $query->getResultArray();
        return !empty($result) ? $result : false;
	}

	/**
	 * Safety´s list
	 * Modules: Dashboard
	 * @since 6/12/2016
	 * @review 9/3/2017
     * @review 20/03/2026 - new CI4 version
	 */
	public function get_safety($arrData)
	{
        $builder = $this->db->table('safety S');
		$builder->select("S.*, CONCAT(first_name, ' ', last_name) name, J.job_description");
		$builder->join('user U', 'U.id_user = S.fk_id_user', 'INNER');
		$builder->join('param_jobs J', 'J.id_job = S.fk_id_job', 'INNER');

		if (isset($arrData["idSafety"])) {
			$builder->where('S.id_safety', $arrData["idSafety"]);
		}
		if (isset($arrData["idJob"])) {
			$builder->where('S.fk_id_job', $arrData["idJob"]);
		}
		if (isset($arrData["fecha"])) {
			$fecha = $arrData["fecha"] . '%';
			$builder->where('S.date LIKE', $fecha);
		}

        $builder->orderBy('id_safety', 'desc');

		if (array_key_exists("limit", $arrData)) {
			$builder->limit($arrData["limit"]);
		}
		$query = $builder->get();

        $result = $query->getResultArray();
        return !empty($result) ? $result : false;
	}

	/**
	 * Special inspection list
	 * Modules: Dashboard 
	 * @since 27/6/2017
     * @review 20/03/2026 - new CI4 version
	 */
	public function get_special_inspection_water_truck($arrData)
	{
        $builder = $this->db->table('inspection_watertruck I');
		$builder->select("I.*, CONCAT(first_name, ' ', last_name) name, V.*");
		$builder->join('user U', 'U.id_user = I.fk_id_user', 'INNER');
		$builder->join('param_vehicle V', 'V.id_vehicle = I.fk_id_vehicle', 'INNER');

		if (isset($arrData["idEmployee"])) {
			$builder->where('U.id_user', $arrData["idEmployee"]);
		}

		$builder->orderBy('I.date_issue', 'desc');
		$builder->limit($arrData["limit"]);
		$query = $builder->get();


        $result = $query->getResultArray();
        return !empty($result) ? $result : false;
	}

	/**
	 * Special inspection list
	 * Modules: Dashboard 
	 * @since 8/5/2017
     * @review 20/03/2026 - new CI4 version
	 */
	public function get_special_inspection_hydrovac($arrData)
	{
		$builder = $this->db->table('inspection_hydrovac I');
		$builder->select("I.*, CONCAT(first_name, ' ', last_name) name, V.*");
		$builder->join('user U', 'U.id_user = I.fk_id_user', 'INNER');
		$builder->join('param_vehicle V', 'V.id_vehicle = I.fk_id_vehicle', 'INNER');

		if (isset($arrData["idEmployee"])) {
			$builder->where('U.id_user', $arrData["idEmployee"]);
		}

		$builder->orderBy('I.date_issue', 'desc');
		$builder->limit($arrData["limit"]);
		$query = $builder->get();

        $result = $query->getResultArray();
        return !empty($result) ? $result : false;
	}

	/**
	 * Special inspection list
	 * Modules: Dashboard 
	 * @since 8/5/2017
     * @review 20/03/2026 - new CI4 version
	 */
	public function get_special_inspection_sweeper($arrData)
	{
        $builder = $this->db->table('inspection_sweeper I');
		$builder->select("I.*, CONCAT(first_name, ' ', last_name) name, V.*");
		$builder->join('user U', 'U.id_user = I.fk_id_user', 'INNER');
		$builder->join('param_vehicle V', 'V.id_vehicle = I.fk_id_vehicle', 'INNER');

		if (isset($arrData["idEmployee"])) {
			$builder->where('U.id_user', $arrData["idEmployee"]);
		}

		$builder->orderBy('I.date_issue', 'desc');
		$query = $builder->get();

        $result = $query->getResultArray();
        return !empty($result) ? $result : false;
	}

	/**
	 * Special inspection list
	 * Modules: Dashboard 
	 * @since 8/5/2017
     * @review 20/03/2026 - new CI4 version
     * @author BMOTTAG
	 */
	public function get_special_inspection_generator($arrData)
	{
		$builder = $this->db->table('inspection_generator I');
		$builder->select("I.*, CONCAT(first_name, ' ', last_name) name, V.*");
		$builder->join('user U', 'U.id_user = I.fk_id_user', 'INNER');
		$builder->join('param_vehicle V', 'V.id_vehicle = I.fk_id_vehicle', 'INNER');

		if (isset($arrData["idEmployee"])) {
			$builder->where('U.id_user', $arrData["idEmployee"]);
		}

		$builder->orderBy('I.date_issue', 'desc');
		$query = $builder->get();

        $result = $query->getResultArray();
        return !empty($result) ? $result : false;
	}

	/**
	 * menu list for a role
	 * Modules: MENU
	 * @since 2/4/2020
     * @review 20/03/2026 - new CI4 version
     * @author BMOTTAG
	 */
    public function get_role_menu($arrData)
    {
        $builder = $this->db->table('param_menu_permisos P');
        $builder->distinct();
        $builder->select();
        $builder->join('param_menu M', 'M.id_menu = P.fk_id_menu', 'INNER');

        if (array_key_exists("idRole", $arrData)) {
            $builder->where('P.fk_id_rol', $arrData["idRole"]);
        }
        if (array_key_exists("menuType", $arrData)) {
            $builder->where('M.menu_type', $arrData["menuType"]);
        }
        if (array_key_exists("menuState", $arrData)) {
            $builder->where('M.menu_state', $arrData["menuState"]);
        }

        $builder->orderBy('M.menu_order', 'ASC');

        $query = $builder->get();
        $result = $query->getResultArray();

        return !empty($result) ? $result : false;
    }

	/**
	 * Lista de permisos
	 * Modules: MENU
	 * @since 31/3/2020
     * @review 20/03/2026 - new CI4 version
      * @author BMOTTAG
	 */
	public function get_role_access($arrData)
	{
        $builder = $this->db->table('param_menu_permisos P');
		$builder->select('P.id_permiso, P.fk_id_menu, P.fk_id_link, P.fk_id_rol, M.menu_name, M.menu_order, M.menu_type, L.link_name, L.link_url, L.order, L.link_icon, L.link_type, R.rol_name, R.estilos');
		$builder->join('param_menu M', 'M.id_menu = P.fk_id_menu', 'INNER');
		$builder->join('param_menu_links L', 'L.id_link = P.fk_id_link', 'LEFT');
		$builder->join('param_rol R', 'R.id_rol = P.fk_id_rol', 'INNER');

		if (isset($arrData["idPermiso"])) {
			$builder->where('P.id_permiso', $arrData["idPermiso"]);
		}
		if (isset($arrData["idMenu"])) {
			$builder->where('P.fk_id_menu', $arrData["idMenu"]);
		}
		if (isset($arrData["idLink"])) {
			$builder->where('P.fk_id_link', $arrData["idLink"]);
		}
		if (isset($arrData["idRole"])) {
			$builder->where('P.fk_id_rol', $arrData["idRole"]);
		}
		if (isset($arrData["menuType"])) {
			$builder->where('M.menu_type', $arrData["menuType"]);
		}
		if (isset($arrData["linkState"])) {
			$builder->where('L.link_state', $arrData["linkState"]);
		}
		if (isset($arrData["menuURL"])) {
			$builder->where('M.menu_url', $arrData["menuURL"]);
		}
		if (isset($arrData["linkURL"])) {
			$builder->where('L.link_url', $arrData["linkURL"]);
		}

		$builder->orderBy('M.menu_order, L.order', 'asc');
        $query = $builder->get();
    
        $result = $query->getResultArray();
        return !empty($result) ? $result : false;
	}

	/**
	 * Hauling list
	 * Modules: Dashboard 
	 * @since 13/1/2017
	 */
	public function get_hauling($arrData)
	{
		$builder = $this->db->table('hauling H');
		$builder->select("H.*, CONCAT(first_name, ' ', last_name) name, C.company_name, V.unit_number, T.truck_type, J.job_description site_from, Z.job_description site_to, M.material, P.payment");
		$builder->join('user U', 'U.id_user = H.fk_id_user', 'INNER');
		$builder->join('param_company C', 'C.id_company = H.fk_id_company', 'INNER');
		$builder->join('param_vehicle V', 'V.id_vehicle = H.fk_id_truck', 'LEFT');
		$builder->join('param_truck_type T', 'T.id_truck_type = H.fk_id_truck_type', 'LEFT');
		$builder->join('param_jobs J', 'J.id_job = H.fk_id_site_from', 'INNER');
		$builder->join('param_jobs Z', 'Z.id_job = H.fk_id_site_to', 'LEFT');
		$builder->join('param_material_type M', 'M.id_material = H.fk_id_material', 'LEFT');
		$builder->join('param_payment P', 'P.id_payment = H.fk_id_payment', 'LEFT');

		if (isset($arrData["idEmployee"])) {
			$builder->where('U.id_user', $arrData["idEmployee"]);
		}
		if (isset($arrData["fecha"])) {
			$builder->where('H.date_issue', $arrData["fecha"]);
		}
		if (isset($arrData["from"]) && $arrData["from"] != '') {
			$builder->where('H.date_issue >=', $arrData["from"]);
		}
		if (isset($arrData["to"]) && $arrData["to"] != '' && $arrData["from"] != '') {
			$builder->where('H.date_issue <', $arrData["to"]);
		}
		if (isset($arrData["state_delete"])) {
			$builder->where('H.state', 3);
		}
		if (isset($arrData["state_active"])) {
			$builder->where('H.state !=', 3);
		}

		$builder->orderBy('H.id_hauling', 'desc');

		if (isset($arrData["limit"])) {
			$builder->limit($arrData["limit"]);
		}
		$query = $builder->get();

        $result = $query->getResultArray();
        return !empty($result) ? $result : false;
	}

	/**
	 * Daily inspection list
	 * Modules: Dashboard 
	 * @since 14/1/2017
	 * @review 20/03/2026 - new CI4 version
	  * @author BMOTTAG	
	 */
	public function get_daily_inspection($arrData)
	{
		$builder = $this->db->table('inspection_daily I');
		$builder->select("I.*, CONCAT(first_name, ' ', last_name) name, V.*");
		$builder->join('user U', 'U.id_user = I.fk_id_user', 'INNER');
		$builder->join('param_vehicle V', 'V.id_vehicle = I.fk_id_vehicle', 'INNER');

		if (array_key_exists("idEmployee", $arrData)) {
			$builder->where('U.id_user', $arrData["idEmployee"]);
		}

		$builder->orderBy('I.date_issue', 'desc');
		$builder->limit($arrData["limit"]);
		$query = $builder->get();

        $result = $query->getResultArray();
        return !empty($result) ? $result : false;
	}

	/**
	 * Heavy inspection list
	 * Modules: Dashboard 
	 * @since 14/1/2017
	 * @review 20/03/2026 - new CI4 version
	  * @author BMOTTAG
	 */
	public function get_heavy_inspection($arrData)
	{
		$builder = $this->db->table('inspection_heavy I');
		$builder->select("I.*, CONCAT(first_name, ' ', last_name) name, V.*");
		$builder->join('user U', 'U.id_user = I.fk_id_user', 'INNER');
		$builder->join('param_vehicle V', 'V.id_vehicle = I.fk_id_vehicle', 'INNER');

		if (isset($arrData["idEmployee"])) {
			$builder->where('U.id_user', $arrData["idEmployee"]);
		}

		$builder->orderBy('I.date_issue', 'desc');
		$builder->limit($arrData["limit"]);
		$query = $builder->get();

		$result = $query->getResultArray();
		return !empty($result) ? $result : false;
	}

	/**
	 * Work Order
	 * @since 21/12/2020
	 * @review 20/03/2026 - new CI4 version
	 * @author BMOTTAG
	 */
	public function get_workorder_info($arrData)
	{
		$builder = $this->db->table('workorder W');
		$builder->select("W.*, CONCAT(first_name, ' ', last_name) name, J.id_job, J.job_description, C.*");
		$builder->join('user U', 'U.id_user = W.fk_id_user', 'INNER');
		$builder->join('param_jobs J', 'J.id_job = W.fk_id_job', 'INNER');
		$builder->join('param_company C', 'C.id_company = W.fk_id_company', 'LEFT');

		if (isset($arrData["jobId"]) && $arrData["jobId"] != '' && $arrData["jobId"] != 0) {
			$builder->where('W.fk_id_job', $arrData["jobId"]);
		}
		if (isset($arrData["idClaim"])) {
			$builder->where('W.fk_id_claim', $arrData["idClaim"]);
		}
		if (isset($arrData["idWorkOrder"]) && $arrData["idWorkOrder"] != '' && $arrData["idWorkOrder"] != 0) {
			$builder->where('W.id_workorder', $arrData["idWorkOrder"]);
		}
		if (isset($arrData["idWorkOrderFrom"]) && $arrData["idWorkOrderFrom"] != '' && $arrData["idWorkOrderFrom"] != 0) {
			$builder->where('W.id_workorder >=', $arrData["idWorkOrderFrom"]);
		}
		if (isset($arrData["idWorkOrderTo"]) && $arrData["idWorkOrderTo"] != '' && $arrData["idWorkOrderTo"] != 0) {
			$builder->where('W.id_workorder <=', $arrData["idWorkOrderTo"]);
		}
		if (isset($arrData["from"]) && $arrData["from"] != '') {
			$builder->where('W.date >=', $arrData["from"]);
		}
		if (isset($arrData["to"]) && $arrData["to"] != '' && $arrData["from"] != '') {
			$builder->where('W.date <', $arrData["to"]);
		}
		if (isset($arrData["state"]) && $arrData["state"] != '') {
			$builder->where('W.state', $arrData["state"]);
		}
		if (isset($arrData["fecha"])) {
			$builder->where('W.date', $arrData["fecha"]);
		}

		$builder->orderBy('W.id_workorder', 'desc');
		$query = $builder->get();

		$result = $query->getResultArray();
		return !empty($result) ? $result : false;
	}

	/**
	 * Forceaccounts info
	 * @since 05/05/2025
	 * @review 20/03/2026 - new CI4 version
	 */
	public function get_forceaccount_info($arrData)
	{
		$builder = $this->db->table('forceaccount W');
		$builder->select('W.*, J.id_job, job_description, CONCAT(U.first_name, " ", U.last_name) name, C.company_name company, C.id_company, A.id_acs');
		$builder->join('param_jobs J', 'J.id_job = W.fk_id_job', 'INNER');
		$builder->join('param_company C', 'C.id_company = W.fk_id_company', 'LEFT');
		$builder->join('acs A', 'A.fk_id_workorder = W.id_forceaccount', 'LEFT');
		$builder->join('user U', 'U.id_user = W.fk_id_user', 'INNER');

		if (isset($arrData["from"]) && $arrData["from"] != '') {
			$builder->where('W.date >=', $arrData["from"]);
		}
		if (isset($arrData["to"]) && $arrData["to"] != '' && $arrData["from"] != '') {
			$builder->where('W.date <', $arrData["to"]);
		}

		$builder->orderBy('W.id_forceaccount', 'desc');
		$query = $builder->get();

		$result = $query->getResultArray();
		return !empty($result) ? $result : false;
	}

	/**
	 * Check In List
	 * @since 1/6/2022
	 */
	public function get_checkin($arrDatos)
	{
		$builder = $this->db->table('new_checkin C');
		$builder->select();
		$builder->join('new_workers W', 'W.id_worker = C.fk_id_worker', 'INNER');
		$builder->join('param_jobs J', 'J.id_job = C.fk_id_job', 'INNER');
		if (isset($arrDatos["idCheckin"])) {
			$builder->where('C.id_checkin', $arrDatos["idCheckin"]);
		}
		if (isset($arrDatos["idJob"])) {
			$builder->where('C.fk_id_job', $arrDatos["idJob"]);
		}
		if (isset($arrDatos["today"])) {
			$builder->where('C.checkin_date', $arrDatos["today"]);
		}
		if (isset($arrDatos["checkout"])) {
			$builder->where('C.checkout_time', '0000-00-00 00:00:00');
		}
		$builder->orderBy('C.fk_id_job, C.id_checkin', 'asc');
		$query = $builder->get();

		$result = $query->getResultArray();
		return !empty($result) ? $result : false;
	}


	/**
	 * User list
	 * @since 30/3/2020
	 * @review 20/03/2026 - new CI4 version
	 */
	public function get_user($arrData)
	{
		$builder = $this->db->table('user U');
		$builder->select();
		$builder->join('param_rol R', 'R.id_rol = U.perfil', 'INNER');
		if (isset($arrData["idUser"])) {
			$builder->where('U.id_user', $arrData["idUser"]);
		}
		if (isset($arrData["idUserMANAGERS"])) {
			$IDmagers = array(2, 3);
			$builder->where_in('U.id_user', $IDmagers);
		}
		if (isset($arrData["state"])) {
			$builder->where('U.state', $arrData["state"]);
		}
		//list without inactive users
		if (isset($arrData["filtroState"])) {
			$builder->where('U.state !=', 2);
		}
		if (isset($arrData["employee_subcontractor"])) {
			$builder->where('U.employee_subcontractor', $arrData["employee_subcontractor"]);
		}
		if (isset($arrData["idRolesSupervisors"])) {
			$idRoles = array(ID_ROL_SUPER_ADMIN, ID_ROL_MANAGER, ID_ROL_SAFETY, ID_ROL_SUPERVISOR);
			$builder->where_in('U.perfil', $idRoles);
			$builder->where('U.id_user !=', 1);
		}

		$builder->orderBy("first_name, last_name", "ASC");
		$query = $builder->get();

		$result = $query->getResultArray();
		return !empty($result) ? $result : false;
	}

	/**
	 * Get user certificates
	 * @since 15/1/2022
	 * @review 20/03/2026 - new CI4 version
	 */
	public function get_user_certificates($arrData)
	{
		$builder = $this->db->table('user_certificates X');
		$builder->select();
		$builder->join('user U', 'U.id_user = X.fk_id_user', 'INNER');
		$builder->join('param_certificates C', 'C.id_certificate = X.fk_id_certificate ', 'INNER');
		if (isset($arrData["idUserCertificate"])) {
			$builder->where('X.id_user_certificate', $arrData["idUserCertificate"]);
		}
		if (isset($arrData["idUser"])) {
			$builder->where('U.id_user', $arrData["idUser"]);
		}
		if (isset($arrData["state"])) {
			$builder->where('U.state', $arrData["state"]);
		}
		if (isset($arrData["expires"])) {
			$builder->where('X.expires', $arrData["expires"]);
		}
		if (isset($arrData["idCertificate"])) {
			$builder->where('C.id_certificate', $arrData["idCertificate"]);
		}
		if (isset($arrData["date"])) {
			$builder->where('X.date_through <=', $arrData["date"]);
			$builder->where('X.expires', 1);
		}
		$builder->orderBy('C.certificate', 'asc');
		$query = $builder->get();

		$result = $query->getResultArray();
		return !empty($result) ? $result : false;
	}

	/**
	 * Get workorder expenses info
	 * @since 18/4/2023
	 */
	public function get_certificate_list($arrData)
	{
		$builder = $this->db->table('param_certificates');
		if (isset($arrData["idCertificate"])) {
			$builder->where('id_certificate', $arrData["idCertificate"]);
		}
		$builder->orderBy('certificate', 'asc');
		$query = $builder->get();

		$result = $query->getResultArray();
		return !empty($result) ? $result : false;
	}

	public function get_certificates_with_users($arrData = [])
	{
		$builder = $this->db->table('param_certificates C');

		$builder->select('
			C.id_certificate,
			C.certificate,
			C.certificate_description,
			U.id_user,
			U.first_name,
			U.last_name,
			X.date_through
		');

		$builder->join('user_certificates X', 'X.fk_id_certificate = C.id_certificate', 'left');
		$builder->join('user U', 'U.id_user = X.fk_id_user', 'left');

		// filtros
		if (!empty($arrData["idCertificate"])) {
			$builder->where('C.id_certificate', $arrData["idCertificate"]);
		}

		if (!empty($arrData["date"])) {
			$builder->where('X.date_through <=', $arrData["date"]);
			$builder->where('X.expires', 1);
		}

		$builder->orderBy('C.certificate', 'asc');

		$query = $builder->get();

		return $query->getResultArray();
	}

	/**
	 * Get JOBs 
	 * @since 03/01/2025
	 */
	public function get_job($arrData)
	{
		$builder = $this->db->table('param_jobs J');
		$builder->join('param_company C', 'C.id_company = J.fk_id_company', 'LEFT');
		$builder->join('param_company_foreman F', 'F.fk_id_job = J.id_job', 'LEFT');
		if (isset($arrData["idJob"])) {
			$builder->where('J.id_job', $arrData["idJob"]);
		}
		if (isset($arrData["state"])) {
			$builder->where('state', $arrData["state"]);
		}
		if (isset($arrData["withLIC"])) {
			$builder->where('flag_upload_details', 1);
		}
		$builder->orderBy("J.job_description", "asc");
		$query = $builder->get();

		$result = $query->getResultArray();
		return !empty($result) ? $result : false;
	}

	/**
	 * Verificar si el job code ya existe en la base de datos
	 * @author BMOTTAG
	 * @since  30/12/2022
	 */
	public function jobCodeVerify($arrData)
	{
		$builder = $this->db->table('param_jobs');
		if (isset($arrData["idJob"])) {
			$builder->where('id_job !=', $arrData["idJob"]);
		}

		$builder->where($arrData["column"], $arrData["value"]);
		$query = $builder->get();

		$result = $query->getResultArray();
		return !empty($result) ? true : false;
	}

	public function get_notifications_access_view(array $arrData = []): array
	{
		$builder = $this->db->table('notifications_access A');

		$builder->select("
			A.*, 
			N.notification, 
			N.description, 
			GROUP_CONCAT(DISTINCT CONCAT(U.first_name, ' ', U.last_name) ORDER BY U.id_user ASC SEPARATOR ', ') AS name_email, 
			GROUP_CONCAT(DISTINCT U.email ORDER BY U.id_user ASC SEPARATOR ', ') AS email, 
			GROUP_CONCAT(DISTINCT CONCAT(X.first_name, ' ', X.last_name) ORDER BY X.id_user ASC SEPARATOR ', ') AS name_sms, 
			GROUP_CONCAT(DISTINCT X.movil ORDER BY X.id_user ASC SEPARATOR ', ') AS movil
		");

		$builder->join('notifications N', 'N.id_notification = A.fk_id_notification', 'inner');

		// JOIN con JSON (se deja como string porque Query Builder no lo parsea)
		$builder->join(
			'user U',
			"JSON_CONTAINS(A.fk_id_user_email, JSON_QUOTE(CAST(U.id_user AS CHAR)))",
			'left'
		);

		$builder->join(
			'user X',
			"JSON_CONTAINS(A.fk_id_user_sms, JSON_QUOTE(CAST(X.id_user AS CHAR)))",
			'left'
		);

		// Filtros
		if (!empty($arrData["idNotificationAccess"])) {
			$builder->where('A.id_notification_access', $arrData["idNotificationAccess"]);
		}

		if (!empty($arrData["idNotification"])) {
			$builder->where('A.fk_id_notification', $arrData["idNotification"]);
		}

		$builder->groupBy('A.id_notification_access');
		$builder->orderBy('N.notification', 'ASC');

		$query = $builder->get();

		return $query->getNumRows() > 0
			? $query->getResultArray()
			: [];
	}

	public function getAvailableNotifications()
	{
		$builder = $this->db->table('notifications n');

		$builder->select('n.*');
		$builder->join(
			'notifications_access na',
			'n.id_notification = na.fk_id_notification',
			'left'
		);

		$builder->where('na.fk_id_notification IS NULL');
		$builder->where('n.setup', 1);

		return $builder->get()->getResultArray();
	}

	/**
	 * Equipment by Type
	 * @author BMOTTAG
	 * @since  24/06/2023
	 */
	public function equipmentByTypeList(): array
	{
		$builder = $this->db->table('param_vehicle_type_2');

		$builder->distinct();
		$builder->select('inspection_type, header_inspection_type');

		return $builder->get()->getResultArray();
	}


	/**
	 * Get dayoff info
	 * @since 7/12/2016
	 * @review 6/2/2017
	 */
	public function get_day_off($arrData)
	{
		$idUser = session()->get("id");

		$firstDay = (new \DateTime())->modify('-6 months')->format('Y-m-d');
		$beforeYesterday = (new \DateTime())->modify('-2 days')->format('Y-m-d');

		$builder = $this->db->table('dayoff D');
		$builder->select("D.*, CONCAT(first_name, ' ', last_name) name");
		$builder->join('user U', 'U.id_user = D.fk_id_user', 'INNER');

		// empleado
		if (isset($arrData["idEmployee"])) {
			$builder->where('U.id_user', $idUser);
		}

		// estado
		if (isset($arrData["state"])) {
			$builder->where('D.state', $arrData["state"]);

			if ($arrData["state"] > 1) {
				$builder->where('D.date_dayoff >=', $beforeYesterday);
			}
		}

		// id específico
		if (isset($arrData["idDayoff"])) {
			$builder->where('D.id_dayoff', $arrData["idDayoff"]);
		}

		// últimos 6 meses
		$builder->where('D.date_issue >=', $firstDay);

		$builder->orderBy('D.id_dayoff', 'DESC');

		return $builder->get()->getResultArray();
	}

	/**
	 * Lista de menu
	 * Modules: MENU
	 * @since 30/3/2020
	 */
	public function get_menu($arrData)
	{
		$builder = $this->db->table('param_menu');
		if (isset($arrData["idMenu"])) {
			$builder->where('id_menu', $arrData["idMenu"]);
		}
		if (isset($arrData["menuType"])) {
			$builder->where('menu_type', $arrData["menuType"]);
		}
		if (isset($arrData["menuState"])) {
			$builder->where('menu_state', $arrData["menuState"]);
		}
		if (isset($arrData["columnOrder"])) {
			$builder->orderBy($arrData["columnOrder"], 'asc');
		} else {
			$builder->orderBy('menu_order', 'asc');
		}

		return $builder->get()->getResultArray();
	}

	/**
	 * Lista de enlaces
	 * Modules: MENU
	 * @since 31/3/2020
	 */
	public function get_links($arrData)
	{
		$builder = $this->db->table('param_menu_links L');

		$builder->join('param_menu M', 'M.id_menu = L.fk_id_menu', 'INNER');

		if (isset($arrData["idMenu"])) {
			$builder->where('fk_id_menu', $arrData["idMenu"]);
		}
		if (isset($arrData["idLink"])) {
			$builder->where('id_link', $arrData["idLink"]);
		}
		if (isset($arrData["linkType"])) {
			$builder->where('link_type', $arrData["linkType"]);
		}
		if (isset($arrData["linkState"])) {
			$builder->where('link_state', $arrData["linkState"]);
		}

		$builder->orderBy('M.menu_order, L.order', 'asc');
		return $builder->get()->getResultArray();
	}

	/**
	 * Get job employee_type_unit_price
	 * @since 27/11/2017
	 */
	public function get_job_employee_type_unit_price($idJob)
	{
		$builder = $this->db->table('job_employee_type_price JE');
		$builder->select();
		$builder->join('param_employee_type PE', 'PE.id_employee_type = JE.fk_id_employee_type ', 'INNER');
		$builder->where('JE.fk_id_job', $idJob);
		$builder->orderBy('PE.employee_type', 'asc');
		return $builder->get()->getResultArray();
	}


}
