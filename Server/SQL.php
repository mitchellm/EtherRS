namespace Server;

require_once(__DIR__ . '/config.Server.php');

class SQL extends Server {
	protected $conn;

	public function __construct() {
		try {
			$this->conn = new \PDO('mysql:host=localhost;dbname=EtherRS', 'root', '');
			$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch(\PDOException $e) {
			$this->log($e->getMessage(), true, 2);
		}
		$this->log('SQL initialized');
	}
}
