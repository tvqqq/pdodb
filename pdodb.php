<?php 
require_once 'config.php';
require_once 'logsql.php';

/**
 * Based on PDO with custom functions to easily work with database.
 */
class pdodb {
	protected $pdo = null;
	protected $sql = '';
	protected $sta = null;
	protected $log;

	public function __construct() {
		$this->log = new logsql();
		$this->connect();
	}

	public function connect() {
		try
		{
			# Get the connection string with format 'mysql:host=...;dbname=...;'
			$this->pdo = new PDO("mysql:host=" . DB_HOST . "; dbname=" . DB_NAME, DB_USER, DB_PWD);
			$this->pdo->query("SET NAMES 'utf8'");

			# We can now log any exceptions on Fatal error. 
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            # Disable emulation of prepared statements, use REAL prepared statements instead.
			$this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		}
		catch (PDOException $ex)
		{
			# Write into log
			echo $this->ExceptionLog($ex->getMessage());
			die();
		}
	}

	public function close() {
		$this->pdo = null;
		$this->sta = null;
	}

	private function setQuery($sql) {
		$sql = trim(str_replace("\r", " ", $sql));
		$this->sql = $sql;
	}

	/**
	 * Function exec SQL for insert, update, delete
	 *
	 * @param string $sql
	 * @return number of rows affect (>0)
	 */
	public function execute($sql) {
		$this->setQuery($sql);
		try
		{
			$this->sta = $this->pdo->prepare($this->sql);
			$this->sta->execute();
			return $this->sta->rowCount();
		}
		catch (PDOException $ex)
		{
			echo $this->ExceptionLog($ex->getMessage(), $this->sql);
			die();
		}
	}

	/**
	 * Function for load results with method fetch
	 * @why b/c func "execute" return an int that method fetch wont work
	 */
	private function execLoad() {
		try
		{
			$this->sta = $this->pdo->prepare($this->sql);
			$this->sta->execute();
			return $this->sta;
		}
		catch (PDOException $ex)
		{
			echo $this->ExceptionLog($ex->getMessage(), $this->sql);
			die();
		}
	}

	# closeCursor(); Frees up the connection to the server

	/**
	 * Function load all rows of table based on sql
	 *
	 * @return Array Object
	 */
	public function all($sql) {
		$this->setQuery($sql);
		if(!$result = $this->execLoad())
			return false;
		$data = $result->fetchAll(PDO::FETCH_OBJ);
		$result->closeCursor();
		return $data;
	}

	/**
	 * Function load one row
	 *
	 * @return Object
	 */
	public function single($sql) {
		$this->setQuery($sql);
		if(!$result = $this->execLoad())
			return false;
		$data = $result->fetch(PDO::FETCH_OBJ);
		$result->closeCursor();
		return $data;
	}

	/**
	 * Function load data of one column
	 *
	 * @return Array
	 */
	public function column($sql) {
		$this->setQuery($sql);
		if(!$result = $this->execLoad())
			return false;
		$data = $result->fetchAll(PDO::FETCH_NUM);
		$result->closeCursor();
		$col = null;
		foreach ($data as $cells) {
			$col[] = $cells[0];
		}
		return $col;
	}

	/**
	 * Function count the records of table
	 *
	 * @return result[0][0]
	 */
	public function count($sql) {
		$this->setQuery($sql);
		if(!$result = $this->execLoad())
			return false;
		$data = $result->fetch(PDO::FETCH_COLUMN);
		$result->closeCursor();
		return $data;
	}

	/**
     *  Returns the last inserted id.
     */
	public function lastInsertId()
	{
		return $this->pdo->lastInsertId();
	}

    /**
     * Starts the transaction
     * @return boolean, true on success or false on failure
     */
    public function beginTransaction()
    {
    	return $this->pdo->beginTransaction();
    }
    
    /**
     *  Execute Transaction
     *  @return boolean, true on success or false on failure
     */
    public function commit()
    {
    	return $this->pdo->commit();
    }
    
    /**
     *  Rollback of Transaction
     *  @return boolean, true on success or false on failure
     */
    public function rollBack()
    {
    	return $this->pdo->rollBack();
    }

	/**	
     * Writes the log and returns the exception
     *
     * @param  string $message
     * @param  string $sql
     * @return string
     */
	private function ExceptionLog($message, $sql = "")
	{
		$e = $message;
		if (!empty($sql)) {
            # Add the Raw SQL to the Log
			$message .= "\r\nRaw SQL : " . $sql;
			$e .= "<br/>Raw SQL : " . $sql;
		}

		$exception = '<b>&#x2622; Exception!</b><br />';
		$exception .= $e;

        # Write into log
		$this->log->write($message);

		return $exception;
	}
}

/*
# Example of Transaction
$pdo = new pdodb();
$pdo->beginTransaction();
try
{
	#sql...
	$pdo->commit();
}
catch (PDOException $ex)
{
	$pdo->rollBack();
}
*/
?>
