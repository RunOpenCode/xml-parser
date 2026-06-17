<?php

declare(strict_types=1);

namespace RunOpenCode\Component\XmlParser\Tests\Pull;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RunOpenCode\Component\Stream\Stream;
use RunOpenCode\Component\XmlParser\Parser\AbstractPullParser;

final class SampleTest extends TestCase
{
    #[Test]
    public function parse(): void
    {
        $stream = Stream::path(__DIR__ . '/../Fixtures/sample.xml');
        $parser = new SampleParser();

        $this->assertSame([
            'Path: SAMPLE/HEADER, content -> This is the header',
            'Path: SAMPLE/ITEMS/ITEM, content -> name: foo, id: 1',
            'Path: SAMPLE/ITEMS/ITEM, content -> name: bar, id: 2',
            'Path: SAMPLE/ITEMS/ITEM, content -> name: baz, id: 3',
            'Path: SAMPLE/ITEMS/ITEM, content -> name: NONE, id: NONE',
            'Path: SAMPLE/ITEMS/ITEM, content -> name: NONE, id: NONE',
        ], [...$parser->parse($stream)]);
    }
}

/**
 * @extends AbstractPullParser<mixed>
 */
final class SampleParser extends AbstractPullParser
{
    public function __construct()
    {
        $this->onElement('header', $this->onHeader(...));
        $this->onElement('sample/items/item', $this->onItem(...));
    }

    /**
     * @return iterable<string>
     */
    private function onHeader(\DOMNode $node): iterable
    {
        yield \sprintf('Path: %s, content -> %s', $this->path, $node->textContent);
    }

    /**
     * @return iterable<string>
     */
    private function onItem(\DomNode $node): iterable
    {
        $xpath = new \DOMXPath($node->ownerDocument ?? throw new \RuntimeException('Owner document is missing.'));

        yield \sprintf(
            'Path: %s, content -> %s, %s',
            $this->path,
            \sprintf('name: %s', \trim($xpath->query('./Name', $node)?->item(0)?->textContent) ?: 'NONE'), // @phpstan-ignore-line
            \sprintf('id: %s', \trim($xpath->query('./Id', $node)?->item(0)?->textContent) ?: 'NONE'),     // @phpstan-ignore-line
        );
    }
}
