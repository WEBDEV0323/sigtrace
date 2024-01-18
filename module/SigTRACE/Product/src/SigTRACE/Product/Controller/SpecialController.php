<?php
namespace SigTRACE\Product\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

class SpecialController extends AbstractActionController
{
    protected $_specialMapper;
    protected $_productMapper;
    protected $_logMapper;
    protected $_auditMapper;
    protected $_caseMapper;
    public function getAuditService()
    {
        if (!$this->_auditMapper) {
            $sm = $this->getServiceLocator();
            $this->_auditMapper = $sm->get('Audit\Model\Audit');
        }
        return $this->_auditMapper;
    }
    public function getProductService()
    {
        if (!$this->_productMapper) {
            $sm = $this->getServiceLocator();
            $this->_productMapper = $sm->get('Product\Model\Product');
        }
        return $this->_productMapper;
    }
    public function getSpecialService()
    {
        if (!$this->_specialMapper) {
            $sm = $this->getServiceLocator();
            $this->_specialMapper = $sm->get('Product\Model\Special');
        }
        return $this->_specialMapper;
    }
    public function getLogService()
    {
        if (!$this->_logMapper) {
            $sm = $this->getServiceLocator();
            $this->_logMapper = $sm->get('Product\Model\Log');
        }
        return $this->_logMapper;
    }
    public function getImportModel()
    {
        if (!$this->_importMapper) {
            $sm = $this->getServiceLocator();
            $this->_importMapper = $sm->get('Settings\Model\Import');
        }
        return $this->_importMapper;
    }
    public function getCasedataModel()
    {
        if (!$this->_caseMapper) {
            $sm = $this->getServiceLocator();
            $this->_caseMapper = $sm->get('Casedata\Model\Casedata');
        }
        return $this->_caseMapper;
    }
    
    public function indexAction()
    {
        $view = new ViewModel();
        $trackerId = $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
        $formId = $this->getEvent()->getRouteMatch()->getParam('formId', 0);
        $productId = $this->getEvent()->getRouteMatch()->getParam('productId', 0); 
        $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id'=>$formId));
        $specialSituations = $this->getSpecialService()->fetchAll(array('product_id'=>$productId), array());
        $view->setVariables(array('specials'=>$specialSituations,'trackerId'=>$trackerId,'productId'=>$productId,'formId'=>$formId));
        $this->layout()->setVariables(array('tracker_id'=>$trackerId,'form_id'=>$formId));
        return $view;

    }
    public function addAction()
    {
        $view = new ViewModel();
        $container = new Container('login');
        $userDetails = $container->user_details;
        $trackerId = $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
        $formId = $this->getEvent()->getRouteMatch()->getParam('formId', 0);
        $productId = $this->getEvent()->getRouteMatch()->getParam('productId', 0); 
        $this->layout()->setVariables(array('tracker_id'=>$trackerId,'form_id'=>$formId));

        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = array_merge_recursive($this->getRequest()->getPost()->toArray());
            if (!empty($post) && $post['product_id'] != '') {
                switch($post['type']) {
                case 'm':
                    foreach ($post['situation'] as $special) {
                        $newId = $this->getSpecialService()
                            ->add(
                                array('product_id'=>$post['product_id'],
                                         'ss_name'=>$special)
                            );
                        $this->getLogService()->add(
                            array('log_date'=>date('Y-m-d H:i:s'),
                                              'log_action' => 'Add',
                                              'log_type'=>'special-situation',
                                              'log_new_value' => $special,
                                              'log_type_id'=> $newId)
                        );
                        $this->getAuditService()->auditTrail($userDetails['u_name'], $userDetails['group_name'], 'Add', 'Special Situation', $newId, $trackerId, $_SERVER['REMOTE_ADDR'], '', $special, 'Success');
                        $this->getCasedataModel()->updateQuantitativeAnalysis($trackerId, $formId, $productId);
                    }
                    break;
                case 'f':
                    $file = pathinfo($_FILES['file']['name']);
                    $changedFileName = $file['filename'].'_'.time().'.'.$file['extension'];
                    if ($file['extension'] == "csv") {
                        $ssPath = files.'special-situation';
                        if (!file_exists($ssPath)) {
                            mkdir($ssPath, 0777, true); 
                        }
                        move_uploaded_file($_FILES["file"]["tmp_name"], $ssPath."/".$changedFileName);
                        $resList = $this->get2DArrayFromCsv($ssPath."/".$changedFileName, ',');
                        //array_shift($resList);
                        foreach ($resList as $res) {
                            if ($res[0] != '') {
                                    $events = $this->getSpecialService()->fetchAll(
                                        array('product_id'=>$post['product_id'],'ss_name'=>$res[0],'ss_archive'=>0), array()
                                    );
                                if (count($events) == 0) {
                                    $newId = $this->getSpecialService()->add(
                                        array('product_id'=>$post['product_id'],
                                        'ss_name'=>$res[0])
                                    );
                                    $this->getLogService()->add(
                                        array('log_date'=>date('Y-m-d H:i:s'),
                                        'log_action' => 'Add',
                                        'log_type'=>'special-situation',
                                        'log_new_value' => $res[0],
                                        'log_type_id'=> $newId)
                                    );
                                    $this->getAuditService()->auditTrail($userDetails['u_name'], $userDetails['group_name'], 'Add', 'Special Situation', $newId, $trackerId, $_SERVER['REMOTE_ADDR'], '', $res[0], 'Success');
                                }
                            }
                        } 
                    } 
                    $this->getCasedataModel()->updateQuantitativeAnalysis($trackerId, $formId, $productId);
                    break;
                default:
                    break;
                }
            } 
            $msg = new Container('ss_msg');
            $msg->msg = 'ADD';
            return $this->redirect()->toUrl('/product/special_situation/'.$trackerId.'/'.$formId.'/'.$post['product_id']);
        }
        $products = $this->getProductService()->getProductsList(array('tracker_id'=>$trackerId), array());
        $view->setVariables(array('products'=>$products,'productId'=>$productId,'trackerId'=>$trackerId,'formId'=>$formId));
        return $view;

    }
    public function get2DArrayFromCsv($file, $delimiter)
    {
        $data2DArray = array();
        if (($handle = fopen($file, "r")) !== false) {
            $i = 0;
            while (($lineArray = fgetcsv($handle, 10000, $delimiter)) !== false) {
                for ($j = 0; $j < count($lineArray); $j++) {
                    $data2DArray[$i][$j] = $lineArray[$j];
                }
                $i++;
            }
            fclose($handle);
        }
        return $data2DArray;
    }   
    public function editAction()
    {
        $view = new ViewModel();
        $container = new Container('login');
        $userDetails = $container->user_details;
        $trackerId = $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
        $formId = $this->getEvent()->getRouteMatch()->getParam('formId', 0);
        $productId = $this->getEvent()->getRouteMatch()->getParam('productId', 0); 
        $situationId = $this->getEvent()->getRouteMatch()->getParam('situationId', 0); 
        $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id'=>$formId));

        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = array_merge_recursive($this->getRequest()->getPost()->toArray());
            if (!empty($post) && $post['situation'] != '') {
                $this->getSpecialService()->update(array('ss_id'=>$post['ss_id']), array('ss_name' => $post['situation']));
                $this->getLogService()->update(array('log_type'=>'special-situation','log_type_id'=> $post['ss_id']), array('log_active_status'=>0));
                $this->getLogService()->add(
                    array('log_date'=>date('Y-m-d H:i:s'),
                              'log_action' => 'Edit',
                              'log_type'=>'special-situation',
                              'log_old_value'=> $post['oldSituation'],
                              'log_new_value' => $post['situation'],
                              'log_type_id'=> $post['ss_id'],
                    'log_active_status'=>1)
                );
                $this->getAuditService()->auditTrail($userDetails['u_name'], $userDetails['group_name'], 'Edit', 'Special Situation', $post['ss_id'], $trackerId, $_SERVER['REMOTE_ADDR'], $post['oldSituation'], $post['situation'], 'Success');
                $this->getCasedataModel()->updateQuantitativeAnalysis($trackerId, $formId, $productId);
                $msg = new Container('ss_msg');
                $msg->msg = 'EDIT';
                return $this->redirect()->toUrl('/product/special_situation/'.$trackerId.'/'.$formId.'/'.$post['productId']); 
            }
        }
        if ((int)$situationId != 0) {
            $res = $this->getSpecialService()->fetchAll(array('ss_id'=>$situationId), array('ss_name','product_id'));
            $products = $this->getProductService()->getProductsList(array('product_id'=>$res[0]['product_id']), array('product_name'));
            $view->setVariables(array('id'=>$situationId,'name'=>$res[0]['ss_name'],'productId'=>$res[0]['product_id'],'productName'=>$products[0]['product_name'],'trackerId'=>$trackerId,'formId'=>$formId));
        }
        return $view;

    }
    public function situationCheckAction()
    {
        $post = array_merge_recursive($this->getRequest()->getPost()->toArray());
        $response = $this->getResponse(); 
        if (!empty($post)) {
            $resp = 0; $errMessage = "";
            $sitName = $post['label'];
            $productId = (int)$post['id'];
            $sitId = isset($post['sitId'])?$post['sitId']:0;
            if ($sitId > 0) { 
                $where = array('product_id'=>$productId,'ss_name'=>$sitName,'ss_archive = 0','ss_id !='.$sitId);
            } else {
                $where = array('product_id'=>$productId,'ss_name'=>$sitName,'ss_archive = 0');
            }
            $situations = $this->getSpecialService()->fetchAll($where, array());
            if (empty($situations)) {
                $resp = 1;
                $errMessage = "Special Situation is Available";
            } else {
                $resp = 0;
                $errMessage = "Special Situation is Already Exist";
            }

            $resultArr = array('responseCode' => $resp, 'errMessage' => $errMessage);
            echo json_encode($resultArr);
        } else {
            echo "Access Denied";
        } 
        return $response;
    }
    public function deleteAction()
    {
        $container = new Container('login');
        $userDetails = $container->user_details;
        $post = array_merge_recursive($this->getRequest()->getPost()->toArray());
        $resp = 0;
        $trackerId = $post['trackerId'];
        $formId = $post['formId'];
        $productId = $post['productId'];
        $response = $this->getResponse(); 
        if (!empty($post)) {
            $ss = $this->getSpecialService()->fetchAll(array('ss_id'=>$post['id']), array('ss_name'));
            $rows = $this->getSpecialService()->update(array('ss_id'=>$post['id']), array('ss_archive' => 1,'ss_archived_date'=>date('Y-m-d H:i:s'))); 
            if ($rows > 0) {
                $resp = 1;
            }
            $resultArr = array('responseCode' => $resp,'Affected Rows'=> $rows);
            echo json_encode($resultArr);
            $this->getAuditService()->auditTrail($userDetails['u_name'], $userDetails['group_name'], 'Delete', 'Special Situation', $post['id'], $post['trackerId'], $_SERVER['REMOTE_ADDR'], '', $ss[0]['ss_name'], 'Success');
            $this->getCasedataModel()->updateQuantitativeAnalysis($trackerId, $formId, $productId);
            $msg = new Container('ss_msg');
            $msg->msg = 'DELETE';
        } else {
            $this->getAuditService()->auditTrail($userDetails['u_name'], $userDetails['group_name'], 'Delete', 'Special Situation', 0, 0, $_SERVER['REMOTE_ADDR'], '', '', 'Failure');
            echo "Access Denied";
        } 
        return $response;
        
    }
    public function historyAction()
    {
        $view = new ViewModel();
        $trackerId = $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
        $formId = $this->getEvent()->getRouteMatch()->getParam('formId', 0);
        $productId = $this->getEvent()->getRouteMatch()->getParam('productId', 0); 
        $situationId = $this->getEvent()->getRouteMatch()->getParam('situationId', 0); 
        $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id'=>$formId));
        if ((int)$situationId != 0) {
            $res = $this->getLogService()->fetch(
                array('log_type_id'=>$situationId,'log_type'=>'special-situation'),
                array()
            );
            $view->setVariables(array('id'=>$situationId,'history'=>$res,'trackerId'=>$trackerId,'productId'=>$productId,'formId'=>$formId));
        }
        return $view;
    }

}
