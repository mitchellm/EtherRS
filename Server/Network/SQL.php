<?php
namespace Server\Network;

require_once(ROOT_DIR . '/data/config.Server.php');

class SQL extends \Server\Server {
	protected $conn;

	public function __construct() {
		try {
			$this->conn = new \PDO('mysql:host='.SQL_HOST.';dbname='.SQL_DB, SQL_USER, SQL_PASS);
			$this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		} catch(\PDOException $e) {
			$this->log($e->getMessage(), true, 2);
		}
		$this->log('SQL connection initialized');
	}
	
	/**
	 *
	 * Get the number of rows from a certain table based on column and value.
	 *
	 * @param  string $table   The table to select from -- Be careful, we can't properly parameterize these.
	 * @param  array  $columns The column sto select with -- We can't parameterize these either!
	 * @param  array  $values  The values we need.
	 *
	 * @return int
	 *
	 */
	public function getCount($table, array $columns, array $values) {
		if($this->conn == false) {
			throw new \Exception(__METHOD__ . ': No SQL connection');
		}
		if(count($columns) != count($values)) {
			throw new \Exception(__METHOD__ . ': Unmatching column and value arrays');
		}
		$sql = '';
		foreach($columns as $column) {
			$sql .= '`' . $column . '` = ? AND ';
		}
		$sql = substr($sql, 0, -5);
		$sql = 'SELECT COUNT(*) as `rows` FROM ' . $table . ' WHERE ' . $sql;
		$query = $this->conn->prepare($sql);
		$query->execute($values);
		$rs = $query->fetch(\PDO::FETCH_ASSOC);
		
		return $rs['rows'];
	}
	
	/**
	 *
	 * Get the PDO instance
	 *
	 * @return PDO object
	 *
	 */
	 public function getConn() {
	 	return $this->conn;
	 }
}
?>
