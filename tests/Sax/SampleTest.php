<?php

declare(strict_types=1);

namespace RunOpenCode\Component\XmlParser\Tests\Sax;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use RunOpenCode\Component\XmlParser\Parser\AbstractSaxParser;
use RunOpenCode\Component\Stream\Stream;

final class SampleTest extends TestCase
{
    #[Test]
    public function parse(): void
    {
        $stream = Stream::path(__DIR__ . '/../Fixtures/sample.xml');
        $parser = new SampleParser();

        $this->assertSame([
            'Document start',
            'Element start: "SAMPLE", attributes: []',
            'Current path: "SAMPLE"',
            'Element start: "HEADER", attributes: []',
            'Current path: "SAMPLE/HEADER"',
            'Element data: "This is the header"',
            'Current path: "SAMPLE/HEADER"',
            'Element end: "HEADER"',
            'Current path: "SAMPLE/HEADER"',
            'Element start: "ITEMS", attributes: []',
            'Current path: "SAMPLE/ITEMS"',
            'Element start: "ITEM", attributes: []',
            'Current path: "SAMPLE/ITEMS/ITEM"',
            'Element start: "NAME", attributes: {"FOO":"10","BAR":"true"}',
            'Current path: "SAMPLE/ITEMS/ITEM/NAME"',
            'Element data: "foo"',
            'Current path: "SAMPLE/ITEMS/ITEM/NAME"',
            'Element end: "NAME"',
            'Current path: "SAMPLE/ITEMS/ITEM/NAME"',
            'Element start: "ID", attributes: []',
            'Current path: "SAMPLE/ITEMS/ITEM/ID"',
            'Element data: "1"',
            'Current path: "SAMPLE/ITEMS/ITEM/ID"',
            'Element end: "ID"',
            'Current path: "SAMPLE/ITEMS/ITEM/ID"',
            'Element end: "ITEM"',
            'Current path: "SAMPLE/ITEMS/ITEM"',
            'Element start: "ITEM", attributes: []',
            'Current path: "SAMPLE/ITEMS/ITEM"',
            'Element start: "NAME", attributes: {"FOO":"10"}',
            'Current path: "SAMPLE/ITEMS/ITEM/NAME"',
            'Element data: "bar"',
            'Current path: "SAMPLE/ITEMS/ITEM/NAME"',
            'Element end: "NAME"',
            'Current path: "SAMPLE/ITEMS/ITEM/NAME"',
            'Element start: "ID", attributes: []',
            'Current path: "SAMPLE/ITEMS/ITEM/ID"',
            'Element data: "2"',
            'Current path: "SAMPLE/ITEMS/ITEM/ID"',
            'Element end: "ID"',
            'Current path: "SAMPLE/ITEMS/ITEM/ID"',
            'Element end: "ITEM"',
            'Current path: "SAMPLE/ITEMS/ITEM"',
            'Element start: "ITEM", attributes: []',
            'Current path: "SAMPLE/ITEMS/ITEM"',
            'Element start: "NAME", attributes: {"BAR":"true"}',
            'Current path: "SAMPLE/ITEMS/ITEM/NAME"',
            'Element data: "baz"',
            'Current path: "SAMPLE/ITEMS/ITEM/NAME"',
            'Element end: "NAME"',
            'Current path: "SAMPLE/ITEMS/ITEM/NAME"',
            'Element start: "ID", attributes: []',
            'Current path: "SAMPLE/ITEMS/ITEM/ID"',
            'Element data: "3"',
            'Current path: "SAMPLE/ITEMS/ITEM/ID"',
            'Element end: "ID"',
            'Current path: "SAMPLE/ITEMS/ITEM/ID"',
            'Element end: "ITEM"',
            'Current path: "SAMPLE/ITEMS/ITEM"',
            'Element start: "ITEM", attributes: []',
            'Current path: "SAMPLE/ITEMS/ITEM"',
            'Element start: "NAME", attributes: []',
            'Current path: "SAMPLE/ITEMS/ITEM/NAME"',
            'Element end: "NAME"',
            'Current path: "SAMPLE/ITEMS/ITEM/NAME"',
            'Element start: "ID", attributes: []',
            'Current path: "SAMPLE/ITEMS/ITEM/ID"',
            'Element end: "ID"',
            'Current path: "SAMPLE/ITEMS/ITEM/ID"',
            'Element end: "ITEM"',
            'Current path: "SAMPLE/ITEMS/ITEM"',
            'Element start: "ITEM", attributes: []',
            'Current path: "SAMPLE/ITEMS/ITEM"',
            'Element start: "NAME", attributes: []',
            'Current path: "SAMPLE/ITEMS/ITEM/NAME"',
            'Element end: "NAME"',
            'Current path: "SAMPLE/ITEMS/ITEM/NAME"',
            'Element start: "ID", attributes: []',
            'Current path: "SAMPLE/ITEMS/ITEM/ID"',
            'Element end: "ID"',
            'Current path: "SAMPLE/ITEMS/ITEM/ID"',
            'Element end: "ITEM"',
            'Current path: "SAMPLE/ITEMS/ITEM"',
            'Element end: "ITEMS"',
            'Current path: "SAMPLE/ITEMS"',
            'Element end: "SAMPLE"',
            'Current path: "SAMPLE"',
            'Document end',
        ], [...$parser->parse($stream)]);
    }
}

/**
 * @extends AbstractSaxParser<string>
 */
final class SampleParser extends AbstractSaxParser
{
    protected function onDocumentStart(StreamInterface $stream): void
    {
        $this->enqueue('Document start');
    }

    protected function onElementStart(string $name, array $attributes): void
    {
        $this->enqueue(\sprintf('Element start: "%s", attributes: %s', $name, \Safe\json_encode($attributes)));
        $this->enqueue(\sprintf('Current path: "%s"', $this->path));
    }

    protected function onElementData(string $data): void
    {
        $this->enqueue(\sprintf('Element data: "%s"', $data));
        $this->enqueue(\sprintf('Current path: "%s"', $this->path));
    }

    protected function onElementEnd(string $name): void
    {
        $this->enqueue(\sprintf('Element end: "%s"', $name));
        $this->enqueue(\sprintf('Current path: "%s"', $this->path));
    }

    protected function onDocumentEnd(StreamInterface $stream): void
    {
        $this->enqueue('Document end');
    }
}
