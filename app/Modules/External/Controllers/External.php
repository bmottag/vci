<?php
namespace App\Modules\External\Controllers;

use App\Controllers\BaseController;
use App\Modules\External\Models\ExternalModel;
use App\Models\GeneralModel;

class External extends BaseController
{
    protected $externalModel;
    protected $generalModel;

    public function __construct()
    {
        $this->externalModel = new ExternalModel();
        $this->generalModel  = new GeneralModel();
    }

    /**
     * Envio de mensaje
     * @since 26/11/2020
     * @author BMOTTAG
     * @review 05/05/2026 - new CI4 version
     */
    public function sendSMSWorker($idJob, $idTaskControl)
    {
        // TODO: SMS sending — implement when Twilio integration is ready
        /*
        $parametric = $this->generalModel->get_basic_search([
            'table' => 'parametric',
            'order' => 'id_parametric',
            'id'    => 'x',
        ]);
        $dato1 = decrypt($parametric[3]['value']);
        $dato2 = decrypt($parametric[4]['value']);

        $client = new \Twilio\Rest\Client($dato1, $dato2);

        $taskInfo = $this->externalModel->get_task_control(['idTaskControl' => $idTaskControl]);
        $jobInfo  = $this->generalModel->get_basic_search([
            'table'  => 'param_jobs',
            'order'  => 'job_description',
            'column' => 'id_job',
            'id'     => $idJob,
        ]);

        $mensaje  = "\n" . $jobInfo[0]['job_description'];
        $mensaje .= "\nClick the following link to fill the COVID FORM.";
        $mensaje .= "\n\n" . base_url('external/addTaskControl/' . $idJob . '/' . $idTaskControl);

        $client->messages->create(
            '+1' . $taskInfo[0]['contact_phone_number'],
            ['from' => '587 600 8948', 'body' => $mensaje]
        );
        */

        $data['linkBack'] = 'more/task_control/' . $idJob;
        $data['titulo']   = "<i class='fa fa-list'></i> COVID-19";
        $data['clase']    = 'alert-info';
        $data['msj']      = 'We have send the SMS to the Worker to fill the COVID form.';

        return $this->render('App\Views\template\answer', $data);
    }

    /**
     * Envio de mensaje para firmar FLHA
     * @since 14/4/2021
     * @author BMOTTAG
     * @review 05/05/2026 - new CI4 version
     */
    public function sendSMSFLHAWorker($idSafety, $idSafetySubcontractor = 'x')
    {
        // TODO: SMS sending — implement when Twilio integration is ready
        /*
        $parametric = $this->generalModel->get_basic_search([
            'table' => 'parametric',
            'order' => 'id_parametric',
            'id'    => 'x',
        ]);
        $dato1 = decrypt($parametric[3]['value']);
        $dato2 = decrypt($parametric[4]['value']);

        $client = new \Twilio\Rest\Client($dato1, $dato2);

        $infoSafety = $this->generalModel->get_safety([
            'idSafety'              => $idSafety,
            'movilNumber'           => true,
            'idSafetySubcontractor' => $idSafetySubcontractor,
        ]);
        $workers = $this->generalModel->get_safety_subcontractors_workers([
            'idSafety'              => $idSafety,
            'movilNumber'           => true,
            'idSafetySubcontractor' => $idSafetySubcontractor,
        ]);

        $mensaje  = 'VCI FLHA - ' . date('F j, Y', strtotime($infoSafety[0]['date']));
        $mensaje .= "\n" . $infoSafety[0]['job_description'];
        $mensaje .= "\nFollow the link, read the FLHA and sign.";
        $mensaje .= "\n\n" . base_url('safety/review_flha/' . $idSafety);

        if ($workers) {
            foreach ($workers as $worker) {
                $client->messages->create(
                    '+1' . $worker['worker_movil_number'],
                    ['from' => '587 600 8948', 'body' => $mensaje]
                );
            }
        }
        */

        $data['linkBack'] = 'safety/review_flha/' . $idSafety;
        $data['titulo']   = "<i class='fa fa-list'></i>FLHA - SMS";
        $data['clase']    = 'alert-info';
        $data['msj']      = 'You have send the SMS to Subcontractors';

        return $this->render('App\Views\template\answer', $data);
    }

    /**
     * Envio de mensaje para firmar - Excavation and Trenching Plan
     * @since 14/4/2021
     * @author BMOTTAG
     * @review 05/05/2026 - new CI4 version
     */
    public function sendSMSExcavationWorker($idExcavation, $idSubcontractor = 'x')
    {
        // TODO: SMS sending — implement when Twilio integration is ready
        /*
        $parametric = $this->generalModel->get_basic_search([
            'table' => 'parametric',
            'order' => 'id_parametric',
            'id'    => 'x',
        ]);
        $dato1 = decrypt($parametric[3]['value']);
        $dato2 = decrypt($parametric[4]['value']);
        $phone = $parametric[5]['value'];

        $client = new \Twilio\Rest\Client($dato1, $dato2);

        $information = $this->generalModel->get_excavation([
            'idExcavation'  => $idExcavation,
            'movilNumber'   => true,
            'idSubcontractor' => $idSubcontractor,
        ]);
        $workers = $this->generalModel->get_excavation_subcontractors([
            'idExcavation'  => $idExcavation,
            'movilNumber'   => true,
            'idSubcontractor' => $idSubcontractor,
        ]);

        $mensaje  = 'VCI Excavation and Trenching Plan - ' . date('F j, Y', strtotime($information[0]['date_excavation']));
        $mensaje .= "\n" . $information[0]['job_description'];
        $mensaje .= "\nFollow the link, read the Excavation and Trenching Plan and sign.";
        $mensaje .= "\n\n" . base_url('jobs/review_excavation/' . $idExcavation);

        if ($workers) {
            foreach ($workers as $worker) {
                $client->messages->create(
                    '+1' . $worker['worker_movil_number'],
                    ['from' => $phone, 'body' => $mensaje]
                );
            }
        }
        */

        $data['linkBack'] = 'jobs/review_excavation/' . $idExcavation;
        $data['titulo']   = "<i class='fa fa-pied-piper-alt'></i>Excavation and Trenching Plan - SMS";
        $data['clase']    = 'alert-info';
        $data['msj']      = 'You have send the SMS to Subcontractors';

        return $this->render('App\Views\template\answer', $data);
    }

    /**
     * Add an employee
     * @since 31/1/2022
     * @author BMOTTAG
     * @review 05/05/2026 - new CI4 version
     */
    public function newEmployee($key)
    {
        $filtro = 'uiAqv828TZr';
        if($filtro != $key) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Access denied.');
        }

        return $this->renderTopOnly('App\Modules\External\Views\form_employee', []);
    }

    /**
     * Save new employee
     * @since 31/01/2022
     * @author BMOTTAG
     * @review 05/05/2026 - new CI4 version
     */
    public function saveEmployee()
    {
        $post  = $this->request->getPost();
        $data  = [];

        $logUser = $post['user'] ?? '';
        $email   = $post['email'] ?? '';

        $resultUser  = $this->generalModel->verifyUser(['column' => 'log_user', 'value' => $logUser]);
        $resultEmail = $this->generalModel->verifyUser(['column' => 'email', 'value' => $email]);

        if ($resultUser || $resultEmail) {
            $data['status'] = 'error';

            if ($resultUser && $resultEmail) {
                $data['message'] = ' Error. The user name and email already exist.';
            } elseif ($resultUser) {
                $data['message'] = ' Error. The user name already exist.';
            } else {
                $data['message'] = ' Error. The email already exist.';
            }
        } else {
            if ($this->externalModel->saveEmployee($post)) {
                $passwd = str_replace(['<', '>', '[', ']', '*', '^', '-', "'", '='], '', $post['inputPassword'] ?? '');
                $firstName = $post['firstName'] ?? '';

                $msj  = 'Thank you for registering, an email was sent with the access data to the system.';
                $msj .= '<br><br><strong>User name: </strong>' . esc($logUser);
                $msj .= '<br><strong>Password: </strong>' . esc($passwd);

                // TODO: Email sending — implement when mail integration is ready
                /*
                $subjet   = 'Welcome to VCI';
                $to       = $email;
                $link     = base_url();
                $emailmsj = "<strong>APP Link: </strong><a href='{$link}'>VCI APP</a>";
                $emailmsj .= '<br><strong>User name: </strong>' . $logUser;
                $emailmsj .= '<br><strong>Password: </strong>' . $passwd;

                $mensaje = "<html><head><title>{$subjet}</title></head><body>
                    <p>Dear {$firstName}:</p>
                    <p>Thank you for registering, the following employees information is the access data to the system:</p>
                    <p>{$emailmsj}</p>
                    <p>Cordially,</p>
                    <p><strong>V-CONTRACTING INC</strong></p>
                </body></html>";

                $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
                $cabeceras .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
                $cabeceras .= 'From: VCI APP <info@v-contracting.ca>' . "\r\n";
                mail($to, $subjet, $mensaje, $cabeceras);
                */

                $data['status'] = 'success';
                $this->session->setFlashdata('retornoExito', $msj);
            } else {
                $data['status'] = 'error';
            }
        }

        return $this->response->setJSON($data);
    }

    /**
     * Form Checkin
     * @since 30/5/2022
     * @author BMOTTAG
     * @review 05/05/2026 - new CI4 version
     */
    public function checkin($idProject, $idCheckin = 'x')
    {
        $data['information'] = false;
        $data['idCheckin']   = false;

        $data['workers'] = $this->generalModel->get_basic_search([
            'table' => 'new_workers',
            'order' => 'worker_name',
            'id'    => 'x',
        ]);

        $data['jobInfo'] = $this->generalModel->get_basic_search([
            'table'  => 'param_jobs',
            'order'  => 'id_job',
            'column' => 'id_job',
            'id'     => $idProject,
        ]);

        $data['checkinList'] = $this->generalModel->get_checkin([
            'today' => date('Y-m-d'),
            'idJob' => $idProject,
        ]);

        if ($idCheckin !== 'x') {
            $data['idCheckin']   = $idCheckin;
            $data['checkinList'] = $this->generalModel->get_checkin(['idCheckin' => $idCheckin]);
        }

        return $this->renderTopOnly('App\Modules\External\Views\form_checkin', $data);
    }

    /**
     * Save Checkin
     * @since 1/6/2022
     * @author BMOTTAG
     * @review 05/05/2026 - new CI4 version
     */
    public function saveCheckin()
    {
        $post        = $this->request->getPost();
        $data        = [];
        $loginBefore = $post['login_before'] ?? '';
        $idWorker    = $post['id_name'] ?? null;
        $idJob       = $post['idProject'] ?? null;

        if ($loginBefore == 1) {
            if ($idCheckin = $this->externalModel->saveCheckin((int) $idWorker, $post)) {
                $data['result']   = true;
                $data['idCheckin'] = $idJob . '/' . $idCheckin;
                $this->session->setFlashdata('retornoExito', 'Welcome, work safe!');
            } else {
                $data['result']  = 'error';
                $data['mensaje'] = 'Error!!! Ask for help.';
                $this->session->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
            }
        } else {
            if ($idWorker = $this->externalModel->saveNewWorker($post)) {
                if ($idCheckin = $this->externalModel->saveCheckin($idWorker, $post)) {
                    $data['result']   = true;
                    $data['idCheckin'] = $idJob . '/' . $idCheckin;
                    $this->session->setFlashdata('retornoExito', 'Welcome, work safe!');
                } else {
                    $data['result']  = 'error';
                    $data['mensaje'] = 'Error!!! Ask for help.';
                    $this->session->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
                }
            } else {
                $data['result']  = 'error';
                $data['mensaje'] = 'Error!!! Ask for help.';
                $this->session->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
            }
        }

        return $this->response->setJSON($data);
    }

    /**
     * Cargo modal - formulario checkout
     * @since 4/06/2022
     * @review 05/05/2026 - new CI4 version
     */
    public function cargarModalCheckout()
    {
        $data['information'] = false;
        $data['idCheckin']   = $this->request->getPost('idCheckin');

        if ($data['idCheckin'] !== 'x') {
            $data['information'] = $this->generalModel->get_checkin(['idCheckin' => $data['idCheckin']]);
        }

        return $this->response
            ->setContentType('text/html')
            ->setBody(view('App\Modules\External\Views\checkout_modal', $data));
    }

    /**
     * Update checkin - checkout
     * @since 4/06/2022
     * @author BMOTTAG
     * @review 05/05/2026 - new CI4 version
     */
    public function saveCheckout()
    {
        $post    = $this->request->getPost();
        $idJob   = $post['idProject'] ?? null;
        $idCheckin = $post['hddId'] ?? null;
        $data    = ['idCheckin' => $idJob . '/' . $idCheckin];

        if ($this->externalModel->saveCheckout($post)) {
            $data['result'] = true;
            $this->session->setFlashdata('retornoExito', 'Thanks for coming, have a good day!');
        } else {
            $data['result'] = 'error';
            $this->session->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        return $this->response->setJSON($data);
    }

    /**
     * List Day Off, for ADMIN
     * @since 27/12/2022
     * @author BMOTTAG
     * @review 05/05/2026 - new CI4 version
     */
    public function aproveDayOff($idDayoff, $idUser)
    {
        $data['idDayoff']    = $idDayoff;
        $data['idUser']      = $idUser;
        $data['dayOffInfo']  = $this->generalModel->get_day_off($data);

        if (!$data['dayOffInfo']) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Day off not found.');
        }

        return $this->renderTopOnly('App\Modules\External\Views\dayoff', $data);
    }

    /**
     * Update dayoff status
     * @since 27/12/2022
     * @author BMOTTAG
     * @review 05/05/2026 - new CI4 version
     */
    public function updateDayoffStatus()
    {
        $post   = $this->request->getPost();
        $data   = ['return' => ($post['hddIdDayOff'] ?? '') . '/' . ($post['hddIdUser'] ?? '')];

        if ($this->externalModel->update_dayoff($post)) {
            $data['result'] = true;
            $this->session->setFlashdata('retornoExito', 'Information saved successfully!!');
        } else {
            $data['result'] = 'error';
            $this->session->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        return $this->response->setJSON($data);
    }
}
