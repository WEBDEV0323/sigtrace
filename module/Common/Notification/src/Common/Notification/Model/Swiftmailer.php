<?php

namespace Common\Notification\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;

//require '../library/Swiftmailer/lib/swift_required.php';

class Swiftmailer
{
    protected $_adapter;

    /**
     * Make the Adapter object avilable as local prtected variable
     *
     * @param Adapter $adapter - DB PDO PgSQL conn
     */
    public function __construct(Adapter $_adapter)
    {
        $this->adapter = $_adapter;
    }
    public function sendMail($host, $user, $password, $port, $subject, $htmlPart, $sendTo, $CC)
    {
        $htmlPart = html_entity_decode($htmlPart);
        $transport = \Swift_SmtpTransport::newInstance()
            ->setHost($host)
            ->setEncryption('tls')
            ->setPort($port)
            ->setUsername($user)
            ->setPassword($password);
        //Create mailer
        $mailer = \Swift_Mailer::newInstance($transport);

        //Create the message
        $CC = array_filter($CC);
        try {
            if (empty($CC)) {
                $message = \Swift_Message::newInstance()
                    ->setSubject($subject)
                    ->setFrom(array($user => 'ASRtrace'))
                    ->setTo($sendTo)
                    ->setBody($htmlPart, 'text/html');
            } else {
                $message = \Swift_Message::newInstance()
                    ->setSubject($subject)
                    ->setFrom(array($user => 'ASRtrace'))
                    ->setTo($sendTo)
                    ->setBody($htmlPart, 'text/html')
                    ->setCc($CC);
            }
            $message->setContentType("text/html");
        } catch (\Exception $e) {
            echo "Caught exception: " . get_class($e) . "\n";
            echo "Message: " . $e->getMessage() ." ".date('Y-m-d H:i:s').  "\n";
            return $e->getMessage()."<<Time>> ".date('Y-m-d H:i:s');
        }
        $result=$mailer->send($message, $failures);
        return $result;
    }

    public function saveMail($formId, $recordId, $from, $to, $cc, $subject, $body)
    {
        $sql = new Sql($this->adapter);
        $insert = $sql->insert('notification_log');

        $data = array(
            'form_id' => $formId,
            'record_id' => $recordId,
            'from_log' => $from,
            'to_log' => (!empty($to)) ? $to : $cc,
            'cc' => (empty($cc))?null:$cc,
            'subject' => $subject,
            'body' => $body
        );
        $insert->values($data);
        $selectString = $sql->prepareStatementForSqlObject($insert);
        $results = $selectString->execute();
        return $results;
    }
    public function getSendMailData()
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from('notification_log');
        $select->where(array('status' => 'queue'));
        $selectString = $sql->prepareStatementForSqlObject($select);
        $results = $selectString->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr;
    }
    public function getSendMailDataBackup1()
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from('notification_log');
        $select->where(array('status' => 'queue1'));
        $newadpater = $this->adapter;
        $selectString = $sql->getSqlStringForSqlObject($select);
        $results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr;
    }
    public function getSendMailDataBackup2()
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from('notification_log');
        $select->where(array('status' => 'queue2'));
        $newadpater = $this->adapter;
        $selectString = $sql->getSqlStringForSqlObject($select);
        $results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr;
    }
    public function getSendMailDataBackup3()
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from('notification_log');
        $select->where(array('status' => 'queue3'));
        $newadpater = $this->adapter;
        $selectString = $sql->getSqlStringForSqlObject($select);
        $results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr;
    }
    public function getSendMailDataBackup4()
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from('notification_log');
        $select->where(array('status' => 'queue4'));
        $newadpater = $this->adapter;
        $selectString = $sql->getSqlStringForSqlObject($select);
        $results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr;
    }

    public function updateNotification($nid, $reponse, $status)
    {
        $sql = new Sql($this->adapter);
       
        $data = array(
            'status' => $status,
            'response' => $reponse,
            'sent_time' => date('Y-m-d H:i:s'),

        );
        $update = $sql->update();
        $update->table('notification_log');
        $update->set($data);
        $update->where(array('id' => $nid));
        $statement = $sql->prepareStatementForSqlObject($update);
        $results = $statement->execute($update);
    }
    public function updateNotification1($nid, $reponse, $status)
    {
        $sql = new Sql($this->adapter);
        /******
* START updating last run
*/
        $data = array(
            'status' => $status,
            'response' => $reponse,
            'sent_time' => date('Y-m-d H:i:s'),

        );
        $update = $sql->update();
        $update->table('notification_log');
        $update->set($data);
        $update->where(array('id' => $nid));
        $statement = $sql->prepareStatementForSqlObject($update);
        $results = $statement->execute($update);
    }
    public function updateNotification2($nid, $reponse, $status)
    {
        $sql = new Sql($this->adapter);
        /******
* START updating last run
*/
        $data = array(
            'status' => $status,
            'response' => $reponse,
            'sent_time' => date('Y-m-d H:i:s'),

        );
        $update = $sql->update();
        $update->table('notification_log');
        $update->set($data);
        $update->where(array('id' => $nid));
        $statement = $sql->prepareStatementForSqlObject($update);
        $results = $statement->execute($update);
    }
    public function updateNotification3($nid, $reponse, $status)
    {
        $sql = new Sql($this->adapter);
        /******
* START updating last run
*/
        $data = array(
            'status' => $status,
            'response' => $reponse,
            'sent_time' => date('Y-m-d H:i:s'),

        );
        $update = $sql->update();
        $update->table('notification_log');
        $update->set($data);
        $update->where(array('id' => $nid));
        $statement = $sql->prepareStatementForSqlObject($update);
        $results = $statement->execute($update);
    }
    public function updateNotification4($nid, $reponse, $status)
    {
        $sql = new Sql($this->adapter);
        /******
* START updating last run
*/
        $data = array(
            'status' => $status,
            'response' => $reponse,
            'sent_time' => date('Y-m-d H:i:s'),

        );
        $update = $sql->update();
        $update->table('notification_log');
        $update->set($data);
        $update->where(array('id' => $nid));
        $statement = $sql->prepareStatementForSqlObject($update);
        $results = $statement->execute($update);
    }

    public function updateNotificationQueue($nid)
    {
        $sql = new Sql($this->adapter);
        $data = array(
           'status' => 'Sending',
        );
        $update = $sql->update();
        $update->table('notification_log');
        $update->set($data);
        $update->where(array('id' => $nid));
        $statement = $sql->prepareStatementForSqlObject($update);
        $results = $statement->execute($update);
    }
    public function getFailedMailData()
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from('notification_log');
        $select->where(array('status' => 'failed'));
        $selectString = $sql->prepareStatementForSqlObject($select);
        $results = $selectString->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr;
    }
}
