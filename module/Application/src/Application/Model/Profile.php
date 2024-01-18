<?php
namespace Application\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Sql;

class Profile
{
    protected $_adapter;
    protected $_serviceLocator;

    /**
     * Make the Adapter object avilable as local prtected variable
     *
     * @param Adapter $adapter - DB PDO PgSQL conn
     */
    public function __construct(Adapter $adapter)
    {
        //        $this->adapter = $adapter;
        $this->_adapter = $adapter;
    }

    public function resetpassword($email)
    {
        $query = "select * from user where email='$email'";
        $statements = $this->_adapter->query($query);
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
            $userkey=$this->_genKey(20);
            $id=$arr[0]['u_id'];
            $qry= "update user us
                    set
                    us.user_publickey='$userkey'
                    where us.u_id=$id";
            $statements = $this->_adapter->query($qry);
            $statements->execute();
            return $userkey;
        }
    }

    public function changepassword($data, $email)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('settings')->where(array("status" => "Yes"));
        $newadpater = $this->_adapter;
        $selectString = $sql->getSqlStringForSqlObject($select);
        $SettingsResults = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
        $SettingsArr = array();
        $SettingsResultSet = new ResultSet;
        $SettingsResultSet->initialize($SettingsResults);
        $SettingsResultSet->buffer();
        $SettingsResultSetCount = $SettingsResultSet->count();
        if ($SettingsResultSetCount > 0) {
            $SettingsArr = $SettingsResultSet->toArray();
            $SettingsResultSetArr = $SettingsArr[0];
            $pwdIteartions = $SettingsResultSetArr['password_iterations'];
            $pwdIteartionLength = $SettingsResultSetArr['password_iteration_length'];
        } else {
            $pwdIteartions = 1000;
            $pwdIteartionLength = 32;
        }

        $query = "select * from user where email='$email' and LOWER(user.user_type) = 'normal'";
        $statements = $this->_adapter->query($query);
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
            $id=$arr[0]['u_id'];
            $qry= "update user us
                    set
                    us.user_password='".$this->createHash($data['newpassword'], $arr[0]['user_salt'], $pwdIteartions, $pwdIteartionLength)."'
                    where us.u_id=$id";
            //us.user_password='". md5($data['newpassword']) . $arr[0]['user_salt']."'
            $statements = $this->_adapter->query($qry);
            $statements->execute();
            return 1;
        }
    }


    public function savepassword($data)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('settings')->where(array("status" => "Yes"));
        $newadpater = $this->_adapter;
        $selectString = $sql->getSqlStringForSqlObject($select);
        $SettingsResults = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
        $SettingsArr = array();
        $SettingsResultSet = new ResultSet;
        $SettingsResultSet->initialize($SettingsResults);
        $SettingsResultSet->buffer();
        $SettingsResultSetCount = $SettingsResultSet->count();
        if ($SettingsResultSetCount > 0) {
            $SettingsArr = $SettingsResultSet->toArray();
            $SettingsResultSetArr = $SettingsArr[0];
            $pwdIteartions = $SettingsResultSetArr['password_iterations'];
            $pwdIteartionLength = $SettingsResultSetArr['password_iteration_length'];
        } else {
            $pwdIteartions = 1000;
            $pwdIteartionLength = 32;
        }
        
        $key=$data['key'];
        $query = "select user_salt from user where user_publickey='$key'";
        $statements = $this->_adapter->query($query);
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
            $sql = new Sql($this->_adapter);
            //'user_password' => md5($data['password']) . $arr[0]['user_salt'],
            $newData = array(
            'user_password' => $this->createHash($data['password'], $arr[0]['user_salt'], $pwdIteartions, $pwdIteartionLength),
            'user_publickey' => $this->_genKey(20),
                'status' => 'Active',
            );
            $update = $sql->update('user')
                ->set($newData)
                ->where(array('user_publickey' => $key));
            $selectStringRole = $sql->getSqlStringForSqlObject($update);
            $this->_adapter->query($selectStringRole, Adapter::QUERY_MODE_EXECUTE);
            return 'changed';
        } else {
            return 'notchanged';
        }
    }

    public function checkifPasswordmatch($email, $data)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('settings')->where(array("status" => "Yes"));
        $newadpater = $this->_adapter;
        $selectString = $sql->getSqlStringForSqlObject($select);
        $SettingsResults = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
        $SettingsArr = array();
        $SettingsResultSet = new ResultSet;
        $SettingsResultSet->initialize($SettingsResults);
        $SettingsResultSet->buffer();
        $SettingsResultSetCount = $SettingsResultSet->count();
        if ($SettingsResultSetCount > 0) {
            $SettingsArr = $SettingsResultSet->toArray();
            $SettingsResultSetArr = $SettingsArr[0];
            $pwdIteartions = $SettingsResultSetArr['password_iterations'];
            $pwdIteartionLength = $SettingsResultSetArr['password_iteration_length'];
        } else {
            $pwdIteartions = 1000;
            $pwdIteartionLength = 32;
        }
        
        $queryClients = "SELECT DISTINCT user. * , user_role_tracker.group_id, `group`.group_name
            FROM user
            LEFT JOIN user_role_tracker ON user.u_id = user_role_tracker.u_id
            LEFT JOIN `group` ON `group`.group_id = user_role_tracker.group_id
            WHERE user.email = '$email' and LOWER(user.status)='active' and LOWER(user.user_type) = 'normal'";
        $statements = $this->_adapter->query($queryClients);
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $arr = $resultSet->toArray();
        //if ($arr[0]['user_password']==md5($data['oldpassword']).$arr[0]['user_salt']) {
        if ($arr[0]['user_password'] == $this->createHash($data['oldpassword'], $arr[0]['user_salt'], $pwdIteartions, $pwdIteartionLength)) {
            return 'exist';
        }
    }

    private function _genKey($length)
    {
        if ($length > 0) {
            $randId="";
            for ($i=1; $i <= $length; $i++) {
                mt_srand((double)microtime() * 1000000);
                $num = mt_rand(1, 72);
                $randId .= $this->_assignRandValue($num);
            }
        }
        return $randId;
    }

    private function _assignRandValue($num)
    {
        switch ($num) {
        case "1":
            $randValue = "a";
            break;
        case "2":
            $randValue = "b";
            break;
        case "3":
            $randValue = "c";
            break;
        case "4":
            $randValue = "d";
            break;
        case "5":
            $randValue = "e";
            break;
        case "6":
            $randValue = "f";
            break;
        case "7":
            $randValue = "g";
            break;
        case "8":
            $randValue = "h";
            break;
        case "9":
            $randValue = "i";
            break;
        case "10":
            $randValue = "j";
            break;
        case "11":
            $randValue = "k";
            break;
        case "12":
            $randValue = "l";
            break;
        case "13":
            $randValue = "m";
            break;
        case "14":
            $randValue = "n";
            break;
        case "15":
            $randValue = "o";
            break;
        case "16":
            $randValue = "p";
            break;
        case "17":
            $randValue = "q";
            break;
        case "18":
            $randValue = "r";
            break;
        case "19":
            $randValue = "s";
            break;
        case "20":
            $randValue = "t";
            break;
        case "21":
            $randValue = "u";
            break;
        case "22":
            $randValue = "v";
            break;
        case "23":
            $randValue = "w";
            break;
        case "24":
            $randValue = "x";
            break;
        case "25":
            $randValue = "y";
            break;
        case "26":
            $randValue = "z";
            break;
        case "27":
            $randValue = "0";
            break;
        case "28":
            $randValue = "1";
            break;
        case "29":
            $randValue = "2";
            break;
        case "30":
            $randValue = "3";
            break;
        case "31":
            $randValue = "4";
            break;
        case "32":
            $randValue = "5";
            break;
        case "33":
            $randValue = "6";
            break;
        case "34":
            $randValue = "7";
            break;
        case "35":
            $randValue = "8";
            break;
        case "36":
            $randValue = "9";
            break;
        case "37":
            $randValue = "j";
            break;
        case "38":
            $randValue = "a";
            break;
        case "39":
            $randValue = "b";
            break;
        case "40":
            $randValue = "c";
            break;
        case "41":
            $randValue = "d";
            break;
        case "42":
            $randValue = "e";
            break;
        case "43":
            $randValue = "f";
            break;
        case "44":
            $randValue = "g";
            break;
        case "45":
            $randValue = "h";
            break;
        case "46":
            $randValue = "i";
            break;
        case "47":
            $randValue = "A";
            break;
        case "48":
            $randValue = "B";
            break;
        case "49":
            $randValue = "C";
            break;
        case "50":
            $randValue = "D";
            break;
        case "51":
            $randValue = "E";
            break;
        case "52":
            $randValue = "F";
            break;
        case "53":
            $randValue = "G";
            break;
        case "54":
            $randValue = "H";
            break;
        case "55":
            $randValue = "I";
            break;
        case "56":
            $randValue = "J";
            break;
        case "57":
            $randValue = "K";
            break;
        case "58":
            $randValue = "L";
            break;
        case "59":
            $randValue = "M";
            break;
        case "60":
            $randValue = "N";
            break;
        case "61":
            $randValue = "O";
            break;
        case "62":
            $randValue = "P";
            break;
        case "63":
            $randValue = "Q";
            break;
        case "64":
            $randValue = "R";
            break;
        case "65":
            $randValue = "S";
            break;
        case "66":
            $randValue = "T";
            break;
        case "67":
            $randValue = "U";
            break;
        case "68":
            $randValue = "V";
            break;
        case "69":
            $randValue = "W";
            break;
        case "70":
            $randValue = "X";
            break;
        case "71":
            $randValue = "Y";
            break;
        case "72":
            $randValue = "Z";
            break;
        }
        return $randValue;
    }

    /**
     * to forgotpassword:check whether the mail exist in database or not
     */
    public function checkifUserexist($email)
    {
        $query = "select count('u_id') as tot from user where email='$email'";
        $statements = $this->_adapter->query($query);
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $arr=$resultSet->toArray();
        if ($arr[0]['tot'] > 0) {
            return 'exist';
        }
    }
    
    public function checkifUserisBioclinicaUser($email)
    {
        $query = "select user_type from user where email='$email'";
        $statements = $this->_adapter->query($query);
        $results = $statements->execute();        
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();        
        $arr=$resultSet->toArray();
        if ($arr[0]['user_type'] == 'Normal') {
            return 'Normal';
        }
    }

    /**
     * to check key exists on user table
     */
    public function checkifKeyexist($key)
    {
        $query = "select count('u_id') as tot from user where user_publickey='$key'";
        $statements = $this->_adapter->query($query);
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $arr=$resultSet->toArray();
        if ($arr[0]['tot'] > 0) {
            return 'exist';
        } else {
            return 'notexist';
        }
    }

    public function createHash($password, $salt, $iterations, $length)
    {
        return bin2hex(hash_pbkdf2("sha256", $password, $salt, $iterations, $length, true));
    }
}
