<?php

namespace Tests\Feature;

use App\Support\MySqlAdvisoryLock;
use Illuminate\Database\Connection;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class MySqlAdvisoryLockTest extends TestCase
{
    public function test_it_runs_callback_and_releases_a_successfully_acquired_lock(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('selectOne')
            ->once()
            ->ordered()
            ->with('SELECT GET_LOCK(?, ?) AS lock_result', ['taskflow:initial-admin', 10])
            ->andReturn((object) ['lock_result' => 1]);
        $connection->shouldReceive('selectOne')
            ->once()
            ->ordered()
            ->with('SELECT RELEASE_LOCK(?) AS lock_result', ['taskflow:initial-admin'])
            ->andReturn((object) ['lock_result' => 1]);

        $result = app(MySqlAdvisoryLock::class)->synchronized(
            $connection,
            'taskflow:initial-admin',
            10,
            fn (): string => 'provisioned',
        );

        $this->assertSame('provisioned', $result);
    }

    public function test_it_throws_clearly_when_lock_acquisition_times_out(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('selectOne')
            ->once()
            ->with('SELECT GET_LOCK(?, ?) AS lock_result', ['taskflow:initial-admin', 10])
            ->andReturn((object) ['lock_result' => 0]);

        try {
            app(MySqlAdvisoryLock::class)->synchronized($connection, 'taskflow:initial-admin', 10, fn () => null);
            $this->fail('Expected a lock timeout exception.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Timed out', $exception->getMessage());
        }
    }

    public function test_it_throws_when_lock_acquisition_returns_null_or_an_unexpected_value(): void
    {
        foreach ([null, (object) ['lock_result' => null], (object) ['lock_result' => 2]] as $lockResult) {
            $connection = Mockery::mock(Connection::class);
            $connection->shouldReceive('selectOne')
                ->once()
                ->with('SELECT GET_LOCK(?, ?) AS lock_result', ['taskflow:initial-admin', 10])
                ->andReturn($lockResult);

            try {
                app(MySqlAdvisoryLock::class)->synchronized($connection, 'taskflow:initial-admin', 10, fn () => null);
                $this->fail('Expected a lock acquisition exception.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('did not grant', $exception->getMessage());
            }
        }
    }

    public function test_it_releases_lock_when_provisioning_callback_throws(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('selectOne')
            ->once()
            ->ordered()
            ->with('SELECT GET_LOCK(?, ?) AS lock_result', ['taskflow:initial-admin', 10])
            ->andReturn((object) ['lock_result' => 1]);
        $connection->shouldReceive('selectOne')
            ->once()
            ->ordered()
            ->with('SELECT RELEASE_LOCK(?) AS lock_result', ['taskflow:initial-admin'])
            ->andReturn((object) ['lock_result' => 1]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('provisioning failed');

        app(MySqlAdvisoryLock::class)->synchronized(
            $connection,
            'taskflow:initial-admin',
            10,
            fn () => throw new RuntimeException('provisioning failed'),
        );
    }

    public function test_it_throws_when_release_lock_returns_zero_or_null(): void
    {
        foreach ([0, null] as $releaseResult) {
            $connection = Mockery::mock(Connection::class);
            $connection->shouldReceive('selectOne')
                ->once()
                ->ordered()
                ->with('SELECT GET_LOCK(?, ?) AS lock_result', ['taskflow:initial-admin', 10])
                ->andReturn((object) ['lock_result' => 1]);
            $connection->shouldReceive('selectOne')
                ->once()
                ->ordered()
                ->with('SELECT RELEASE_LOCK(?) AS lock_result', ['taskflow:initial-admin'])
                ->andReturn($releaseResult === null ? (object) ['lock_result' => null] : (object) ['lock_result' => $releaseResult]);

            try {
                app(MySqlAdvisoryLock::class)->synchronized(
                    $connection,
                    'taskflow:initial-admin',
                    10,
                    fn (): string => 'provisioned',
                );
                $this->fail('Expected a lock release exception.');
            } catch (RuntimeException $exception) {
                $this->assertSame('The MySQL advisory lock could not be released.', $exception->getMessage());
            }
        }
    }

    public function test_callback_exception_is_preserved_when_lock_release_fails(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('selectOne')
            ->once()
            ->ordered()
            ->with('SELECT GET_LOCK(?, ?) AS lock_result', ['taskflow:initial-admin', 10])
            ->andReturn((object) ['lock_result' => 1]);
        $connection->shouldReceive('selectOne')
            ->once()
            ->ordered()
            ->with('SELECT RELEASE_LOCK(?) AS lock_result', ['taskflow:initial-admin'])
            ->andReturn((object) ['lock_result' => 0]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('original callback failure');

        app(MySqlAdvisoryLock::class)->synchronized(
            $connection,
            'taskflow:initial-admin',
            10,
            fn () => throw new RuntimeException('original callback failure'),
        );
    }
}
