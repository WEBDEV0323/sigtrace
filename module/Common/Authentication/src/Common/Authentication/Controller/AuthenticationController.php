<?php
namespace Common\Authentication\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use \Firebase\JWT\JWT;
use Session\Container\SessionContainer;
use UnexpectedValueException;

class AuthenticationController extends AbstractActionController
{
    protected $_auditService;
    protected $_authService;
    
    
    public function getAuditService()
    {
        if (!$this->_auditService) {
            $sm = $this->getServiceLocator();
            $this->_auditService = $sm->get('Audit\Service');
        }
        return $this->_auditService;
    }
    
    public function getAuthService()
    {
        if (!$this->_authService) {
            $sm = $this->getServiceLocator();
            $this->_authService = $sm->get('Auth\Service');
        }
        return $this->_authService;
    }
    
    public function indexAction()
    {
        $session = new SessionContainer();
        $userSession = $session->getSession('user');
        $session->setSession('activeTime', array('timestamp'=>time()));
        if (isset($userSession->u_id)) {
            return $this->redirect()->toRoute('tracker');
        } else {
            // $configData = $this->getAuthService()->getConfigParams();           
            // try {
            //     $authUrl = $configData['authorization_endpoint'];
            //     $clientId = $configData['client_id'];
            //     $responseType = $configData['response_type'];
            //     $redirectUri = $configData['redirect_uri'];
            //     $responseMode = $configData['response_mode'];
            //     $scope = $configData['scope'];
                
            //     $Url = $authUrl."?";
            //     $Url .= "client_id=".$clientId;
            //     $Url .= "&response_type=".$responseType;
            //     $Url .= "&redirect_uri=".$redirectUri;
            //     $Url .= "&response_mode=".$responseMode;
            //     $Url .= "&scope=".$scope;
            //     return $this->redirect()->toUrl($Url);
            // } catch (Exception $ex) {
            //     echo "Caught exception $ex\n"; exit;
            // }  

            $userInfo = array(
                'unique_name'   => '\\suma.k',
                'u_name'        => 'suma.k',
                'upn'           => 'suma.k@qinecsa.com',
                'email'         => 'suma.k@qinecsa.com',
                'password'      => '123456', //'e10adc3949ba59abbe56e057f20f883e',
                'group_id'      => '2',
                'group_name'    => 'Normal',
                'iss'           => 'https://qinawsfs.qinecsa.com/adfs'
            );

            $results = $this->getAuthService()->login($userInfo);

            if ($results['statusCode'] == 200) {
                
                return new ViewModel();
                $this->getAuditService()->saveToLog(0, isset($userinfo['upn'])?$userinfo['upn']:'', 'login', '', '', 'login', 'login succeed', 0);
                $trackers = $this->getAuthService()->dashboardResults();
                $dashUrls = $this->getAuthService()->getDashboardUrl();
                if ($trackers['trackerCounts'] == 1 && !empty($dashUrls)) {
                    $depArray = $dashUrls['dependent_on'] != ''? explode(",", $dashUrls['dependent_on']):array();
                    $urlParams = $dashUrls['action_url'];
                    foreach ($depArray as $Id) {
                        $urlParams .= '/'.$trackers['trackers'][0][$Id];
                    }

                    return $this->redirect()->toUrl($urlParams); 
                }
                return $this->redirect()->toRoute('tracker');
            } else {
                $this->getAuditService()->saveToLog(0, isset($userinfo['upn'])?$userinfo['upn']:'', 'login', '', '', 'login failed: User not configured', 'login failed', 0);
                return $this->redirect()->toUrl('/auth/errorpage');
            }
        }
    }
    
    public function oauthredirectAction()
    {
        //to get Access token and userinfo using id_token.
        try {            
            $configData = $this->getAuthService()->getConfigParams();           
            $clientId = $configData['client_id'];
            $clientSecret = $configData['client_secret'];
            $grantType = $configData['grant_type'];
            $redirectUri =$configData['redirect_uri'];
            $get = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);            
            $accesscode = isset($get['code'])?$get['code']:"code";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $configData['token_endpoint']);
            curl_setopt($ch, CURLOPT_POST, 1);            
            curl_setopt(
                $ch, CURLOPT_POSTFIELDS,
                "grant_type=".$grantType."&client_id=".$clientId."&redirect_uri=".$redirectUri."&code=".$accesscode."&client_secret=".urlencode($clientSecret)
            );
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $server_output = curl_exec($ch);
            curl_close($ch); 
            $jsonoutput = json_decode($server_output, true);
            $tks = isset($jsonoutput['id_token'])?explode('.', $jsonoutput['id_token']):array();
            if (count($tks) != 3) {
                throw new UnexpectedValueException('Wrong number of segments');
            }
            //list($headb64, $bodyb64, $cryptob64) = $tks;
            $chkey = curl_init();
            curl_setopt($chkey, CURLOPT_URL, $configData['publickey_endpoint']);            
            curl_setopt($chkey, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
            curl_setopt($chkey, CURLOPT_RETURNTRANSFER, true);
            $serverOutputKey = curl_exec($chkey);
            curl_close($chkey);
            $jsonoutputKey = json_decode($serverOutputKey, true);
            $publicKey = "-----BEGIN CERTIFICATE-----" . "\n" . wordwrap($jsonoutputKey['keys'][0]['x5c'][0], 64, "\n", true) . "\n" . "-----END CERTIFICATE-----";
            $payload = JWT::decode($jsonoutput['id_token'], $publicKey, array($jsonoutputKey['keys'][0]['alg']));  

            $idokenArr = Array (
                        'id_token' => $jsonoutput['id_token'],
                        'publickey'=>$publicKey,
                        'alg'=>$jsonoutputKey['keys'][0]['alg']
                    );
            $this->authenticationAction($payload, $idokenArr);    
        } catch (Exception $ex) {
            echo "Caught exception $ex\n";
            exit;
        }
    }

    public function authenticationAction($data, $idokenArr=array())
    {
        $session = new SessionContainer();
        $configData = $this->getAuthService()->getConfigParams();
        $logoutUrl = $configData['end_session_endpoint'];
        $session->setSession('config', array('config' => $configData));
        $session->setSession('config', array('idokenArr' => $idokenArr));
        if (is_object($data)) {
            $userinfo = (array) $data;
            if (!empty($userinfo)) {
                $results = $this->getAuthService()->login($userinfo);
                if ($results['statusCode'] == 200) {
                    $this->getAuditService()->saveToLog(0, isset($userinfo['upn'])?$userinfo['upn']:'', 'login', '', '', 'login', 'login succeed', 0);
                    $trackers = $this->getAuthService()->dashboardResults();
                    $dashUrls = $this->getAuthService()->getDashboardUrl();
                    if ($trackers['trackerCounts'] == 1 && !empty($dashUrls)) {
                        $depArray = $dashUrls['dependent_on'] != ''? explode(",", $dashUrls['dependent_on']):array();
                        $urlParams = $dashUrls['action_url'];
                        foreach ($depArray as $Id) {
                            $urlParams .= '/'.$trackers['trackers'][0][$Id];
                        }
                        return $this->redirect()->toUrl($urlParams); 
                    }
                    return $this->redirect()->toRoute('tracker');
                } else {
                    $this->getAuditService()->saveToLog(0, isset($userinfo['upn'])?$userinfo['upn']:'', 'login', '', '', 'login failed: User not configured', 'login failed', 0);
                    return $this->redirect()->toUrl('/auth/errorpage');
                }
            } else {
                $this->getAuditService()->saveToLog(0, isset($userinfo['upn'])?$userinfo['upn']:'', 'login', '', '', 'login failed: Access Denied', 'login failed', 0);
                return $this->redirect()->toUrl($logoutUrl);
            }
        } else {
            $this->getAuditService()->saveToLog(0, isset($userinfo['upn'])?$userinfo['upn']:'', 'login', '', '', 'login failed: Access Denied', 'login failed', 0);
            return $this->redirect()->toUrl($logoutUrl);
        }
    }
    
    public function healthcheckAction()
    {
        http_response_code(200);die;
    }
    
    public function errorpageAction()
    {
        return new ViewModel(
            array(
            'message' => 'This user is not available in application. Please contact administrator.',
            )
        );
    }
    
    public function logoutAction()
    {
        try {
            $session = new SessionContainer();
            $userSession = $session->getSession('user');
            $configData = $this->getAuthService()->getConfigParams(); 
            if (isset($userSession->u_id)) {
                $this->getAuthService()->logout();
            }
            $logoutUrl = $configData['end_session_endpoint'];
            if (isset($userSession->user_details) && !empty($userSession->user_details)) {
                $userDetails = $userSession->user_details;
                $session->clearSession('user');
                $session->clearSession('config');
                $session->clearSession('tracker');
                $session->clearSession('trackerids');
                $session->clearSession('eventData');
                $this->getAuditService()->saveToLog(0, isset($userDetails['email'])?$userDetails['email']:'', 'logout', '', '', 'logout', 'logout success', 0);
                return $this->redirect()->toUrl($logoutUrl);
            } else {
                $this->getAuditService()->saveToLog(0, isset($userDetails['email'])?$userDetails['email']:'', 'logout', '', '', 'logout because userDetails not found', 'userDetails not found', 0);
                return $this->redirect()->toUrl($logoutUrl);
            }
        } catch (Exception $ex) {
            echo "Caught exception $ex\n";
                exit;
        }
    }
}
