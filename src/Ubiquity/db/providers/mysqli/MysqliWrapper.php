<?php

namespace Ubiquity\db\providers\mysqli;

use Ubiquity\db\providers\AbstractDbWrapper;

/**
 * Ubiquity\db\providers\mysqli$MysqliWrapper
 * This class is part of Ubiquity
 *
 * @author jcheron <myaddressmail@gmail.com>
 * @version 1.0.0
 * @property \mysqli $dbInstance
 *
 */
class MysqliWrapper extends AbstractDbWrapper {
	protected $transactionLevel = 0;
	
	protected function _execute($statement, array $values = null){
		if($values!==null){
			$statement->bind_param(str_repeat('s', sizeof($values)), ...$values);
		}
		return $statement->execute();
	}
	
	public function __construct($dbType = 'mysql') {
		$this->quote = '`';
	}
	
	public function fetchAllColumn($statement, array $values = null, string $column = null) {
		$result = false;
		if ($this->_execute($statement,$values)) {
			$res = $statement->get_result();
			$result = [];
			while ($row = $res->fetch_row()){
				$result[] = $row[0];
			}
		}
		return $result;
	}
	
	public function lastInsertId() {
		return $this->dbInstance->insert_id;
	}
	
	public function fetchAll($statement, array $values = null, $mode = null) {
		$result = false;
		if ($statement->execute($values )) {
			$res = $statement->get_result();
			$result = $res->fetch_all($mode??\MYSQLI_ASSOC);
		}
		return $result;
	}
	
	public function fetchOne($statement, array $values = null, $mode = null) {
		$result = false;
		if ($this->_execute ($statement, $values )) {
			$res = $statement->get_result();
			$result = $res->fetch_array( $mode );
		}
		//$statement->close();
		return $result;
	}
	
	public static function getAvailableDrivers() {
		return 'mysql';
	}
	
	public function prepareStatement(string $sql) {
		$st=$this->dbInstance->prepare ( $sql );
		return new MysqliStatement($st);
	}
	
	public function fetchColumn($statement, array $values = null, int $columnNumber = null) {
		if ($this->_execute ( $statement,$values )) {
			$res = $statement->get_result();
			return $res->fetch_row()[$columnNumber??0];
		}
		return false;
	}
	
	public function getStatement($sql) {
		\preg_match_all('/:([[:alpha:]]+)/', $sql,$params);
		$sql=\preg_replace('/:[[:alpha:]]+/','?',$sql);
		$st=$this->dbInstance->prepare ( $sql);
		return new MysqliStatement($st,$params);
	}
	
	public function execute($sql) {
		return $this->dbInstance->real_query( $sql );
	}
	
	public function connect(string $dbType, $dbName, $serverName, string $port, string $user, string $password, array $options) {
		$this->dbInstance = new \mysqli( $serverName,$user,$password,$dbName, $port);
		$this->dbInstance->set_charset("utf8");
		foreach ($options as $key=>$value){
			$this->dbInstance->set_opt($key, $value);
		}
	}
	
	public function getDSN(string $serverName, string $port, string $dbName, string $dbType = 'mysql') {
		return 'mysqli:dbname=' . $dbName . ';host=' . $serverName . ';charset=UTF8;port=' . $port;
	}
	
	public function bindValueFromStatement($statement, $parameter, $value) {
		return $statement->bind_param( 's' ,$value);
	}
	
	public function query(string $sql) {
		return $this->dbInstance->query( $sql );
	}
	
	public function queryAll(string $sql, int $fetchStyle = null) {
		return $this->dbInstance->query ( $sql )->fetch_all( $fetchStyle );
	}
	
	public function queryColumn(string $sql, int $columnNumber = null) {
		return $this->dbInstance->query ( $sql )->fetch_row()[$columnNumber??0];
	}
	
	public function executeStatement($statement, array $values = null) {
		return $this->_execute ( $statement,$values );
	}
	
	public function getTablesName() {
		$query = $this->dbInstance->query ( 'SHOW TABLES' );
		$result=[];
		while ($row = $query->fetch_row()){
			$result[] = $row[0];
		}
		return $result;
	}
	
	public function statementRowCount($statement) {
		return $statement->num_rows;
	}
	
	public function inTransaction() {
		return null;
	}
	
	public function commit() {
		return $this->dbInstance->commit();
	}
	
	public function rollBack() {
		return $this->dbInstance->rollBack ();
	}
	
	public function beginTransaction() {
		return $this->dbInstance->begin_transaction();
	}
	
	public function savePoint($level) {
		$this->dbInstance->savepoint($level);
	}
	
	public function releasePoint($level) {
		$this->dbInstance->release_savepoint($level);
	}
	
	public function rollbackPoint($level) {
		$this->dbInstance->rollback($level);
	}
	
	public function nestable() {
		return true;
	}
	
	public function ping() {
		return $this->dbInstance->ping();
	}
	
	public function getPrimaryKeys($tableName) {
		$fieldkeys = array ();
		$recordset = $this->dbInstance->query ( "SHOW KEYS FROM `{$tableName}` WHERE Key_name = 'PRIMARY'" );
		$keys = $recordset->fetch_all( \MYSQLI_ASSOC );
		foreach ( $keys as $key ) {
			$fieldkeys [] = $key ['Column_name'];
		}
		return $fieldkeys;
	}
	
	public function getForeignKeys($tableName, $pkName, $dbName = null) {
		$recordset = $this->dbInstance->query ( "SELECT *
												FROM
												 information_schema.KEY_COLUMN_USAGE
												WHERE
												 REFERENCED_TABLE_NAME = '" . $tableName . "'
												 AND REFERENCED_COLUMN_NAME = '" . $pkName . "'
												 AND TABLE_SCHEMA = '" . $dbName . "';" );
		return $recordset->fetch_all( \MYSQLI_ASSOC );
	}
	
	public function getFieldsInfos($tableName) {
		$fieldsInfos = array ();
		$recordset = $this->dbInstance->query ( "SHOW COLUMNS FROM `{$tableName}`" );
		$fields = $recordset->fetch_all( \MYSQLI_ASSOC );
		foreach ( $fields as $field ) {
			$fieldsInfos [$field ['Field']] = [ "Type" => $field ['Type'],"Nullable" => $field ["Null"] ];
		}
		return $fieldsInfos;
	}
}