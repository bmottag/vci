<?php
namespace App\Modules\Dashboard\Models;

use CodeIgniter\Model;

class DashboardModel extends Model
{
    protected $session;

    public function __construct()
    {
        parent::__construct();
        $this->session = session();
    }

    /**
     * Contar registros del modulo SAFETY
     * @author BMOTTAG
     * @since  8/12/2016
     */
    public function countSafety()
    {
            $userRol = $this->session->get("rol");
            $idUser = $this->session->get("id");
        
            $year = date('Y');
            $firstDay = date('Y-m-d', mktime(0,0,0, 1, 1, $year));//primer dia del año

            $sql = "SELECT count(id_safety) CONTEO";
            $sql.= " FROM safety";
            $sql.= " WHERE date >= '$firstDay'";
            if($userRol == 7){ //If it is a BASIC USER, just show the records of the user session
                $sql.= " AND fk_id_user = $idUser";
            }

            $query = $this->db->query($sql);
            $row = $query->row();
            return $row->CONTEO;
    }

    /**
     * Contar registros del modulo JOBS
     * @author BMOTTAG
     * @since  31/1/2017
     */
    public function countJobs()
    {
            $sql = "SELECT count(id_job) CONTEO";
            $sql.= " FROM param_jobs";
            $sql.= " WHERE state = 1";

            $query = $this->db->query($sql);
            $row = $query->row();
            return $row->CONTEO;
    }

    
    /**
     * Verificar si aprobaron o negaron ul dia de permiso en los ultimos 7 dias
     * @since 9/12/2016
     */
    public function dayOffInfo() 
    {
        $idUser = $this->session->get("id");

        $fecha = date('Y-m-d');
        $nuevafecha = strtotime ( '-7 day' , strtotime ( $fecha ) ) ;
        $nuevafecha = date ( 'Y-m-d' , $nuevafecha ); 
        
        $builder = $this->db->table('dayoff');
        $builder->where('fk_id_user', $idUser);
        $builder->where('state <>', 1);
        $builder->where('date_update >=', $nuevafecha);
        $builder->orderBy('id_dayoff', 'DESC');
        $builder->limit(1);

        $query = $builder->get();

        if ($query->getNumRows() > 0) {
            return $query->getRowArray();
        } else {
            return false;
        }
    }
    
    /**
     * Contar registros del modulo HAULING
     * si no es ADMIN entonces filtra por usuario
     * @author BMOTTAG
     * @since  13/1/2017
     */
    public function countHauling()
    {
            $userRol = $this->session->get("rol");
            $idUser = $this->session->get("id");
            
            $year = date('Y');
            $firstDay = date('Y-m-d', mktime(0,0,0, 1, 1, $year));//primer dia del año

            $sql = "SELECT count(id_hauling) CONTEO";
            $sql.= " FROM hauling";
            $sql.= " WHERE date_issue >= '$firstDay'";
            if($userRol == 7){ //If it is a BASIC USER, just show the records of the user session
                $sql.= " AND fk_id_user = $idUser";
            }			

            $query = $this->db->query($sql);
            $row = $query->row();
            return $row->CONTEO;
    }
    
    /**
     * Contar registros del modulo DAILY INSPECTION
     * si no es ADMIN entonces filtra por usuario
     * @author BMOTTAG
     * @since  14/1/2017
     */
    public function countDailyInspection()
    {
            $userRol = $this->session->get("rol");
            $idUser = $this->session->get("id");
            
            $year = date('Y');
            $firstDay = date('Y-m-d', mktime(0,0,0, 1, 1, $year));//primer dia del año

            $sql = "SELECT count(id_inspection_daily) CONTEO";
            $sql.= " FROM inspection_daily";
            $sql.= " WHERE date_issue >= '$firstDay'";
            if($userRol == 7){ //If it is a BASIC USER, just show the records of the user session
                $sql.= " AND fk_id_user = $idUser";
            }			

            $query = $this->db->query($sql);
            $row = $query->row();
            return $row->CONTEO;
    }
    
    /**
     * Contar registros del modulo HEAVY INSPECTION
     * si no es ADMIN entonces filtra por usuario
     * @author BMOTTAG
     * @since  14/1/2017
     */
    public function countHeavyInspection()
    {
            $userRol = $this->session->get("rol");
            $idUser = $this->session->get("id");
            
            $year = date('Y');
            $firstDay = date('Y-m-d', mktime(0,0,0, 1, 1, $year));//primer dia del año

            $sql = "SELECT count(id_inspection_heavy) CONTEO";
            $sql.= " FROM inspection_heavy";
            $sql.= " WHERE date_issue >= '$firstDay'";
            if($userRol == 7){ //If it is a BASIC USER, just show the records of the user session
                $sql.= " AND fk_id_user = $idUser";
            }			

            $query = $this->db->query($sql);
            $row = $query->row();
            return $row->CONTEO;
    }
    
    /**
     * Contar registros del modulo SPECIAL INSPECTION
     * si no es ADMIN entonces filtra por usuario
     * @author BMOTTAG
     * @since  8/5/2017
     */
    public function countSpecialInspection()
    {
            $userRol = $this->session->get("rol");
            $idUser = $this->session->get("id");
            
            $year = date('Y');
            $firstDay = date('Y-m-d', mktime(0,0,0, 1, 1, $year));//primer dia del año

            $sql = "SELECT count(id_inspection_generator) CONTEO";
            $sql.= " FROM inspection_generator";
            $sql.= " WHERE date_issue >= '$firstDay'";
            if($userRol == 7){ //If it is a BASIC USER, just show the records of the user session
                $sql.= " AND fk_id_user = $idUser";
            }			

            $query = $this->db->query($sql);
            $row = $query->row();
            $generator = $row->CONTEO;

            $sql = "SELECT count(id_inspection_hydrovac) CONTEO";
            $sql.= " FROM inspection_hydrovac";
            $sql.= " WHERE date_issue >= '$firstDay'";
            if($userRol == 7){ //If it is a BASIC USER, just show the records of the user session
                $sql.= " AND fk_id_user = $idUser";
            }			

            $query = $this->db->query($sql);
            $row = $query->row();
            $hydrovac = $row->CONTEO;

            
            $sql = "SELECT count(id_inspection_sweeper) CONTEO";
            $sql.= " FROM inspection_sweeper";
            $sql.= " WHERE date_issue >= '$firstDay'";
            if($userRol == 7){ //If it is a BASIC USER, just show the records of the user session
                $sql.= " AND fk_id_user = $idUser";
            }			

            $query = $this->db->query($sql);
            $row = $query->row();
            $sweeper = $row->CONTEO;

            $number = $generator+ $hydrovac + $sweeper;
            return $number;
    }

    
    
    
    
    
    
}