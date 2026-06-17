<?php

declare(strict_types=1);

namespace RunOpenCode\Component\XmlParser\Tests\Pull;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RunOpenCode\Component\XmlParser\Parser\AbstractPullParser;
use RunOpenCode\Component\Stream\Stream;

final class BrokenDocumentTest extends TestCase
{
    #[Test]
    public function exception_handler_may_be_overridden(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Exception handler may be overridden.');

        $stream = Stream::path(__DIR__ . '/../Fixtures/broken.xml');
        $parser = new BrokenParser();

        [...$parser->parse($stream)];
    }
}

/**
 * @extends AbstractPullParser<mixed>
 */
final class BrokenParser extends AbstractPullParser
{
    protected function onParseError(string $message, int $code, int $lineno): void
    {
        throw new \RuntimeException('Exception handler may be overridden.');
    }
}
