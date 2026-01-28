<?php

declare(strict_types=1);

namespace AmazeeIO\AmazeeAIConfigure\Tests\Service;

use AmazeeIO\AmazeeAIConfigure\SecretsWriter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Exception\ProcessFailedException;

#[CoversClass(SecretsWriter::class)]
final class SecretsWriterTest extends TestCase
{
    private string $tempDir;
    private string $logFile;
    private string $originalCwd;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/secrets_writer_test_'.uniqid();
        $this->logFile = $this->tempDir.'/var/secrets_writer.log';
        $this->originalCwd = getcwd() ?: '';

        mkdir($this->tempDir.'/bin', 0777, true);
        mkdir($this->tempDir.'/var', 0777, true);
    }

    protected function tearDown(): void
    {
        if ('' !== $this->originalCwd) {
            chdir($this->originalCwd);
        }

        if (is_dir($this->tempDir)) {
            $this->deleteDirectory($this->tempDir);
        }
    }

    #[Test]
    public function itWritesSecretsUsingConsoleCommand(): void
    {
        $this->writeConsoleScript(0);
        chdir($this->tempDir);

        $writer = new SecretsWriter();
        $writer->write([
            'AMAZEEAI_LLM_KEY' => 'secret-key',
            'AMAZEEAI_LLM_API_URL' => 'https://llm.example.test',
        ]);

        $this->assertFileExists($this->logFile);
        $log = file($this->logFile, \FILE_IGNORE_NEW_LINES);

        $this->assertIsArray($log);
        $this->assertNotEmpty($log);
        $firstEntry = json_decode($log[0], true);
        $this->assertIsArray($firstEntry);
        $this->assertSame('AMAZEEAI_LLM_KEY', $firstEntry['key']);
        $this->assertSame('dev', $firstEntry['env']);
        $this->assertSame('secret-key', $firstEntry['value']);
    }

    #[Test]
    public function itThrowsWhenConsoleCommandFails(): void
    {
        $this->writeConsoleScript(1);
        chdir($this->tempDir);

        $writer = new SecretsWriter();

        $this->expectException(ProcessFailedException::class);

        $writer->write([
            'AMAZEEAI_LLM_KEY' => 'secret-key',
        ]);
    }

    private function writeConsoleScript(int $exitCode): void
    {
        $script = <<<'PHP'
<?php
$logFile = '__LOG_FILE__';
$args = $_SERVER['argv'];
$value = trim(stream_get_contents(STDIN));

$key = $args[2] ?? '';
$env = 'dev';
foreach ($args as $arg) {
    if (str_starts_with($arg, '--env=')) {
        $env = substr($arg, 6);
        break;
    }
}

if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0777, true);
}

$entry = [
    'key' => $key,
    'env' => $env,
    'value' => $value,
];

file_put_contents($logFile, json_encode($entry) . PHP_EOL, FILE_APPEND);
exit(__EXIT_CODE__);
PHP;

        $script = str_replace('__LOG_FILE__', addslashes($this->logFile), $script);
        $script = str_replace('__EXIT_CODE__', (string) $exitCode, $script);

        file_put_contents($this->tempDir.'/bin/console', $script);
    }

    private function deleteDirectory(string $path): void
    {
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($path);
    }
}
