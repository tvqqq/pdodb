<?php 
require_once 'config.php';
require_once 'logsql.php';

/**
 * Based on PDO with custom functions to easily work with database.
 *
 * Instruction==
 * 1. Create class Model extends pdodb.php
 * 2. Write a function with parameters (options)
 * 3. $sql = "...where xxx = ?";
 * 4. $this->setQuery($sql);
 * 5. return $this->execute() [for insert, update, delete] or $this->all() or $this->single(array(xxx));
 * Note: Example of Transaction on end of file
 */

class pdodb{
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
			// get the connection string with format 'mysql:host=...;dbname=...; & set utf8'
			$this->pdo = new PDO("mysql:host=" . DB_HOST . "; dbname=" . DB_NAME, DB_USER, DB_PWD);
			$this->pdo->query("SET NAMES 'utf8'");

			# We can now log any exceptions on Fatal error. 
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            # Disable emulation of prepared statements, use REAL prepared statements instead.
			$this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		}
		catch (PDOException $ex)
		{
			//Write into log
			echo $this->ExceptionLog($ex->getMessage());
			die();
		}
	}

	public function close() {
		$this->pdo = null;
		$this->sta = null;
	}

	public function setQuery($sql) {
		$sql = trim(str_replace("\r", " ", $sql));
		$this->sql = $sql;
	}

	/**
	 * Function for insert, update, delete
	 */
	public function execute($opt = array()) {
		try
		{
			$this->sta = $this->pdo->prepare($this->sql);
			if($opt) {
				for($i=0; $i < count($opt); $i++) {
					$this->sta->bindParam($i+1, $opt[$i]);
				}
			}
			$this->sta->execute();
			return $this->sta->rowCount(); // >0 -> success or number of rows affect
		}
		catch (PDOException $ex)
		{
			echo $this->ExceptionLog($ex->getMessage(), $this->sql, implode(",",array_values($opt)));
			die();
		}
	}

	/**
	 * Function for load with method fetch
	 * Because method execute return an int -> fetch dont work
	 */
	private function execLoad($opt = array()) {
		try
		{
			$this->sta = $this->pdo->prepare($this->sql);
			if($opt) {
				for($i=0; $i < count($opt); $i++) {
					$this->sta->bindParam($i+1, $opt[$i]);
				}
			}
			$this->sta->execute();
			return $this->sta;
		}
		catch (PDOException $ex)
		{
			echo $this->ExceptionLog($ex->getMessage(), $this->sql, implode(",",array_values($opt)));
			die();
		}
	}

	/**
	 * Function load all rows of table based on sql
	 */
	public function all($opt = array()) {
		if(!$opt) {
			if(!$result = $this->execLoad())
				return false;
		}
		else {
			if(!$result = $this->execLoad($opt))
				return false;
		}
		$data = $result->fetchAll(PDO::FETCH_OBJ); //object
		$result->closeCursor(); // Frees up the connection to the server
		return $data;
	}

	/**
	 * Function load one row
	 */
	public function single($opt = array()) {
		if(!$opt) {
			if(!$result = $this->execLoad())
				return false;
		}
		else {
			if(!$result = $this->execLoad($opt))
				return false;
		}
		$data = $result->fetch(PDO::FETCH_OBJ); //object
		$result->closeCursor(); // Frees up the connection to the server
		return $data;
	}

	/**
	 * Function load data of one column
	 */
	public function column($opt = array()) {
		if(!$opt) {
			if(!$result = $this->execLoad())
				return false;
		}
		else {
			if(!$result = $this->execLoad($opt))
				return false;
		}
		$data = $result->fetchAll(PDO::FETCH_NUM); //array
		$result->closeCursor(); // Frees up the connection to the server

		$col = null;
		foreach ($data as $cells) {
			$col[] = $cells[0];
		}
		return $col;
	}

	/**
	 * Function count the records of table
	 */
	public function count($opt = array()) {
		if(!$opt) {
			if(!$result = $this->execLoad())
				return false;
		}
		else {
			if(!$result = $this->execLoad($opt))
				return false;
		}
		$data = $result->fetch(PDO::FETCH_COLUMN); //count (int)
		$result->closeCursor(); // Frees up the connection to the server
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
	private function ExceptionLog($message, $sql = "", $para = "")
	{
		$e = $message;
		if (!empty($sql)) {
            # Add the Raw SQL to the Log
			$message .= "\r\nRaw SQL : " . $sql;
			$e .= "<br/>Raw SQL : " . $sql;
		}

		if (!empty($para)) {
            # Add the Parameter to the Log
			$message .= "\r\nPara : " . $para;
			$e .= "<br/>Para : " . $para;
		}

		$exception = '<b>&#x2622; Exception!</b> Check logs<br />';
		$exception .= $e;

        # Write into log
		$this->log->write($message);

		return $exception;
	}
}

//==Example of Transaction==
// $pdo = new pdodb();
// $pdo->beginTransaction();
// try
// {
// 	$sql = "insert into user values(?,?,?,?)";
// 	$pdo->setQuery($sql);
// 	$pdo->execute(array(null,1,1,2));
// 	$sql2 = "insert into user values(?,?,?,?)";
// 	$pdo->setQuery($sql);
// 	$pdo->execute(array(null,1,1,'aa')); //fail
// 	$pdo->commit();
// }
// catch (PDOException $ex)
// {
// 	$pdo->rollBack();
// }


// $pdo = new pdodb();
// $sql = "select * from user where id = ?";
// $pdo->setQuery($sql);
// echo '<pre>';
// print_r($pdo->single(array(1)));
?>
