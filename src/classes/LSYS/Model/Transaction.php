<?php
/**
 * lsys orm
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */

namespace LSYS\Model;
class Transaction {
    protected $db;
    protected $in_transaction;
    public function __construct(Database $db=null){
        $this->db=$db?$db:DI::get()->modelDB();
    }
    /**
     * 事务使用的数据库对象
     * @return \LSYS\Model\Database
     */
    public function db() {
        return $this->db;
    }
	/**
	 * begin transction
	 * @param Database $db
	 * @return \LSYS\ORM\Transaction
	 */
	public function beginTransaction(){
	    $this->in_transaction=true;
	    return $this->db->beginTransaction();
	}
	/**
	 *  orm commit
	 */
	public function commit() {
	    $this->in_transaction=false;
	    $this->db->commit();
		return $this;
	}
	/**
	 * orm rollback
	 */
	public function rollback() {
	    $this->in_transaction=false;
	    $this->db->rollback();
		return $this;
	}
	/**
	 * on transaction
	 * @return bool
	 */
	public function inTransaction(){
	    return $this->in_transaction;
	}
}