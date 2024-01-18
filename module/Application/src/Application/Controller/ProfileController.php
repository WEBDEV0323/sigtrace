<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Notification\Controller\Email;

class ProfileController extends AbstractActionController
{
    protected $_login;

    public function getAdminService()
    {
        if (!$this->_login) {
            $sm = $this->getServiceLocator();
            $this->_login = $sm->get('Application\Model\Profile');
        }
        return $this->_login;
    }
    public function indexAction()
    {
        $key=$this->getEvent()->getRouteMatch()->getParam('id');
        $post = $this->getRequest()->getPost()->toArray();
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        $request = $this->getRequest();
        $response = $this->getResponse();
        if ($post) {
            $key=  $post['key'];
        }
        $res=$this->getAdminService()->checkifKeyexist($key);
        if ($post && $res=='exist') {
            $response=0;
            if ($post['password']!==$post['confirmpassword']) {
                $response=1;
            } elseif ($post['password']=='') {
                $response=2;
            } elseif ($post['confirmpassword']=='') {
                $response=3;
            }
            if ($response==0) {
                $res=$this->getAdminService()->savepassword($post);
                echo $res;
                die;
            } else {
                echo $response;
                die;
            }
        }

        return new ViewModel(
            array(
                'key' => $key,
                'exist'=>$res
            )
        );
    }

    public function resetpasswordAction()
    {
        $response = $this->getResponse();
        $request = $this->getRequest();
        try{
            if ($request->isPost()) {
                $post = array_merge_recursive($this->getRequest()->getPost()->toArray());
                if (isset($post['u_name']) && $post['u_name'] != '') {
                    $post['u_name'] = filter_var($post['u_name'], FILTER_SANITIZE_EMAIL);
                    $result = $this->getAdminService()->checkifUserexist($post['u_name']);
                    if ($result == 'exist') {
                        $result = $this->getAdminService()->checkifUserisBioclinicaUser($post['u_name']);
                        if ($result == 'Normal') {
                            $res = $this->getAdminService()->resetpassword($post['u_name']);
                            $this->forward()->dispatch(
                                'Notification\Controller\Email',
                                array(
                                'action' => 'setpasswordmail',
                                'param1' => $post['u_name'],
                                'param2' => $res
                                )
                            );
                            $response->setContent(1);
                            return $response;
                        } else {
                            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_406);
                            $response->setContent('Bioclinica user cannot reset password.');
                            return $response;
                        }
                    } else {
                        $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_406);
                        $response->setContent('Not Acceptable');
                        return $response;
                    }
                } else {
                    $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_406);
                    $response->setContent('Not Acceptable');
                    return $response;
                }
            }
        } catch (\Zend\Db\Adapter\Exception $e) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_406);
            $response->setContent('Not Acceptable');
            return $response;

        } catch (\Exception $e) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_406);
            $response->setContent('Not Acceptable');
            return $response;
        }
    }


    public function changepasswordAction()
    {
        session_start();
        $post = $this->getRequest()->getPost()->toArray();
        $userDetails = $_SESSION['user_details'];
        $email=$userDetails['email'];
        if (!isset($_SESSION['u_id'])) {
            return $this->redirect()->toRoute('home');
        } else {
            if (isset($post) && !empty($post)) {
                $result = $this->getAdminService()->checkifPasswordmatch($email, $post);
                if ($result == 'exist') {
                    $res = $this->getAdminService()->changepassword($post, $email);
                    echo 1;
                    die;
                } else {
                    echo 2;
                    die;
                }
            }

            return new ViewModel(
                array()
            );
        }
    }
    
}
