<?php
namespace App\Modules\More\Controllers;

use App\Controllers\BaseController;
use App\Modules\More\Models\MoreModel;
use App\Models\GeneralModel;
use TCPDF;

class More extends BaseController
{
    protected $moreModel;
    protected $generalModel;

    public function __construct()
    {
        $this->moreModel   = new MoreModel();
        $this->generalModel = new GeneralModel();
    }

    public function environmental($idJob)
    {
        $data['jobInfo']     = $this->generalModel->get_basic_search(['table' => 'param_jobs', 'order' => 'job_description', 'column' => 'id_job', 'id' => $idJob]);
        $data['information'] = $this->moreModel->get_environmental(['idJob' => $idJob]);

        return $this->render('App\Modules\More\Views\environmental_list', $data);
    }

    public function add_environmental($idJob, $idEnvironmental = 'x')
    {
        $data['information'] = false;
        $data['jobInfo']     = $this->generalModel->get_basic_search(['table' => 'param_jobs', 'order' => 'job_description', 'column' => 'id_job', 'id' => $idJob]);
        $data['workersList'] = $this->generalModel->get_user(['state' => 1]);

        if ($idEnvironmental != 'x') {
            $data['information'] = $this->moreModel->get_environmental(['idJob' => $idJob]);
            if (!$data['information']) {
                throw new \CodeIgniter\Exceptions\PageNotFoundException('Record not found.');
            }
        }

        return $this->render('App\Modules\More\Views\form_environmental', $data);
    }

    public function save_environmental()
    {
        $post    = $this->request->getPost();
        $idUser  = $this->session->get('id');
        $data    = ['idRecord' => $post['hddIdJob'] ?? null];

        if ($idEnvironmental = $this->moreModel->add_environmental($post, $idUser)) {
            $data['result']          = true;
            $data['mensaje']         = 'You have saved the Environmental Site Inspection, continue uploading the information.';
            $data['idEnvironmental'] = $idEnvironmental;
            session()->setFlashdata('retornoExito', 'You have saved the Environmental Site Inspection, continue uploading the information!!');
        } else {
            $data['result']          = 'error';
            $data['mensaje']         = 'Error!!! Ask for help.';
            $data['idEnvironmental'] = '';
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        return $this->response->setJSON($data);
    }

	/**
	 * Signature
	 * param $type: supervisor / worker
	 * param $id: llave principal del formulario
	 * @since 5/1/2018
	 * @author BMOTTAG
	 * @review 21/04/2026 - new CI4 version
	 */
	public function add_signature_esi()
	{
		$imageData = $this->request->getPost('image'); 
		$id = $this->request->getPost('extraValue'); 
		$type = $this->request->getPost('otherValue'); 

        $fileName = $type . '_' . $id . ".png";
        $arrParam = [
            "table" => "job_environmental",
            "primaryKey" => "id_job_environmental",
            "id" => $id,
            "column" => $type . '_signature',
            "value" => 'images/signature/esi/' . $fileName
        ];

		$filePath = WRITEPATH . '../public/images/signature/esi/' . $fileName;

		if(!$imageData){
			return redirect()->back()->with('error', 'No signature provided.');
		}

		$imageData = str_replace('data:image/png;base64,', '', $imageData);
		$imageData = str_replace(' ', '+', $imageData);

		if(!is_dir(dirname($filePath))) mkdir(dirname($filePath), 0755, true);

		if(file_put_contents($filePath, base64_decode($imageData))){
			$this->generalModel->updateRecord($arrParam);
			return redirect()->back()->with('retornoExito', 'Signature saved successfully.');
		} else {
			return redirect()->back()->with('retornoError', 'Error saving signature.');
		}
	}

    public function generaEnvironmentalPDF($idJob)
    {
		$pdf = new TCPDF();

		$pdf->SetCreator('VCI');
		$pdf->SetAuthor('VCI');
		$pdf->SetTitle('ESI Report');

		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);

		// 👇 espacio para logo
		$pdf->SetMargins(10, 25, 10);
		$pdf->SetAutoPageBreak(TRUE, 10);

		$pdf->SetFont('dejavusans', '', 8);

		$data = [
			'info' => $this->moreModel->get_environmental(['idJob' => $idJob])			
		];

		$pdf->AddPage();

		// LOGO
		$logo = FCPATH . 'images/logo.png';

		if (is_file($logo)) {
			$pdf->Image($logo, 10, 8, 30);
		}

        $html = view('App\Modules\More\Views\reporte_esi', $data);
		$pdf->writeHTML($html, true, false, true, false, '');

		$pdf->lastPage();

		$filename = 'esi_' . $idJob . '.pdf';

		return $this->response
			->setHeader('Content-Type', 'application/pdf')
			->setBody($pdf->Output($filename, 'I'));
    }

    public function ppe_inspection()
    {
        $data['information'] = $this->moreModel->get_ppe_inspection([]);

        return $this->render('App\Modules\More\Views\ppe_inspection_list', $data);
    }

    public function cargarModalPPEInspection()
    {
        $data['information']     = false;
        $data['idPPEInspection'] = $this->request->getPost('idPPEInspection');

        if ($data['idPPEInspection'] != 'x') {
            $data['information'] = $this->generalModel->get_basic_search([
                'table'  => 'templates',
                'order'  => 'id_template',
                'column' => 'id_template',
                'id'     => $data['idPPEInspection'],
            ]);
        }

        return $this->response
            ->setContentType('text/html')
            ->setBody(view('App\Modules\More\Views\ppe_modal', $data));
    }

    public function save_ppe_inspection()
    {
        $post            = $this->request->getPost();
        $idUser          = $this->session->get('id');
        $userRol         = $this->session->get('rol');
        $idPPEInspection = $post['hddId'] ?? '';

        $msj = $idPPEInspection != '' ? 'You have updated a PPE Inspection!!' : 'You have added a new PPE Inspection';
        $data = [];

        if ($id = $this->moreModel->savePPEInspection($post, $idUser, $userRol)) {
            $data['result']   = true;
            $data['idRecord'] = $id;
            session()->setFlashdata('retornoExito', $msj);
        } else {
            $data['result']   = 'error';
            $data['idRecord'] = '';
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        return $this->response->setJSON($data);
    }

    public function add_ppe_inspection($idPPEInspection = 'x')
    {
        $data['information'] = false;

        if ($idPPEInspection != 'x') {
            $data['information']          = $this->moreModel->get_ppe_inspection(['idPPEInspection' => $idPPEInspection]);
            $data['ppeInspectionWorkers'] = $this->moreModel->get_ppe_inspection_workers($idPPEInspection);
            $data['workersList']          = $this->generalModel->get_user(['state' => 1]);

            if (!$data['information']) {
                throw new \CodeIgniter\Exceptions\PageNotFoundException('Record not found.');
            }
        }

        return $this->render('App\Modules\More\Views\form_ppe_inspection', $data);
    }

    public function add_signature($typo, $idPPEInspection, $idWorker)
    {
        if (empty($typo) || empty($idPPEInspection) || empty($idWorker)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Invalid parameters.');
        }

        if ($this->request->getMethod() === 'post') {
            if ($typo == 'inspector') {
                $name     = 'images/signature/ppe_inspection/' . $typo . '_' . $idPPEInspection . '.png';
                $arrParam = ['table' => 'ppe_inspection', 'primaryKey' => 'id_ppe_inspection', 'id' => $idPPEInspection, 'column' => 'inspector_signature', 'value' => $name];
                $data['linkBack'] = 'more/add_ppe_inspection/' . $idPPEInspection;
            } else {
                $name     = 'images/signature/ppe_inspection/' . $typo . '_' . $idWorker . '.png';
                $arrParam = ['table' => 'ppe_inspection_workers', 'primaryKey' => 'id_ppe_inspection_worker', 'id' => $idWorker, 'column' => 'signature', 'value' => $name];
                $data['linkBack'] = 'more/add_ppe_inspection/' . $idPPEInspection . '#anclaWorker';
            }

            $dataUri      = $this->request->getPost('image');
            $encodedImage = explode(',', $dataUri)[1];
            file_put_contents(FCPATH . $name, base64_decode($encodedImage));

            $data['titulo'] = "<i class='fa fa-life-saver fa-fw'></i>SIGNATURE";
            if ($this->generalModel->updateRecord($arrParam)) {
                $data['clase'] = 'alert-success';
                $data['msj']   = 'Good job, you have saved your signature.';
            } else {
                $data['clase'] = 'alert-danger';
                $data['msj']   = 'Ask for help.';
            }

            return $this->render('App\Views\template\answer', $data);
        }

        return $this->response->setBody(view('template/make_signature'));
    }

    public function add_workers_ppe_inspection($idPPEInspection)
    {
        if (empty($idPPEInspection)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Invalid parameters.');
        }

        $data['workersList']    = $this->generalModel->get_user(['state' => 1]);
        $data['idPPEInspection'] = $idPPEInspection;

        return $this->render('App\Modules\More\Views\form_add_workers', $data);
    }

    public function save_ppe_inspection_workers()
    {
        $post            = $this->request->getPost();
        $idPPEInspection = $post['hddIdPPEInpection'] ?? null;
        $data            = ['idRecord' => $idPPEInspection];
        $workers         = $post['workers'] ?? [];

        if ($this->moreModel->add_ppe_inspection_worker($idPPEInspection, $workers)) {
            $data['result'] = true;
            session()->setFlashdata('retornoExito', 'You have added the Workers, remember to get the signature of each one.');
        } else {
            $data['result'] = 'error';
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        return $this->response->setJSON($data);
    }

    public function deleteInspectionWorker()
    {
        $identificador = $this->request->getPost('identificador');
        $porciones     = explode('-', $identificador);
        $idPPEInspection       = $porciones[0];
        $idPPEInspectionWorker = $porciones[1];

        $data = ['idRecord' => $idPPEInspection];
        $arrParam = ['table' => 'ppe_inspection_workers', 'primaryKey' => 'id_ppe_inspection_worker', 'id' => $idPPEInspectionWorker];

        if ($this->generalModel->deleteRecord($arrParam)) {
            $data['result'] = true;
            session()->setFlashdata('retornoExito', 'You have deleted one worker.');
        } else {
            $data['result'] = 'error';
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        return $this->response->setJSON($data);
    }

    public function updateInspection()
    {
        $post            = $this->request->getPost();
        $idPPEInspection = $post['hddIdPPEInspection'] ?? null;

        if ($this->moreModel->update_ppe_inspection_worker($post)) {
            session()->setFlashdata('retornoExito', 'You have updated the Inspection');
        } else {
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        return redirect()->to(base_url('more/add_ppe_inspection/' . $idPPEInspection));
    }

    public function add_one_worker()
    {
        $post            = $this->request->getPost();
        $idPPEInspection = $post['hddIdPPEInspection'] ?? null;

        if ($this->moreModel->addOneWorker($post)) {
            session()->setFlashdata('retornoExito', 'You have added one Worker.');
        } else {
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        return redirect()->to(base_url('more/add_ppe_inspection/' . $idPPEInspection));
    }

    public function generaPPEInspectionPDF($idPPEInspection)
    {
        $data['info']                 = $this->moreModel->get_ppe_inspection(['idPPEInspection' => $idPPEInspection]);
        $data['ppeInspectionWorkers'] = $this->moreModel->get_ppe_inspection_workers($idPPEInspection);

        $pdf = $this->_buildPDF('PPE INSPECTION REPORT');
        $pdf->AddPage();
        $html = view('App\Modules\More\Views\reporte_ppe_inspection', $data);
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->lastPage();
        $pdf->Output('ppe_inspection_' . $idPPEInspection . '.pdf', 'I');
    }

    public function video($tema)
    {
        $data = [];
        if ($tema == 'jobs') {
            $data['titulo'] = 'Jobs Info intro';
            $data['enlace'] = 'http://youtu.be/ESHRC4o8Hnk';
        }

        return $this->render('App\Modules\More\Views\video', $data);
    }

    public function confined($idJob)
    {
        $data['jobInfo']     = $this->generalModel->get_basic_search(['table' => 'param_jobs', 'order' => 'job_description', 'column' => 'id_job', 'id' => $idJob]);
        $data['information'] = $this->generalModel->get_confined_space(['idJob' => $idJob]);

        return $this->render('App\Modules\More\Views\confined_list', $data);
    }

    public function add_confined($idJob, $idConfined = 'x')
    {
        $data['information'] = false;
        $data['jobInfo']     = $this->generalModel->get_basic_search(['table' => 'param_jobs', 'order' => 'job_description', 'column' => 'id_job', 'id' => $idJob]);
        $data['workersList'] = $this->generalModel->get_user(['state' => 1]);

        if ($idConfined != 'x') {
            $data['information']    = $this->generalModel->get_confined_space(['idConfined' => $idConfined]);
            $data['confinedWorkers'] = $this->moreModel->get_confined_workers($idConfined, null);

            if (!$data['information']) {
                throw new \CodeIgniter\Exceptions\PageNotFoundException('Record not found.');
            }
        }

        return $this->render('App\Modules\More\Views\form_confined', $data);
    }

    public function save_confined()
    {
        $post    = $this->request->getPost();
        $idUser  = $this->session->get('id');
        $userRol = $this->session->get('rol');
        $data    = ['idRecord' => $post['hddIdJob'] ?? null];

        if ($idConfined = $this->moreModel->add_confined($post, $idUser, $userRol)) {
            $data['result']    = true;
            $data['mensaje']   = 'You have saved the Confined Space Entry Permit, continue uploading the information.';
            $data['idConfined'] = $idConfined;
            session()->setFlashdata('retornoExito', 'You have saved the Confined Space Entry Permit, continue uploading the information. Add Worker(s) in charge of entry and signatures at the end of the form.');
        } else {
            $data['result']    = 'error';
            $data['mensaje']   = 'Error!!! Ask for help.';
            $data['idConfined'] = '';
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        return $this->response->setJSON($data);
    }

    public function confined_workers($idJob, $idConfined)
    {
        $data['information']    = false;
        $data['jobInfo']        = $this->generalModel->get_basic_search(['table' => 'param_jobs', 'order' => 'job_description', 'column' => 'id_job', 'id' => $idJob]);
        $data['workersList']    = $this->generalModel->get_user(['state' => 1]);

        if ($idConfined != 'x') {
            $data['information']    = $this->generalModel->get_confined_space(['idConfined' => $idConfined]);
            $data['confinedWorkers'] = $this->moreModel->get_confined_workers($idConfined, 2);

            if (!$data['information']) {
                throw new \CodeIgniter\Exceptions\PageNotFoundException('Record not found.');
            }
        }

        return $this->render('App\Modules\More\Views\form_confined_workers', $data);
    }

    public function workers_site($idJob, $idConfined)
    {
        $data['information']    = false;
        $data['jobInfo']        = $this->generalModel->get_basic_search(['table' => 'param_jobs', 'order' => 'job_description', 'column' => 'id_job', 'id' => $idJob]);
        $data['workersList']    = $this->generalModel->get_user(['state' => 1]);

        if ($idConfined != 'x') {
            $data['information']    = $this->generalModel->get_confined_space(['idConfined' => $idConfined]);
            $data['confinedWorkers'] = $this->moreModel->get_confined_workers($idConfined, 1);

            if (!$data['information']) {
                throw new \CodeIgniter\Exceptions\PageNotFoundException('Record not found.');
            }
        }

        return $this->render('App\Modules\More\Views\form_confined_workers_site', $data);
    }

    public function add_workers_confined($idJob, $idConfined, $wos)
    {
        if (empty($idJob) || empty($idConfined)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Invalid parameters.');
        }

        $data['workersList'] = $this->generalModel->get_user(['state' => 1]);
        $data['idConfined']  = $idConfined;
        $data['idJob']       = $idJob;
        $data['wos']         = $wos;

        return $this->render('App\Modules\More\Views\form_add_workers_confined', $data);
    }

    public function save_confined_workers()
    {
        $post      = $this->request->getPost();
        $idConfined = $post['hddIdConfined'] ?? null;
        $idJob      = $post['hddIdJob'] ?? null;
        $wos        = $post['hddWOS'] ?? null;
        $workers    = $post['workers'] ?? [];

        $data = ['idRecord' => $wos == 2 ? 'confined_workers/' . $idJob . '/' . $idConfined : 'workers_site/' . $idJob . '/' . $idConfined];

        if ($this->moreModel->add_confined_worker($idConfined, $wos, $workers)) {
            $data['result']  = true;
            $data['mensaje'] = 'Solicitud guardada correctamente.';
            session()->setFlashdata('retornoExito', 'You have added the Workers, remember to get the signature of each one.');
        } else {
            $data['result']  = 'error';
            $data['mensaje'] = 'Error al guardar. Intente nuevamente.';
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        return $this->response->setJSON($data);
    }

    public function deleteConfinedWorker($idJob, $idConfined, $idConfinedWorker)
    {
        if (empty($idJob) || empty($idConfined) || empty($idConfinedWorker)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Invalid parameters.');
        }

        $arrParam = ['table' => 'job_confined_workers', 'primaryKey' => 'id_job_confined_worker', 'id' => $idConfinedWorker];
        if ($this->generalModel->deleteRecord($arrParam)) {
            session()->setFlashdata('retornoExito', 'You have deleted one worker.');
        } else {
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        return redirect()->to(base_url('more/confined_workers/' . $idJob . '/' . $idConfined));
    }

    public function deleteConfinedWorkerSite($idJob, $idConfined, $idConfinedWorker)
    {
        if (empty($idJob) || empty($idConfined) || empty($idConfinedWorker)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Invalid parameters.');
        }

        $arrParam = ['table' => 'job_confined_workers', 'primaryKey' => 'id_job_confined_worker', 'id' => $idConfinedWorker];
        if ($this->generalModel->deleteRecord($arrParam)) {
            session()->setFlashdata('retornoExito', 'You have deleted one worker.');
        } else {
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        return redirect()->to(base_url('more/workers_site/' . $idJob . '/' . $idConfined));
    }

    public function confined_One_Worker()
    {
        $post      = $this->request->getPost();
        $idJob     = $post['hddIdJob'] ?? null;
        $idConfined = $post['hddIdConfined'] ?? null;

        if ($this->moreModel->confinedSaveOneWorker($post)) {
            session()->setFlashdata('retornoExito', "You have added one Worker. Don't forget to sign.");
        } else {
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        return redirect()->to(base_url('more/confined_workers/' . $idJob . '/' . $idConfined));
    }

    public function confined_worker_site()
    {
        $post      = $this->request->getPost();
        $idJob     = $post['hddIdJob'] ?? null;
        $idConfined = $post['hddIdConfined'] ?? null;

        if ($this->moreModel->confinedSaveWorkerOnSite($post)) {
            session()->setFlashdata('retornoExito', "You have added one Worker. Don't forget to sign.");
        } else {
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        return redirect()->to(base_url('more/workers_site/' . $idJob . '/' . $idConfined));
    }

    public function add_signature_confined($typo, $idJob, $idConfined, $idWorker)
    {
        if (empty($typo) || empty($idConfined) || empty($idWorker)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Invalid parameters.');
        }

        if ($this->request->getMethod() === 'post') {
            switch ($typo) {
                case 'authorization':
                    $name     = 'images/signature/confined/' . $typo . '_' . $idWorker . '.png';
                    $arrParam = ['table' => 'job_confined', 'primaryKey' => 'id_job_confined', 'id' => $idConfined, 'column' => 'authorization_signature', 'value' => $name];
                    $data['linkBack'] = 'more/add_confined/' . $idJob . '/' . $idConfined . '#anclaSignature';
                    break;
                case 'worker':
                    $name     = 'images/signature/confined/' . $typo . '_' . $idWorker . '.png';
                    $this->moreModel->updateConfinedWorkerInOut(['id' => $idWorker, 'column' => 'date_time_in']);
                    $arrParam = ['table' => 'job_confined_workers', 'primaryKey' => 'id_job_confined_worker', 'id' => $idWorker, 'column' => 'signature', 'value' => $name];
                    $data['linkBack'] = 'more/confined_workers/' . $idJob . '/' . $idConfined . '#anclaWorker';
                    break;
                case 'worker_out':
                    $name     = 'images/signature/confined/' . $typo . '_' . $idWorker . '.png';
                    $this->moreModel->updateConfinedWorkerInOut(['id' => $idWorker, 'column' => 'date_time_out']);
                    $arrParam = ['table' => 'job_confined_workers', 'primaryKey' => 'id_job_confined_worker', 'id' => $idWorker, 'column' => 'signature_out', 'value' => $name];
                    $data['linkBack'] = 'more/confined_workers/' . $idJob . '/' . $idConfined . '#anclaWorker';
                    break;
                case 'cancellation':
                    $name     = 'images/signature/confined/' . $typo . '_' . $idWorker . '.png';
                    $arrParam = ['table' => 'job_confined', 'primaryKey' => 'id_job_confined', 'id' => $idConfined, 'column' => 'cancellation_signature', 'value' => $name];
                    $data['linkBack'] = 'more/add_confined/' . $idJob . '/' . $idConfined . '#anclaSignature';
                    break;
                case 'post_entry':
                    $name     = 'images/signature/confined/' . $typo . '_' . $idWorker . '.png';
                    $arrParam = ['table' => 'job_confined', 'primaryKey' => 'id_job_confined', 'id' => $idConfined, 'column' => 'post_entry_signature', 'value' => $name];
                    $data['linkBack'] = 'more/post_entry/' . $idJob . '/' . $idConfined;
                    break;
                default:
                    throw new \CodeIgniter\Exceptions\PageNotFoundException('Invalid type.');
            }

            $dataUri      = $this->request->getPost('image');
            $encodedImage = explode(',', $dataUri)[1];
            file_put_contents(FCPATH . $name, base64_decode($encodedImage));

            $data['titulo'] = "<i class='fa fa-life-saver fa-fw'></i>SIGNATURE";
            if ($this->generalModel->updateRecord($arrParam)) {
                $data['clase'] = 'alert-success';
                $data['msj']   = 'Good job, you have saved your signature.';
            } else {
                $data['clase'] = 'alert-danger';
                $data['msj']   = 'Ask for help.';
            }

            return $this->render('App\Views\template\answer', $data);
        }

        return $this->response->setBody(view('template/make_signature'));
    }

    public function update_confined_worker()
    {
        $post      = $this->request->getPost();
        $idJob     = $post['hddIdJob'] ?? null;
        $idConfined = $post['hddIdConfined'] ?? null;

        if ($this->moreModel->saveConfinedWorker($post)) {
            session()->setFlashdata('retornoExito', 'You have updated the record!!');
        } else {
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        return redirect()->to(base_url('more/confined_workers/' . $idJob . '/' . $idConfined));
    }

    public function re_testing($idJob, $idConfined)
    {
        $data['jobInfo']     = $this->generalModel->get_basic_search(['table' => 'param_jobs', 'order' => 'job_description', 'column' => 'id_job', 'id' => $idJob]);
        $data['information'] = $this->generalModel->get_confined_space(['idConfined' => $idConfined]);
        $data['info']        = $this->moreModel->get_confined_re_testing(['idConfined' => $idConfined]);

        return $this->render('App\Modules\More\Views\form_confined_re_testing', $data);
    }

    public function cargarModalRetesting()
    {
        $data['information'] = false;
        $data['idConfined']  = $this->request->getPost('idConfined');
        $data['idRetesting'] = $this->request->getPost('idRetesting');

        if ($data['idRetesting'] != 'x') {
            $result = $this->moreModel->get_confined_re_testing(['idRetesting' => $data['idRetesting']]);
            $data['information'] = $result;
            $data['idConfined']  = $result[0]['fk_id_job_confined'];
        }

        $infoConfined  = $this->generalModel->get_confined_space(['idConfined' => $data['idConfined']]);
        $data['idJob'] = $infoConfined[0]['fk_id_job'];

        return $this->response
            ->setContentType('text/html')
            ->setBody(view('App\Modules\More\Views\re_testing_modal', $data));
    }

    public function save_re_testing()
    {
        $post      = $this->request->getPost();
        $idConfined = $post['hddIdConfined'] ?? null;
        $idJob      = $post['hddIdJob'] ?? null;
        $data       = ['idRecord' => $idJob . '/' . $idConfined];

        if ($this->moreModel->saveRetesting($post)) {
            $data['result'] = true;
            session()->setFlashdata('retornoExito', 'You have saved the ENVIRONMENTAL CONDITIONS - RE TESTING');
        } else {
            $data['result'] = 'error';
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        return $this->response->setJSON($data);
    }

    public function post_entry($idJob, $idConfined)
    {
        $data['jobInfo']     = $this->generalModel->get_basic_search(['table' => 'param_jobs', 'order' => 'job_description', 'column' => 'id_job', 'id' => $idJob]);
        $data['workersList'] = $this->generalModel->get_user(['state' => 1]);
        $data['information'] = $this->generalModel->get_confined_space(['idConfined' => $idConfined]);

        return $this->render('App\Modules\More\Views\form_confined_post_entry', $data);
    }

    public function save_post_entry()
    {
        $post      = $this->request->getPost();
        $idJob     = $post['hddIdJob'] ?? null;
        $idConfined = $post['hddConfined'] ?? null;
        $data       = ['idRecord' => $idJob . '/' . $idConfined];

        if ($this->moreModel->save_post_entry($post)) {
            $data['result']  = true;
            $data['mensaje'] = 'You have saved the Post-entry Inspection.';
            session()->setFlashdata('retornoExito', 'You have saved the Post-entry Inspection.');
        } else {
            $data['result']  = 'error';
            $data['mensaje'] = 'Error!!! Ask for help.';
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        return $this->response->setJSON($data);
    }

    public function rescue_plan($idJob, $idConfined)
    {
        $data['information'] = false;
        $data['jobInfo']     = $this->generalModel->get_basic_search(['table' => 'param_jobs', 'order' => 'job_description', 'column' => 'id_job', 'id' => $idJob]);
        $data['workersList'] = $this->generalModel->get_user(['state' => 1]);

        if ($idConfined != 'x') {
            $data['information']    = $this->generalModel->get_confined_space(['idConfined' => $idConfined]);
            $data['confinedWorkers'] = $this->moreModel->get_confined_workers($idConfined, 1);

            if (!$data['information']) {
                throw new \CodeIgniter\Exceptions\PageNotFoundException('Record not found.');
            }
        }

        return $this->render('App\Modules\More\Views\form_confined_rescue_plan', $data);
    }

    public function save_rescue_plan()
    {
        $post      = $this->request->getPost();
        $idJob     = $post['hddIdJob'] ?? null;
        $idConfined = $post['hddConfined'] ?? null;
        $data       = ['idRecord' => $idJob . '/' . $idConfined];

        if ($this->moreModel->save_rescue_plan($post)) {
            $data['result']  = true;
            $data['mensaje'] = 'You have saved the ON-SITE RESCUE PLAN.';
            session()->setFlashdata('retornoExito', 'You have saved the ON-SITE RESCUE PLAN.');
        } else {
            $data['result']  = 'error';
            $data['mensaje'] = 'Error!!! Ask for help.';
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        return $this->response->setJSON($data);
    }

    public function generaConfinedPDF($idConfined)
    {
        $data['info']          = $this->generalModel->get_confined_space(['idConfined' => $idConfined]);
        $data['confinedWorkers'] = $this->moreModel->get_confined_workers($idConfined, 2);
        $data['WorkersOnSite'] = $this->moreModel->get_confined_workers($idConfined, 1);
        $data['retesting']     = $this->moreModel->get_confined_re_testing(['idConfined' => $idConfined]);

        $pdf = $this->_buildPDF('Confined space entry permit report');
        $pdf->AddPage();
        $html = view('App\Modules\More\Views\reporte_confined', $data);
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->lastPage();
        ob_end_clean();
        $pdf->Output('confined_' . $idConfined . '.pdf', 'I');
    }

    public function task_control($idJob)
    {
        $data['jobInfo']     = $this->generalModel->get_basic_search(['table' => 'param_jobs', 'order' => 'job_description', 'column' => 'id_job', 'id' => $idJob]);
        $data['information'] = $this->moreModel->get_task_control(['idJob' => $idJob]);

        return $this->render('App\Modules\More\Views\task_control_list', $data);
    }

    public function add_task_control($idJob, $idTaskControl = 'x')
    {
        $data['information'] = false;
        $data['jobInfo']     = $this->generalModel->get_basic_search(['table' => 'param_jobs', 'order' => 'job_description', 'column' => 'id_job', 'id' => $idJob]);
        $data['companyList'] = $this->generalModel->get_basic_search(['table' => 'param_company', 'order' => 'company_name', 'column' => 'company_type', 'id' => 2]);

        if ($idTaskControl != 'x') {
            $data['information'] = $this->moreModel->get_task_control(['idTaskControl' => $idTaskControl]);
            if (!$data['information']) {
                throw new \CodeIgniter\Exceptions\PageNotFoundException('Record not found.');
            }
        }

        return $this->render('App\Modules\More\Views\form_task_control', $data);
    }

    public function save_task_control()
    {
        $post   = $this->request->getPost();
        $idUser = $this->session->get('id');
        $data   = ['idRecord' => $post['hddIdJob'] ?? null];

        if ($idTaskControl = $this->moreModel->add_task_control($post, $idUser)) {
            $data['result']        = true;
            $data['mensaje']       = "You have saved the Task Assessment and Control, don't forget to sign..";
            $data['idTaskControl'] = $idTaskControl;
            session()->setFlashdata('retornoExito', "You have saved the Task Assessment and Control, don't forget to sign.!!");
        } else {
            $data['result']        = 'error';
            $data['mensaje']       = 'Error!!! Ask for help.';
            $data['idTaskControl'] = '';
            session()->setFlashdata('retornoError', '<strong>Error!!!</strong> Ask for help');
        }

        return $this->response->setJSON($data);
    }

    public function add_signature_tac($typo, $idJob, $idTaskControl)
    {
        if (empty($typo) || empty($idJob) || empty($idTaskControl)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Invalid parameters.');
        }

        if ($this->request->getMethod() === 'post') {
            $name     = 'images/signature/tac/' . $typo . '_' . $idTaskControl . '.png';
            $arrParam = ['table' => 'job_task_control', 'primaryKey' => 'id_job_task_control', 'id' => $idTaskControl, 'column' => $typo . '_signature', 'value' => $name];
            $data['linkBack'] = 'more/add_task_control/' . $idJob . '/' . $idTaskControl;

            $dataUri      = $this->request->getPost('image');
            $encodedImage = explode(',', $dataUri)[1];
            file_put_contents(FCPATH . $name, base64_decode($encodedImage));

            $data['titulo'] = "<i class='fa fa-life-saver fa-fw'></i>SIGNATURE";
            if ($this->generalModel->updateRecord($arrParam)) {
                $data['clase'] = 'alert-success';
                $data['msj']   = 'Good job, you have saved your signature.';
            } else {
                $data['clase'] = 'alert-danger';
                $data['msj']   = 'Ask for help.';
            }

            return $this->render('App\Views\template\answer', $data);
        }

        return $this->response->setBody(view('template/make_signature'));
    }

    public function generaTaskControlPDF($idTaskControl)
    {
        $data['info'] = $this->moreModel->get_task_control(['idTaskControl' => $idTaskControl]);

        $pdf = $this->_buildPDF('Task Control Report');
        $pdf->AddPage();
        $html = view('App\Modules\More\Views\reporte_task_control', $data);
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->lastPage();
        $pdf->Output('tac_' . $idTaskControl . '.pdf', 'I');
    }

    private function _buildPDF($title)
    {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('VCI');
        $pdf->SetTitle($title);
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, '', PDF_HEADER_STRING, [0, 64, 255], [0, 64, 128]);
        $pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
        $pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->SetFont('dejavusans', '', 8);
        return $pdf;
    }
}
