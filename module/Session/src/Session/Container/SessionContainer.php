<?php
namespace Session\Container;

use Zend\Session\Container;
use \Firebase\JWT\JWT;
//use Application\Controller\IndexController;
use Common\Audit\Model\Audit;

class SessionContainer
{
    
    public function __construct() 
    {
        $payload = '';
        $auditLog = new Audit();
        if (!isset($_SESSION)) {
            session_start();
        } else if (isset($_SESSION['config'])) {
            $idokenArr = $_SESSION['config']['idokenArr'];
            try {
                $payload = JWT::decode($idokenArr['id_token'], $idokenArr['publickey'], array($idokenArr['alg']));          
            } catch (InvalidArgumentException $ex) {
            
            } catch (UnexpectedValueException  $ex) {
            
            } catch (SignatureInvalidException  $ex) {
            
            } catch (BeforeValidException  $ex) {
            
            } catch (ExpiredException  $ex) {
            

            } catch (DomainException  $ex) {
            
            } catch (Exception $ex) {
                

            } finally {
                if (!is_object($payload)) {
                    $userDetails = $_SESSION['user'];
                    $url = $_SESSION['config']['end_session_endpoint'];
                    $sessionContainer = new Container();
                    $sessionContainer->getManager()->getStorage()->clear('user');
                    $sessionContainer->getManager()->getStorage()->clear('config');
                    $sessionContainer->getManager()->getStorage()->clear('tracker');
                    $sessionContainer->getManager()->getStorage()->clear('eventData');
                    $auditLog->saveToLog(0, isset($userDetails['email'])?$userDetails['email']:'', 'logout', '', '', 'token expired: logout', 'logout success', 0);   
                    header("Location: ".$url); //redirect to logout url
                    exit;    
                }
            }

        }
    }
    public function setSession($sessionName = '', $sessionValue = '')
    { 
        $sessionContainer = new Container($sessionName);
        if (is_array($sessionValue)) {
            foreach ($sessionValue as $key => $value) {
                $_SESSION[$key] = $value;
                $sessionContainer->$key = $value;
            } 
        } else if ($sessionName != '' && $sessionValue != '') {
            $_SESSION[$sessionName] = $sessionValue;
            $sessionContainer->$sessionName = $sessionValue;
        }
    }
    
    public function getSession($sessionName)
    { 
        $sessionContainer = new Container($sessionName);
        return $sessionContainer;
    }
    
    public function updateSession($sessionName, $sessionValue = '')
    {
        $sessionContainer = new Container($sessionName);
        if (is_array($sessionValue)) {
            foreach ($sessionValue as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        if (is_array($v)) {
                            foreach ($v as $x => $y) {
                                $_SESSION[$key][$k][$x] = $y;
                                $sessionContainer->$key[$k][$x] = $y; 
                            }
                        } else {
                            $_SESSION[$key][$k] = $v;
                            $sessionContainer->$key[$k] = $v;
                        } 
                    }
                } else {
                    $_SESSION[$key] = $value;
                    $sessionContainer->$key = $value;
                }
               
            } 
        } else if ($sessionName != '' && $sessionValue != '') {
            $_SESSION[$sessionName] = $sessionValue;
            $sessionContainer->$sessionName = $sessionValue;
        }
    }
    
    public function clearSession($name)
    { 
        $sessionContainer = new Container($name);
        $sessionContainer->getManager()->getStorage()->clear($name);
        unset($_SESSION[$name]);
    }
    
}




