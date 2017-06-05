<?php 
class logsql {

	# @string, Log directory name
	private $path = '/logs/';

	# @void, Default Constructor, Sets the timezone and path of the log files.
	public function __construct() {
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$this->path = dirname(__FILE__) . $this->path;
	}

	/**
	*   @void 
	*	Creates the log
	*
	*   @param string $message the message which is written into the log.
	*	@description:
	*	 1. Checks if directory exists, if not, create one and call this method again.
	*	 2. Checks if log already exists.
	*	 3. If not, new log gets created. Log is written into the logs folder.
	*	 4. Logname is current date(Year - Month - Day).
	*	 5. If log exists, edit method called.
	*	 6. Edit method modifies the current log.
	*/	
	public function write($message) {
		$date = new DateTime();
		$log = $this->path . $date->format('Y-m-d') . ".txt";

		if(is_dir($this->path)) {
			if(!file_exists($log)) {
				$f = fopen($log, "a+") or die("Fatal Error");
				$logContent = "Time: " . $date->format('H:i:s') . "\r\n" . $message . "\r\n";
				fwrite($f, $logContent);
				fclose(($f));
			}
			else {
				$this->edit($log, $date, $message);
			}
		}
		else {
			if (mkdir($this->path, 0777) == true) {
				$this->write($message);
			}
		}
	}

	/** 
	*  @void
	*  Gets called if log exists. 
	*  Modifies current log and adds the message to the log.
	*
	* @param string $log
	* @param DateTimeObject $date
	* @param string $message
	*/
	public function edit($log, $date, $message) {
		$logContent = "Time: " . $date->format('H:i:s') . "\r\n" . $message . "\r\n-----\r\n";
		$logContent = $logContent . file_get_contents($log);
		file_put_contents($log, $logContent);
	}
}

?>