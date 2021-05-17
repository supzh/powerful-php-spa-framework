<?php

use Application\Database;

class SysSessionHandler implements SessionHandlerInterface
{
    private $db;
    /*
     *
        CREATE TABLE `session` (
          `session_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
          `session_expires` datetime NOT NULL,
          `session_data` text COLLATE utf8_unicode_ci,
          PRIMARY KEY (`session_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
     *
     * */

    public function __construct(\PDO $db = null)
    {
    	if(null === $db) {
    		$db = DataBase::getDb();
    	}
        $this->db = $db;
    }

    public function open($savePath, $sessionName)
    {
        if($this->db){
            return true;
        }else{
            return false;
        }
    }

    public function close()
    {
        return true;
    }

    public function read($id)
    {
        $stm = $this->db->prepare("SELECT session_data FROM session WHERE session_id = '".$id."' AND session_expires > '".date('Y-m-d H:i:s')."'");
        $stm->execute();
        if($row = $stm->fetch()){
            return $row['session_data'];
        }else{
            return "";
        }
    }
    public function write($id, $data)
    {
        $DateTime = date('Y-m-d H:i:s');
        $NewDateTime = date('Y-m-d H:i:s',strtotime($DateTime.' + 1 hour'));
        
		$sql = "select * from session where session_id='$id'";
		$st = $this->db->prepare($sql);
		$st->execute();
		if($st->fetch()) {
			
			$stm = $this->db->prepare("update session set session_expires = '".$NewDateTime."', session_data = '".$data."' where session_id = '".$id."'");
			
			if($stm->execute()){
				return true;
			}else{
				return false;
			}
			
		} else {
			$stm = $this->db->prepare("REPLACE INTO session SET session_id = '".$id."', session_expires = '".$NewDateTime."', session_data = '".$data."'");
			
			if($stm->execute()){
				return true;
			}else{
				return false;
			}
		}
		
		
    }
    public function destroy($id)
    {
        $stm = $this->db->prepare("DELETE FROM session WHERE session_id ='".$id."'");
        if($stm->execute()){
            return true;
        }else{
            return false;
        }
    }
    public function gc($maxlifetime)
    {
        $stm = $this->db->prepare("DELETE FROM session WHERE ((UNIX_TIMESTAMP(Session_Expires) + ".$maxlifetime.") < ".$maxlifetime.")");
        if($stm->execute()){
            return true;
        }else{
            return false;
        }
    }
}