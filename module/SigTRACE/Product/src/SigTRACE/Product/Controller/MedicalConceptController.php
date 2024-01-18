<?php
namespace SigTRACE\Product\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Session\Container\SessionContainer;

class MedicalConceptController extends AbstractActionController
{
    protected $_medicalConceptMapper;
    protected $_auditService;
    
    public function getAuditService()
    {
        if (!$this->_auditService) {
            $sm = $this->getServiceLocator();
            $this->_auditService = $sm->get('Audit\Service');
        }
        return $this->_auditService;
    }

    public function getMedicalConceptService()
    {
        if (!$this->_medicalConceptMapper) {
            $sm = $this->getServiceLocator();
            $this->_medicalConceptMapper = $sm->get('Product\Model\MedicalConcept');
        }
        return $this->_medicalConceptMapper;
    }
    
    public function isAdmin($trackerId) 
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $trackerContainer = $session->getSession('tracker');
        $userDetails = $userContainer->user_details;
        $roleId = isset($userDetails['group_id'])?$userDetails['group_id']:0; 
        $trackerUserGroups = $trackerContainer->tracker_user_groups;
        $sessionGroup = $trackerUserGroups[$trackerId]['session_group'];
        if ($roleId != 1 && $sessionGroup != "Administrator") {
            return $this->redirect()->toRoute('tracker');
        } 
        return true;
    }
    
    /*
     * function to show all listing of one particular tracker
     */

    public function medicalConceptManagementAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = (int) $this->getEvent()->getRouteMatch()->getParam('formId', 0);
            $activeSubstanceId = (int) $this->getEvent()->getRouteMatch()->getParam('activeSubstanceId', 0);
            if ($this->isAdmin($trackerId)) {
                $trackerResults = $this->getMedicalConceptService()->trackerResults($trackerId);
                $medicalConceptData = $this->getMedicalConceptService()->getAllMedicalConcepts('*', 'pt_archive=0 AND as_id='.$activeSubstanceId);
                $this->layout()->setVariables(array('tracker_id'=>$trackerId,'form_id'=>$formId));
                return new ViewModel(
                    array(
                        'trackerResults' => $trackerResults,
                        'medicalConceptData' => $medicalConceptData,
                        'activeSubstanceId' => $activeSubstanceId,                        
                        'trackerId' => $trackerId
                    )
                );
            }
        }
    }
    public function addAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = (int) $this->getEvent()->getRouteMatch()->getParam('formId', 0);
            $activeSubstanceId = (int) $this->getEvent()->getRouteMatch()->getParam('activeSubstanceId', 0);            
            if ($this->isAdmin($trackerId)) {
                $trackerResults = $this->getMedicalConceptService()->trackerResults($trackerId);
                $this->layout()->setVariables(array('tracker_id'=>$trackerId,'form_id'=>$formId));
                $preferredTerm = $this->getMedicalConceptService()->getAllMedicalConcepts('*', 'pt_archive=0 AND pt_type!="Medical Concept" AND as_id='.$activeSubstanceId.' AND (mc_id IS NULL OR mc_id="" OR mc_id=0)');//echo '<pre>';print_r($preferredTerm);die;
                return new ViewModel(
                    array(
                        'trackerResults' => $trackerResults,
                        'activeSubstanceId' => $activeSubstanceId,
                        'activeSubstanceId' => $activeSubstanceId,
                        'preferredTerm' => $preferredTerm,
                        'trackerId' => $trackerId
                    )
                );
            }                        
        }      
    }
    
    public function medicalConceptCheckAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $dataArr = $this->getRequest()->getPost()->toArray();
        $dataArr  = filter_var_array($dataArr, FILTER_SANITIZE_STRING);
        if (!isset($userContainer->u_id) && empty($dataArr)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {            
            $result = $this->getMedicalConceptService()->addMedicalConcept($dataArr, $userContainer);
            if ($result['responseCode'] > 0) {
                $this->getAuditService()->saveToLog(intval($result['actionId']), $userContainer->email, 'Add Medical Concept/Preferred Term', '', "{'pt_name':'".$dataArr['medicalConceptName']."','pt_type':'".(isset($dataArr['type']))?$dataArr['type']:''."','preferred_terms':{".json_encode($dataArr['preferredTermIds'])."}}", $dataArr['reason'], 'Success', $result['clientId']);
                $this->flashMessenger()->addMessage(array('success' => 'Medical Concept/Preferred Term added successfully!'));
            }
            $response->setContent(json_encode($result));
            return $response;
        }
            
    }
    
    public function editAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = (int) $this->getEvent()->getRouteMatch()->getParam('formId', 0);
            $activeSubstanceId = (int) $this->getEvent()->getRouteMatch()->getParam('activeSubstanceId', 0);
            $medicalConceptId = (int) $this->getEvent()->getRouteMatch()->getParam('medicalConceptId', 0);
            if ($this->isAdmin($trackerId)) {
                $trackerResults = $this->getMedicalConceptService()->trackerResults($trackerId);
                $this->layout()->setVariables(array('tracker_id'=>$trackerId,'form_id'=>$formId));
                $selectedMedicalConcept = $this->getMedicalConceptService()->getSelectedMedicalConcept($medicalConceptId);//echo '<pre>';print_r($selectedMedicalConcept);die;
                $preferredTerm = $this->getMedicalConceptService()->getAllMedicalConcepts('*', 'pt_archive=0 AND pt_type!="Medical Concept" AND as_id='.$activeSubstanceId.' AND (mc_id=0 OR mc_id="" OR mc_id IS NULL OR mc_id='.$medicalConceptId.') AND pt_id!='.$medicalConceptId);
                $selectedPreferredTerm = $this->getMedicalConceptService()->getAllMedicalConcepts('*', 'pt_archive=0 AND pt_type!="Medical Concept" AND as_id='.$activeSubstanceId.' AND mc_id='.$selectedMedicalConcept['0']['pt_id']);                
                return new ViewModel(
                    array(
                        'trackerResults' => $trackerResults,
                        'medicalConceptId' => $medicalConceptId,
                        'activeSubstanceId' => $activeSubstanceId,
                        'selectedMedicalConcept' => $selectedMedicalConcept,
                        'preferredTerm' => $preferredTerm,
                        'selectedPreferredTerm' => $selectedPreferredTerm,
                        'trackerId' => $trackerId
                    )
                );
            }
            
        }
    }
    
    public function saveEditMedicalConceptAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $dataArr = $this->getRequest()->getPost()->toArray();//echo '<pre>';print_r($dataArr);die;
        $dataArr  = filter_var_array($dataArr, FILTER_SANITIZE_STRING);
        if (!isset($userContainer->u_id) && empty($dataArr)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {            
            $prevMcName = $this->getMedicalConceptService()->getAllMedicalConcepts('pt_name,pt_type', 'pt_id='.$dataArr['medicalConceptId']);
            $prevPtIds = $this->getMedicalConceptService()->getAllMedicalConcepts('pt_id', 'mc_id='.$dataArr['medicalConceptId']);
            foreach ($prevPtIds as $key => $value) {
                $previousPt[] = $value['pt_id'];
            }
            if (empty($previousPt)) {
                $previousPt = array();
            }
            $result = $this->getMedicalConceptService()->saveMedicalConcept($dataArr, $userContainer, $prevMcName);
            $response->setContent(json_encode($result));
            if ($result['responseCode'] > 0) {
                $this->getAuditService()->saveToLog(intval($result['actionId']), $userContainer->email, 'Edit Medical Concept/Preferred Term', "{'pt_name':'".$prevMcName['0']['pt_name']."','pt_type':'".$prevMcName['0']['pt_type']."','preferred_terms':{".json_encode($previousPt)."}}", "{'pt_name':'".$dataArr['medicalConceptName']."','pt_type':'".$dataArr['type']."','preferred_terms':{".json_encode($dataArr['preferredTermIds'])."}}", $dataArr['reason'], 'Success', $result['clientId']);
                $this->flashMessenger()->addMessage(array('success' => 'Medical Concept/Preferred Term updated successfully!'));
            }
            return $response;
        }        
    }
    
    public function deleteMedicalConceptAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $dataArr = $this->getRequest()->getPost()->toArray();
        $dataArr  = filter_var_array($dataArr, FILTER_SANITIZE_STRING);
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {            
            $trackerId = (int) isset($dataArr['tracker_id'])?$dataArr['tracker_id']:'';
            $medicalConceptId = (int) isset($dataArr['pt_id'])?$dataArr['pt_id']:'';
            //$prevActSub = $this->getActiveSubstanceService()->getAllActiveSubstances('as_id='.$activeSubstancesId);
            $result = $this->getMedicalConceptService()->deleteMedicalConcept($trackerId, $medicalConceptId, $userContainer);
            if ($result['responseCode'] > 0) {
                $this->getAuditService()->saveToLog(intval($dataArr['pt_id']), $userContainer->email, 'Delete Medical Concept/Preferred Term', $dataArr['pt_name'], "", $dataArr['comment'], 'Success', $result['clientId']);
                $this->flashMessenger()->addMessage(array('success' => 'Medical Concept/Preferred Term deleted successfully!'));
                $response->setContent(\Zend\Json\Json::encode('deleted'));
            } else {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                $response->setContent(\Zend\Json\Json::encode('error'));
            }
            //$response->setContent(json_encode($result));
            return $response;
        }
    }
    
}
