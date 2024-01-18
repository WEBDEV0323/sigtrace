<?php
namespace SigTRACE\Product\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

class SynonymController extends AbstractActionController
{
    protected $_synonymMapper;
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

    public function getSynonymService()
    {
        if (!$this->_synonymMapper) {
            $sm = $this->getServiceLocator();
            $this->_synonymMapper = $sm->get('Product\Model\Synonym');
        }
        return $this->_synonymMapper;
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
        $this->layout()->setVariables(array('tracker_id' => $trackerId));
        $productId = $this->getEvent()->getRouteMatch()->getParam('productId', 0); 
        $synonyms = $this->getSynonymService()->fetchAll(array('product_id'=>$productId), array());
        $view->setVariables(array('synonyms'=>$synonyms,'trackerId'=>$trackerId,'productId'=>$productId,'formId'=>$formId));
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
                    foreach ($post['synonym'] as $syn) {
                        $newId = $this->getSynonymService()->add(
                            array('product_id'=>$post['product_id'],
                                                          'syn_name'=>$syn,
                                                          'syn_created_date'=> date('Y-m-d H:i:s'))
                        );
                        $this->getLogService()->add(
                            array('log_date'=>date('Y-m-d H:i:s'),
                                      'log_action' => 'Add',
                                      'log_type'=>'synonym',
                                      'log_new_value' => $syn,
                                      'log_type_id'=> $newId)
                        );
                        $this->getAuditService()->auditTrail($userDetails['u_name'], $userDetails['group_name'], 'Add', 'Synonym', $newId, $trackerId, $_SERVER['REMOTE_ADDR'], '', '"'.$syn.'"', 'Success');
                        $this->getCasedataModel()->updateQuantitativeAnalysis($trackerId, $formId, $productId);
                    }
                    break;
                case 'f':
                    $file = pathinfo($_FILES['file']['name']);
                    $changedFileName = $file['filename'].'_'.time().'.'.$file['extension'];
                    if ($file['extension'] == "csv") {
                        $ssPath = files.'synonym';
                        if (!file_exists($ssPath)) {
                            mkdir($ssPath, 0777, true); 
                        }
                        move_uploaded_file($_FILES["file"]["tmp_name"], $ssPath."/".$changedFileName);
                        $resList = $this->get2DArrayFromCsv($ssPath."/".$changedFileName, ',');
                        //array_shift($resList);
                        foreach ($resList as $res) {
                            if ($res[0] != '') {
                                $synonyms = $this->getSynonymService()->fetchAll(
                                    array('product_id'=>$post['product_id'],'syn_name'=>$res[0],'syn_archive'=>0), array()
                                );
                                if (count($synonyms) == 0) {
                                    $newId = $this->getSynonymService()->add(
                                        array('product_id'=>$post['product_id'],
                                        'syn_name'=>$res[0],
                                        'syn_created_date'=> date('Y-m-d H:i:s'))
                                    );
                                    $this->getLogService()->add(
                                        array('log_date'=>date('Y-m-d H:i:s'),
                                                  'log_action' => 'Add',
                                                  'log_type'=>'synonym',
                                                  'log_new_value' => $res[0],
                                                  'log_type_id'=> $newId)
                                    );
                                    $this->getAuditService()->auditTrail($userDetails['u_name'], $userDetails['group_name'], 'Add', 'Synonym', $newId, $trackerId, $_SERVER['REMOTE_ADDR'], '', '"'.$res[0].'"', 'Success');
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
            $msg = new Container('syn_msg');
            $msg->msg = 'ADD';
            return $this->redirect()->toUrl('/product/synonym/'.$trackerId.'/'.$formId.'/'.$post['product_id']);
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
        $synId = $this->getEvent()->getRouteMatch()->getParam('synonymId', 0); 
        $this->layout()->setVariables(array('tracker_id' => $trackerId));
        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = array_merge_recursive($this->getRequest()->getPost()->toArray());
            if (!empty($post) && $post['syn'] != '') {
                $this->getSynonymService()->update(array('syn_id'=>$post['synId']), array('syn_name' => $post['syn']));
                $this->getLogService()->update(array('log_type'=>'synonym','log_type_id'=> $post['synId']), array('log_active_status'=>0));
                $this->getLogService()->add(
                    array('log_date'=>date('Y-m-d H:i:s'),
                                                  'log_action' => 'Edit',
                                                  'log_type'=>'synonym',
                                                  'log_old_value'=> $post['oldSyn'],
                                                  'log_new_value' => $post['syn'],
                                                  'log_type_id'=> $post['synId'],
                    'log_active_status'=>1)
                );
                $this->getAuditService()->auditTrail($userDetails['u_name'], $userDetails['group_name'], 'Edit', 'Synonym', $post['synId'], $trackerId, $_SERVER['REMOTE_ADDR'], $post['oldSyn'], $post['syn'], 'Success');
                $this->getCasedataModel()->updateQuantitativeAnalysis($trackerId, $formId, $productId);
                $msg = new Container('syn_msg');
                $msg->msg = 'EDIT';
                return $this->redirect()->toUrl('/product/synonym/'.$trackerId.'/'.$formId.'/'.$post['productId']); 
            }
        }
        if ((int)$synId != '') {
            $res = $this->getSynonymService()->fetchAll(array('syn_id'=>$synId), array('syn_name','product_id'));
            $products = $this->getProductService()->getProductsList(array('product_id'=>$res[0]['product_id']), array('product_name'));
            $view->setVariables(array('id'=>$synId,'name'=>$res[0]['syn_name'],'trackerId'=>$trackerId,'formId'=>$formId,'productId'=>$res[0]['product_id'],'productName'=>$products[0]['product_name']));
        }
        return $view;

    }
    public function synonymCheckAction()
    {
        $post = array_merge_recursive($this->getRequest()->getPost()->toArray());
        $response = $this->getResponse(); 
        if (!empty($post)) {
            $resp = 0; $errMessage = "";
            $synonymName = $post['label'];
            $productId = (int)$post['id'];
            $synId = isset($post['synId'])?$post['synId']:0;
            if ($synId > 0) { 
                $where = array('product_id'=>$productId,'syn_name'=>$synonymName,'syn_id !='.$synId,'syn_archive = 0');
            } else {
                $where = array('product_id'=>$productId,'syn_name'=>$synonymName,'syn_archive = 0');
            }
            $synonyms = $this->getSynonymService()->fetchAll($where, array());
            if (empty($synonyms)) {
                $resp = 1;
                $errMessage = "Synonym is Available";
            } else {
                $resp = 0;
                $errMessage = "Synonym is Already Exist";
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
            $syn = $this->getSynonymService()->fetchAll(array('syn_id'=>$post['id']), array('syn_name'));
            $rows = $this->getSynonymService()->update(array('syn_id'=>$post['id']), array('syn_archive' => 1,'syn_archived_date'=>date('Y-m-d H:i:s'))); 
            if ($rows > 0) {
                $resp = 1;
            }
            $resultArr = array('responseCode' => $resp,'Affected Rows'=> $rows);
            echo json_encode($resultArr);
            $this->getAuditService()->auditTrail($userDetails['u_name'], $userDetails['group_name'], 'Delete', 'Synonym', $post['id'], $post['trackerId'], $_SERVER['REMOTE_ADDR'], '', $syn[0]['syn_name'], 'Success');
            $this->getCasedataModel()->updateQuantitativeAnalysis($trackerId, $formId, $productId);
            $msg = new Container('syn_msg');
            $msg->msg = 'DELETE';
        } else {
            echo "Access Denied";
        } 
        return $response;
        
    }
    public function historyAction()
    {
        $view = new ViewModel();
        $trackerId = $this->getEvent()->getRouteMatch()->getParam('trackerId', 0); 
        $productId = $this->getEvent()->getRouteMatch()->getParam('productId', 0); 
        $synonymId = $this->getEvent()->getRouteMatch()->getParam('synonymId', 0); 
        $formId = $this->getEvent()->getRouteMatch()->getParam('formId', 0);
        $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id'=>$formId));
        if ((int)$synonymId != 0) {
            $res = $this->getLogService()->fetch(array('log_type_id'=>$synonymId,'log_type'=>'synonym'), array());
            $view->setVariables(array('id'=>$synonymId,'history'=>$res,'trackerId'=>$trackerId,'productId'=>$productId,'formId'=>$formId));
        }
        return $view;
    }
}
