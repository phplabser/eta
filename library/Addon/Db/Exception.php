<?php
/**
 * Eta Framework 2
 * Fast and powerful PHP framework
 *
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @copyright Copyright (c) 2014-2015 Phplabs (http://www.phplabs.pl)
 */

namespace Eta\Addon\Db;

class Exception extends \PDOException
{
    /**
     * SQL query code
     * @var string
     */
    protected $_sql;

    /**
     * Query bind params
     * @var array|ArrayObject
     */
    protected $_params;

    /**
     * Constructor
     *
     * @param string $message
     * @param int $code
     * @param string $sqlName
     * @param string $sql
     * @param array|ArrayObject $params
     */
    public function __construct($message = '', $code = 0, $sql = '', $params = null)
	{
		$this->_sql     = $sql;
		$this->_params  = $params;

		if (strpos($message, "Raise exception:")) {
	        $message = mb_substr($message, strpos($message, 'ERROR:') + 6);
	    }

		parent::__construct($message,(int)$code);
	}

	/**
	 * Returns SQL query
	 * @return string
	 */
	public function getSQL() : string
	{
	    return $this->_sql;
	}


	/**
	 * Returns SQL query params
	 * @return array|ArrayObject
	 */
	public function getParams()
	{
	    return $this->_params;
	}


	/**
	 * Returns string exception representation
	 * @return string
	 */
	public function __toString() : string
	{
		$counter = 0;

		$eDetails = __CLASS__ . "\n";
		$eDetails .= "Message: " . $this->getMessage() . "\n";
		$eDetails .= "Code: " . $this->getCode() . "\n\n";
		$eDetails .= "SQL:\n" . $this->_sql . "\n\n";

		if ($this->_params != null) {
		    $eDetails .= "Bind params:\n" . var_export($this->_params, true) . "\n\n";
		}

		$eDetails .= "Stack trace:\n";

		foreach ($this->getTrace() as $trace) {
			$eDetails .= "#" . $counter++ . " " . $trace['file'] . "(" . $trace['line'] . ") ";

			if (isset($trace['class'])) {
				$eDetails .= $trace['class'] . $trace['type'];
			}

			$eDetails .= $trace['function'] . "(";
			$eDetails .= ");\n";
		}
		return $eDetails;
	}


}