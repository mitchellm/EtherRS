<?php
namespace Server;

require_once(__DIR__ . '/config.Server.php');

class SQL extends Server {
	protected $conn;

	public function __construct() {
		try {
			$this->conn = new \PDO('mysql:host=localhost;dbname=etherrs', 'root', '');
			$this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		} catch(\PDOException $e) {
			$this->log($e->getMessage(), true, 2);
		}
		$this->log('SQL initialized');
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
			$this->log(__METHOD__ . ': No SQL connection');
			return false;
		}
		if(count($columns) != count($values)) {
			throw new \Exception(__METHOD__ . ': Unmatching column and value arrays');
		}

		$sql = "SELECT COUNT(*) FROM ";
		$sql .= "{$table} WHERE ";

		$count = count($columns);

		for($x = 0; $x < $count; $x++) {
			$sql .= " `{$columns[$x]}` = ? ";
			$x != $count -1 ? $sql .= "AND" : false;
		}
		$stmt = $this->conn->prepare($sql);
		$stmt->execute($values);
		$rs = $stmt->fetch(\PDO::FETCH_ASSOC);
		return $rs["COUNT(*)"];
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
