<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class ContainerEntrypointTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/tallerflow-entrypoint-'.bin2hex(random_bytes(8));
        mkdir($this->directory.'/bin', 0755, true);
        mkdir($this->directory.'/public');

        $script = file_get_contents(dirname(__DIR__, 2).'/container-entrypoint.sh');
        file_put_contents($this->directory.'/entrypoint.sh', str_replace('cd /var/www/html', 'cd '.escapeshellarg($this->directory), $script));
        file_put_contents($this->directory.'/bin/php', <<<'SH'
#!/bin/sh
printf '%s\n' "$*" >> "$ENTRYPOINT_TEST_LOG"
if [ "$*" = 'artisan migrate --force --no-interaction' ]; then
    exit "${MIGRATION_EXIT_CODE:-0}"
fi
SH);
        file_put_contents($this->directory.'/bin/chown', "#!/bin/sh\nexit 0\n");
        chmod($this->directory.'/bin/php', 0755);
        chmod($this->directory.'/bin/chown', 0755);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->directory);

        parent::tearDown();
    }

    public function test_migrations_run_before_optimization_and_server_start(): void
    {
        $process = $this->runEntrypoint();

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $this->assertSame([
            'artisan storage:link',
            'artisan view:clear',
            'artisan migrate --force --no-interaction',
            'artisan optimize',
            'server-started',
        ], file($this->directory.'/commands.log', FILE_IGNORE_NEW_LINES));
    }

    public function test_failed_migrations_prevent_server_start(): void
    {
        $process = $this->runEntrypoint(42);

        $this->assertSame(42, $process->getExitCode(), $process->getErrorOutput());
        $this->assertSame([
            'artisan storage:link',
            'artisan view:clear',
            'artisan migrate --force --no-interaction',
        ], file($this->directory.'/commands.log', FILE_IGNORE_NEW_LINES));
    }

    private function runEntrypoint(int $migrationExitCode = 0): Process
    {
        $process = new Process(['sh', $this->directory.'/entrypoint.sh', 'php', 'server-started'], env: [
            'PATH' => $this->directory.'/bin:'.getenv('PATH'),
            'ENTRYPOINT_TEST_LOG' => $this->directory.'/commands.log',
            'MIGRATION_EXIT_CODE' => (string) $migrationExitCode,
        ]);
        $process->run();

        return $process;
    }
}
