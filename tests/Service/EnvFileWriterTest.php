<?php

declare(strict_types=1);

namespace AmazeeIO\AmazeeAIConfigure\Tests\Service;

use AmazeeIO\AmazeeAIConfigure\EnvFileWriter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(EnvFileWriter::class)]
final class EnvFileWriterTest extends TestCase
{
    private string $tempDir;
    private EnvFileWriter $writer;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/envwriter_test_'.uniqid();
        mkdir($this->tempDir);
        $this->writer = new EnvFileWriter(new NullLogger(), $this->tempDir);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempDir.'/.env.local')) {
            unlink($this->tempDir.'/.env.local');
        }
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
    }

    #[Test]
    public function itCreatesEnvLocalFile(): void
    {
        $this->writer->write([
            'TEST_KEY' => 'test_value',
        ]);

        $this->assertFileExists($this->tempDir.'/.env.local');
        $content = file_get_contents($this->tempDir.'/.env.local');
        $this->assertIsString($content);
        $this->assertStringContainsString('TEST_KEY=test_value', $content);
    }

    #[Test]
    public function itWritesMultipleVariables(): void
    {
        $this->writer->write([
            'API_KEY' => 'secret123',
            'API_URL' => 'https://api.example.com',
            'DEBUG' => 'true',
        ]);

        $content = file_get_contents($this->tempDir.'/.env.local');
        $this->assertIsString($content);
        $this->assertStringContainsString('API_KEY=secret123', $content);
        $this->assertStringContainsString('API_URL=https://api.example.com', $content);
        $this->assertStringContainsString('DEBUG=true', $content);
    }

    #[Test]
    public function itQuotesValuesWithSpaces(): void
    {
        $this->writer->write([
            'MESSAGE' => 'hello world',
        ]);

        $content = file_get_contents($this->tempDir.'/.env.local');
        $this->assertIsString($content);
        $this->assertStringContainsString('MESSAGE="hello world"', $content);
    }

    #[Test]
    public function itUpdatesExistingVariables(): void
    {
        // Write initial content.
        file_put_contents($this->tempDir.'/.env.local', "EXISTING_KEY=old_value\nOTHER_KEY=keep_this\n");

        $this->writer->write([
            'EXISTING_KEY' => 'new_value',
            'NEW_KEY' => 'added_value',
        ]);

        $content = file_get_contents($this->tempDir.'/.env.local');
        $this->assertIsString($content);
        $this->assertStringContainsString('EXISTING_KEY=new_value', $content);
        $this->assertStringContainsString('OTHER_KEY=keep_this', $content);
        $this->assertStringContainsString('NEW_KEY=added_value', $content);
        $this->assertStringNotContainsString('old_value', $content);
    }

    #[Test]
    public function itPreservesComments(): void
    {
        // Write initial content with comment.
        file_put_contents($this->tempDir.'/.env.local', "# This is a comment\nEXISTING_KEY=value\n");

        $this->writer->write([
            'NEW_KEY' => 'new_value',
        ]);

        $content = file_get_contents($this->tempDir.'/.env.local');
        $this->assertIsString($content);
        $this->assertStringContainsString('# This is a comment', $content);
    }

    #[Test]
    public function itEndsFileWithNewline(): void
    {
        $this->writer->write([
            'KEY' => 'value',
        ]);

        $content = file_get_contents($this->tempDir.'/.env.local');
        $this->assertIsString($content);
        $this->assertStringEndsWith("\n", $content);
    }
}
