<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Connection;
use RuntimeException;
use Throwable;

class MySqlAdvisoryLock
{
    public function synchronized(Connection $connection, string $name, int $timeout, Closure $callback): mixed
    {
        $lock = $connection->selectOne('SELECT GET_LOCK(?, ?) AS lock_result', [$name, $timeout]);
        $result = $lock?->lock_result;

        if ($result === 0 || $result === '0') {
            throw new RuntimeException("Timed out after {$timeout} seconds waiting for the MySQL advisory lock.");
        }

        if ($result !== 1 && $result !== '1') {
            throw new RuntimeException('MySQL did not grant the requested advisory lock.');
        }

        $callbackException = null;

        try {
            $result = $callback();
        } catch (Throwable $exception) {
            $callbackException = $exception;
        }

        try {
            $release = $connection->selectOne('SELECT RELEASE_LOCK(?) AS lock_result', [$name]);
            $released = $release?->lock_result;

            if ($released !== 1 && $released !== '1') {
                throw new RuntimeException('The MySQL advisory lock could not be released.');
            }
        } catch (Throwable $releaseException) {
            if ($callbackException !== null) {
                report($releaseException);
                throw $callbackException;
            }

            throw new RuntimeException('The MySQL advisory lock could not be released.', 0, $releaseException);
        }

        if ($callbackException !== null) {
            throw $callbackException;
        }

        return $result;
    }
}
