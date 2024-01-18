<?php
namespace SigTRACE\Product\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

class LabeleventController extends AbstractActionController
{
    protected $_labeleventMapper;
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
    public function getLabeleventService()
    {
        if (!$this->_labeleventMapper) {
            $sm = $this->getServiceLocator();
            $this->_labeleventMapper = $sm->get('Product\Model\Labelevent');
        }
        return $this->_labeleventMapper;
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
        $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id'=>$formId));
        $productId = $this->getEvent()->getRouteMatch()->getParam('productId', 0); 
        $labelEvents = $this->getLabeleventService()->fetchAll(array('product_id'=>$productId), array());
        $view->setVariables(array('labelEvents'=>$labelEvents,'trackerId'=>$trackerId,'productId'=>$productId,'formId'=>$formId));
        $this->layout()->setVariables(array('tracker_id'=>$trackerId,'form_id'=>$formId));
        return $view;

    }
    public function addAction()
    {
        $view = new ViewModel();
        $trackerId = $this->getEvent()->getRouteMatch()->getParam('trackerId', 0); 
        //$this->layout()->setVariables(array('tracker_id' => $trackerId));
        $formId = $this->getEvent()->getRouteMatch()->getParam('formId', 0);
        $this->layout()->setVariables(array('tracker_id'=>$trackerId,'form_id'=>$formId));
        $productId = $this->getEvent()->getRouteMatch()->getParam('productId', 0); 
        $container = new Container('login');
        $userDetails = $container->user_details;
        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = array_merge_recursive($this->getRequest()->getPost()->toArray());
            if (!empty($post) && $post['product_id'] !== '') {
                switch($post['type']) {
                case 'm':
                    foreach ($post['event'] as $syn) {
                        $newId = $this->getLabeleventService()
                            ->add(
                                array('product_id'=>$post['product_id'],
                                         'le_name'=>$syn,
                                         'le_created_date'=> date('Y-m-d H:i:s'))
                            );
                        $this->getLogService()->add(
                            array('log_date'=>date('Y-m-d H:i:s'),
                                              'log_action' => 'Add',
                                              'log_type'=>'label-event',
                                              'log_new_value' => $syn,
                                              'log_type_id'=> $newId)
                        );
                        $this->getAuditService()->auditTrail($userDetails['u_name'], $userDetails['group_name'], 'Add', 'Label Event', $newId, $trackerId, $_SERVER['REMOTE_ADDR'], '', '"'.$syn.'"', 'Success');
                        $this->getCasedataModel()->updateQuantitativeAnalysis($trackerId, $formId, $productId);
                    }
                    break;
                case 'f':
                    $file = pathinfo($_FILES['file']['name']);
                    $changedFileName = $file['filename'].'_'.time().'.'.$file['extension'];
                    if ($file['extension'] == "csv") {
                        $ssPath = files.'label-event';
                        if (!file_exists($ssPath)) {
                            mkdir($ssPath, 0777, true); 
                        }
                        move_uploaded_file($_FILES["file"]["tmp_name"], $ssPath."/".$changedFileName);
                        $resList = $this->get2DArrayFromCsv($ssPath."/".$changedFileName, ',');
                        //array_shift($resList);
                        foreach ($resList as $res) {
                            if ($res[0] != '') {
                                $events = $this->getLabeleventService()->fetchAll(
                                    array('product_id'=>$post['product_id'],'le_name'=>$res[0],'le_archive'=>0), array()
                                );
                                if (count($events) == 0) {
                                    $newId = $this->getLabeleventService()->add(
                                        array('product_id'=>$post['product_id'],
                                        'le_name'=>$res[0],
                                        'le_created_date'=> date('Y-m-d H:i:s'))
                                    );
                                    $this->getLogService()->add(
                                        array('log_date'=>date('Y-m-d H:i:s'),
                                                  'log_action' => 'Add',
                                                  'log_type'=>'label-event',
                                                  'log_new_value' => $res[0],
                                                  'log_type_id'=> $newId)
                                    );
                                    $this->getAuditService()->auditTrail($userDetails['u_name'], $userDetails['group_name'], 'Add', 'Label Event', $newId, $trackerId, $_SERVER['REMOTE_ADDR'], '', '"'.$res[0].'"', 'Success');
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
            $msg = new Container('le_msg');
            $msg->msg = 'ADD';
            return $this->redirect()->toUrl('/product/label-event/'.$trackerId.'/'.$formId.'/'.$post['product_id']);
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
        $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id'=>$formId));
        $productId = $this->getEvent()->getRouteMatch()->getParam('productId', 0); 
        $eventId = $this->getEvent()->getRouteMatch()->getParam('labelId', 0);
        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = array_merge_recursive($this->getRequest()->getPost()->toArray());
            if (!empty($post) && $post['event'] !== '') {
                $this->getLabeleventService()->update(array('le_id'=>$post['le_id']), array('le_name' => $post['event']));
                $this->getLogService()->update(array('log_type'=>'label-event','log_type_id'=> $post['le_id']), array('log_active_status'=>0));
                $this->getLogService()->add(
                    array('log_date'=>date('Y-m-d H:i:s'),
                              'log_action' => 'Edit',
                              'log_type'=>'label-event',
                              'log_old_value'=> $post['oldEvent'],
                              'log_new_value' => $post['event'],
                              'log_type_id'=> $post['le_id'],
                    'log_active_status'=>1)
                );
                $this->getAuditService()->auditTrail($userDetails['u_name'], $userDetails['group_name'], 'Edit', 'Label Event', $post['le_id'], $trackerId, $_SERVER['REMOTE_ADDR'], $post['oldEvent'], $post['event'], 'Success');
                $this->getCasedataModel()->updateQuantitativeAnalysis($trackerId, $formId, $productId);
                $msg = new Container('le_msg');
                $msg->msg = 'EDIT';
                return $this->redirect()->toUrl('/product/label-event/'.$trackerId.'/'.$formId.'/'.$post['productId']); 
            }
        }
        if ((int)$eventId != '') {
            $res = $this->getLabeleventService()->fetchAll(array('le_id'=>$eventId), array('le_name','product_id'));
            $products = $this->getProductService()->getProductsList(array('product_id'=>$res[0]['product_id']), array('product_name'));
            $view->setVariables(array('id'=>$eventId,'name'=>$res[0]['le_name'],'productId'=>$res[0]['product_id'],'productName'=>$products[0]['product_name'],'trackerId'=>$trackerId,'formId'=>$formId));
        }
        return $view;

    }
    public function eventCheckAction()
    {
        $post = array_merge_recursive($this->getRequest()->getPost()->toArray());
        $response = $this->getResponse(); 
        if (!empty($post)) {
            $resp = 0; $errMessage = "";
            $eventName = $post['label'];
            $productId = (int)$post['id'];
            $eveId = isset($post['eveId'])?$post['eveId']:0;
            if ($eveId > 0) { 
                $where = array('product_id'=>$productId,'le_name'=>$eventName,'le_archive = 0','le_id !='.$eveId);
            } else {
                $where = array('product_id'=>$productId,'le_name'=>$eventName,'le_archive = 0');
            }
            $events = $this->getLabeleventService()->fetchAll($where, array());
            if (empty($events)) {
                $resp = 1;
                $errMessage = "Product is Available";
            } else {
                $resp = 0;
                $errMessage = "Product is Already Exist";
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
            $event = $this->getLabeleventService()->fetchAll(
                array('le_id'=>$post['id']), array('le_name')
            );
            $rows = $this->getLabeleventService()->update(array('le_id'=>$post['id']), array('le_archive' => 1,'le_archived_date'=>date('Y-m-d H:i:s'))); 
            if ($rows > 0) {
                $resp = 1;
            }
            $resultArr = array('responseCode' => $resp,'Affected Rows'=> $rows);
            echo json_encode($resultArr);
            $this->getAuditService()->auditTrail($userDetails['u_name'], $userDetails['group_name'], 'Delete', 'Label Event', $post['id'], $post['trackerId'], $_SERVER['REMOTE_ADDR'], '', $event[0]['le_name'], 'Success');
            $this->getCasedataModel()->updateQuantitativeAnalysis($trackerId, $formId, $productId);
            $msg = new Container('le_msg');
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
        $formId = $this->getEvent()->getRouteMatch()->getParam('formId', 0);
        $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id'=>$formId));
        $productId = $this->getEvent()->getRouteMatch()->getParam('productId', 0); 
        $labelId = $this->getEvent()->getRouteMatch()->getParam('labelId', 0); 
        if ((int)$labelId != 0) {
            $res = $this->getLogService()->fetch(
                array('log_type_id'=>$labelId,'log_type'=>'label-event'),
                array()
            );
            $view->setVariables(array('id'=>$labelId,'history'=>$res,'trackerId'=>$trackerId,'productId'=>$productId,'formId'=>$formId));
        }
        return $view;
    }
}
