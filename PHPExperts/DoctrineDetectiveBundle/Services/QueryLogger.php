<?php

namespace PHPExperts\DoctrineDetectiveBundle\Services;

class QueryLogger implements \Doctrine\DBAL\Logging\SQLLogger
{
    protected  static $requests;

    protected $requestId;
    protected $serviceName;
    protected $methodName;
    protected $queryId;

    protected $requestStartTime = 0;
    protected $queryStartTime = 0;

    public function __construct($requestName = null)
    {
        $this->requestId = $requestName;
        if (!$requestName) {
            $this->requestId = count(self::$requests);
        }

        $this->requestStartTime = microtime(true);
    }

    /**
     * Logs a SQL statement somewhere.
     *
     * @param string     $sql    The SQL to be executed.
     * @param array|null $params The SQL parameters.
     * @param array|null $types  The SQL parameter types.
     *
     * @return void
     */
    public function startQuery($sql, array $params = null, array $types = null)
    {
        $backtrace = debug_backtrace();
        foreach ($backtrace as $trace) {
            if (isset($trace['class']) && substr($trace['class'], -7) === 'Service') {
                $this->serviceName = substr($trace['class'], strrpos($trace['class'], '\\'));
                $this->methodName = $trace['function'];
            }
        }
//        exit;
        $this->queryStartTime = microtime(true);
        $this->queryId = count(self::$requests[$this->requestId][$this->serviceName][$this->methodName]['queries']);
        $sql = $this->interpolateQuery($sql, $params);

        if (!isset(self::$requests[$this->requestId][$this->serviceName]['time'])) {
            self::$requests[$this->requestId][$this->serviceName]['time'] = 0;
        }

        if (!isset(self::$requests[$this->requestId][$this->serviceName][$this->methodName]['time'])) {
            self::$requests[$this->requestId][$this->serviceName][$this->methodName]['time'] = 0;
        }

        self::$requests[$this->requestId][$this->serviceName][$this->methodName]['queries'][$this->queryId] = [
            'query' => $sql,
        ];
    }

    /**
     * Marks the last started query as stopped. This can be used for timing of queries.
     *
     * @return void
     */
    public function stopQuery()
    {
        $currentTime = microtime(true);
        $totalRequestTime = ($currentTime - $this->requestStartTime) * 1000;
        $totalQueryTime = ($currentTime - $this->queryStartTime) * 1000;
        self::$requests[$this->requestId]['time'] = $totalRequestTime;
        self::$requests[$this->requestId][$this->serviceName]['time'] += $totalQueryTime;
        self::$requests[$this->requestId][$this->serviceName][$this->methodName]['time'] += $totalQueryTime;
        self::$requests[$this->requestId][$this->serviceName][$this->methodName]['queries'][$this->queryId]['time'] = $totalQueryTime;
    }

    public function getLog()
    {
        return self::$requests;
    }

    /**
     * Replaces any parameter placeholders in a query with the value of that
     * parameter. Useful for debugging. Assumes anonymous parameters from
     * $params are are in the same order as specified in $query
     *
     * @param string $query The sql query with parameter placeholders
     * @param array $params The array of substitution parameters
     * @return string The interpolated query
     */
    protected function interpolateQuery($query, $params) {
        $keys = array();
        $values = $params;

        if (!$params) {
            return $query;
        }

        # build a regular expression for each parameter
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $keys[] = '/:'.$key.'/';
            } else {
                $keys[] = '/[?]/';
            }

            if (is_string($value))
                $values[$key] = "'" . $value . "'";

            if (is_array($value))
                $values[$key] = "'" . implode("','", $value) . "'";

            if (is_null($value))
                $values[$key] = 'NULL';
        }

        $query = preg_replace($keys, $values, $query, 1, $count);

        return $query;
    }
}
