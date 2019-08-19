<?php

namespace Ubiquity\db\providers\mysqli;

/**
 * Ubiquity\db\providers\mysqli$MysqliWrapper
 * This class is part of Ubiquity
 *
 * @author jcheron <myaddressmail@gmail.com>
 * @version 1.0.0
 * @property \mysqli $dbInstance
 *
 */
class MysqliStatement {
	protected $statement;
	protected $preparedParams;
	
	protected function replaceNamedParams($values){
		$params=\count($this->preparedParams[1]??[])>0?$this->preparedParams[1]:null;
		if($params!=null){
			$result=[];
			foreach ($params as $param){
				$result[]=$values[$param];
			}
			return $result;
			}
		return $values;
	}
	
	public function execute(array $values = null){
		if($this->statement){
			if($values!==null){
				$values=$this->replaceNamedParams($values);
				$this->statement->bind_param(\str_repeat('s', \sizeof($values)), ...$values);
			}
			return $this->statement->execute();
		}
		return false;
	}
	
	public function __construct($statement,$params=null) {
		$this->statement = $statement;
		$this->preparedParams=$params;
	}
	
	public function fetchColumn($columnNumber=0){
		$res = $this->statement->get_result();
		return $res->fetch_row()[$columnNumber??0];
	}
	
	public function get_result(){
		if($this->statement)
			return $this->statement->get_result();
		return [];
	}
	
	public function close(){
		/*if($this->statement)
			return $this->statement->close();*/
		return true;
	}
}