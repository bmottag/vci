<?php
namespace App\Modules\Planner\Models;

use CodeIgniter\Model;
use App\Models\GeneralModel;

class PlannerModel extends Model
{
    protected $protectFields = false;
    protected $generalModel;

    public function __construct()
    {
        parent::__construct();
        $this->generalModel = new GeneralModel();
    }

    public function get_programmings_by_date(string $date): array
    {
        $builder = $this->db->table('programming P');
        $builder->select('P.id_programming, P.fk_id_job, P.fk_id_workorder, P.date_programming, P.observation, P.state, J.job_description');
        $builder->join('param_jobs J', 'J.id_job = P.fk_id_job', 'inner');
        $builder->where('P.date_programming', $date);
        $builder->where('P.state !=', 3);
        $builder->orderBy('J.job_description', 'asc');
        return $builder->get()->getResultArray();
    }

    public function get_dayoff_workers_by_date(string $date): array
    {
        $builder = $this->db->table('dayoff D');
        $builder->select('U.id_user, CONCAT(U.first_name, " ", U.last_name) AS name');
        $builder->join('user U', 'U.id_user = D.fk_id_user', 'inner');
        $builder->where('D.date_dayoff', $date);
        $builder->where('D.state', 2);
        $builder->groupBy('U.id_user');
        $builder->orderBy('U.first_name', 'asc');
        return $builder->get()->getResultArray();
    }

    /**
     * Total worked hours per employee, from Monday of the week containing
     * $date through $date (inclusive). Used to show workers' accumulated
     * hours for the running week in the Planner sidebar.
     */
    public function get_weekly_worked_hours(string $date): array
    {
        $dateObj   = new \DateTime($date);
        $dayOfWeek = (int) $dateObj->format('N'); // 1 (Mon) - 7 (Sun)
        $monday    = (clone $dateObj)->modify('-' . ($dayOfWeek - 1) . ' days');

        $builder = $this->db->table('task');
        $builder->select('fk_id_user, working_hours_new');
        $builder->where('start >=', $monday->format('Y-m-d') . ' 00:00:00');
        $builder->where('start <=', $dateObj->format('Y-m-d') . ' 23:59:59');
        $builder->where('working_hours_new IS NOT NULL');

        $totals = [];
        foreach ($builder->get()->getResultArray() as $row) {
            $parts   = explode(':', $row['working_hours_new']);
            $seconds = ((int) $parts[0] * 3600) + ((int) ($parts[1] ?? 0) * 60) + (int) ($parts[2] ?? 0);

            $uid           = (int) $row['fk_id_user'];
            $totals[$uid] = ($totals[$uid] ?? 0) + $seconds;
        }

        return $totals;
    }

    public function planner_add_worker(int $idProgramming, int $idUser): int|false
    {
        $this->db->table('programming_worker')->insert([
            'fk_id_programming'      => $idProgramming,
            'fk_id_programming_user' => $idUser,
            'fk_id_employee_type'    => 1,
            'fk_id_hour'             => 15,
            'site'                   => 1,
        ]);
        $id = $this->db->insertID();
        return $id ?: false;
    }

    public function planner_save_worker_detail(int $idProgrammingWorker, string $field, mixed $value): bool
    {
        return (bool) $this->db->table('programming_worker')
            ->where('id_programming_worker', $idProgrammingWorker)
            ->update([$field => $value]);
    }

    public function get_available_jobs_for_date(string $date): array
    {
        $existing    = $this->db->table('programming')
            ->select('fk_id_job')
            ->where('date_programming', $date)
            ->where('state !=', 3)
            ->get()->getResultArray();
        $existingIds = array_column($existing, 'fk_id_job');

        $builder = $this->db->table('param_jobs');
        $builder->select('id_job, job_description');
        $builder->where('state', 1);
        if (!empty($existingIds)) {
            $builder->whereNotIn('id_job', $existingIds);
        }
        $builder->orderBy('job_description', 'asc');
        return $builder->get()->getResultArray();
    }

    public function planner_create_programming(int $idJob, string $date): int|false
    {
        $this->db->table('programming')->insert([
            'fk_id_user'       => session()->get('id'),
            'date_issue'       => date('Y-m-d G:i:s'),
            'fk_id_job'        => $idJob,
            'date_programming' => $date,
            'state'            => 1,
            'flag_date'        => 1,
        ]);
        $id = $this->db->insertID();
        return $id ?: false;
    }

    public function planner_delete_project(int $idProgramming): bool
    {
        $this->db->table('programming_worker')
            ->where('fk_id_programming', $idProgramming)
            ->delete();
        $this->db->table('programming_material')
            ->where('fk_id_programming', $idProgramming)
            ->delete();
        $this->db->table('programming_ocasional')
            ->where('fk_id_programming', $idProgramming)
            ->delete();
        return (bool) $this->db->table('programming')
            ->where('id_programming', $idProgramming)
            ->update(['state' => 3]);
    }

    public function planner_insert_material(array $data): int|false
    {
        $this->db->table('programming_material')->insert($data);
        $id = $this->db->insertID();

        if ($id > 0) {
            $programData = $this->generalModel->get_basic_search([
                'table'  => 'programming',
                'order'  => 'id_programming',
                'column' => 'id_programming',
                'id'     => $data['fk_id_programming'],
            ]);
            $fkWorkorder = $programData ? ($programData[0]['fk_id_workorder'] ?? null) : null;
            if ($fkWorkorder) {
                $this->db->table('workorder_materials')->insert([
                    'fk_id_workorder'             => $fkWorkorder,
                    'fk_id_material'              => $data['fk_id_material'],
                    'quantity'                    => $data['quantity'],
                    'unit'                        => $data['unit'],
                    'description'                 => $data['description'],
                    'fk_id_programming_materials' => $id,
                ]);
            }
        }

        return $id > 0 ? $id : false;
    }

    public function countWorkers($idProgramming)
    {
        $query = $this->db->query(
            'SELECT count(id_programming_worker) CONTEO FROM programming_worker WHERE fk_id_programming = ?',
            [$idProgramming]
        );
        return $query->getRow()->CONTEO;
    }

    public function verifyProject($arrData)
    {
        $builder = $this->db->table('programming');
        $builder->where('fk_id_job', $arrData['idJob']);
        $builder->where('date_programming', $arrData['date']);
        $builder->where('state !=', 3);
        return $builder->get()->getNumRows() >= 1;
    }

    public function get_vehicles_inspection($arrData)
    {
        $sql = "SELECT id_vehicle, CONCAT(unit_number,' -----> ', description) as unit_description
                FROM param_vehicle V
                INNER JOIN param_vehicle_type_2 T ON T.id_type_2 = V.type_level_2
                WHERE fk_id_company = 1
                AND T.link_inspection != 'NA' AND V.id_vehicle NOT IN(41,42,43,44,61,62) AND V.state = 1 AND V.so_blocked = 1";

        if (!empty($arrData['vehicleToExclude'])) {
            $sql .= ' AND V.id_vehicle NOT IN (' . implode(',', $arrData['vehicleToExclude']) . ')';
        }
        $sql .= ' ORDER BY unit_number';

        $query  = $this->db->query($sql);
        $trucks = [];
        if ($query->getNumRows() > 0) {
            foreach ($query->getResultArray() as $i => $row) {
                $trucks[$i]['id_truck']    = $row['id_vehicle'];
                $trucks[$i]['unit_number'] = $row['unit_description'];
            }
        }
        return $trucks;
    }

    public function get_programming_materials($arrData)
    {
        $builder = $this->db->table('programming_material P');
        $builder->join('param_material_type M', 'M.id_material = P.fk_id_material', 'inner');
        if (array_key_exists('idProgramming', $arrData)) {
            $builder->where('P.fk_id_programming', $arrData['idProgramming']);
        }
        $builder->orderBy('M.material', 'asc');
        $query = $builder->get();
        return $query->getNumRows() > 0 ? $query->getResultArray() : false;
    }

    public function get_programming_occasional($arrData)
    {
        $idProgramming = $arrData['idProgramming'];
        $sql           = "SELECT P.*, C.company_name, C.does_hauling
                          FROM programming_ocasional P
                          INNER JOIN param_company C ON C.id_company = P.fk_id_company
                          WHERE (P.fk_id_programming = ? OR ? IS NULL)
                          ORDER BY C.company_name ASC";
        $query = $this->db->query($sql, [$idProgramming, $idProgramming]);
        return $query->getNumRows() > 0 ? $query->getResultArray() : false;
    }
}
