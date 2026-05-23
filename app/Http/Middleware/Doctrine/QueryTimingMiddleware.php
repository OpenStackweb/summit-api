<?php

namespace App\Http\Middleware\Doctrine;

use Doctrine\DBAL\Driver as DBALDriver;
use Doctrine\DBAL\Driver\Connection as DBALConnection;
use Doctrine\DBAL\Driver\Middleware as DBALMiddleware;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\Result as DBALResult;
use Doctrine\DBAL\Driver\Statement as DBALStatement;

/**
 * DBAL Driver Middleware that records SQL execution duration into
 * QueryTimingCollector. Registered globally via config/doctrine.php so it
 * times every query in every request; the request-scoped accumulator is
 * reset at the top of each request by ServerTimingDoctrine.
 *
 * Per-query overhead is two microtime(true) calls — negligible.
 */
class QueryTimingMiddleware implements DBALMiddleware
{
    public function wrap(DBALDriver $driver): DBALDriver
    {
        return new QueryTimingDriver($driver);
    }
}

class QueryTimingDriver extends AbstractDriverMiddleware
{
    public function connect(array $params): DBALConnection
    {
        return new QueryTimingConnection(parent::connect($params));
    }
}

class QueryTimingConnection extends AbstractConnectionMiddleware
{
    public function query(string $sql): DBALResult
    {
        $start = microtime(true);
        try {
            return parent::query($sql);
        } finally {
            QueryTimingCollector::record($start, $sql);
        }
    }

    public function exec(string $sql): int
    {
        $start = microtime(true);
        try {
            return parent::exec($sql);
        } finally {
            QueryTimingCollector::record($start, $sql);
        }
    }

    public function prepare(string $sql): DBALStatement
    {
        return new QueryTimingStatement(parent::prepare($sql), $sql);
    }
}

class QueryTimingStatement extends AbstractStatementMiddleware
{
    private string $sql;

    public function __construct($wrapped, string $sql)
    {
        parent::__construct($wrapped);
        $this->sql = $sql;
    }

    public function execute($params = null): DBALResult
    {
        $start = microtime(true);
        try {
            return parent::execute($params);
        } finally {
            QueryTimingCollector::record($start, $this->sql);
        }
    }
}
