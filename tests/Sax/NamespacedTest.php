<?php

declare(strict_types=1);

namespace RunOpenCode\Component\XmlParser\Tests\Sax;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use RunOpenCode\Component\XmlParser\Parser\AbstractSaxParser;
use RunOpenCode\Component\Stream\Stream;

final class NamespacedTest extends TestCase
{
    #[Test]
    public function parse(): void
    {
        $stream = Stream::path(__DIR__ . '/../Fixtures/namespaced.xml');
        $parser = new NamespacedParser();

        $this->assertSame([
            'Document start',
            'Namespace declaration: foo, https://example.com',
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
            'Element start: "HTTPS://EXAMPLE.COM:NAME", attributes: {"FOO":"10","BAR":"true"}',
            'Current path: "SAMPLE/ITEMS/ITEM/HTTPS://EXAMPLE.COM:NAME"',
            'Element data: "foo"',
            'Current path: "SAMPLE/ITEMS/ITEM/HTTPS://EXAMPLE.COM:NAME"',
            'Element end: "HTTPS://EXAMPLE.COM:NAME"',
            'Current path: "SAMPLE/ITEMS/ITEM/HTTPS://EXAMPLE.COM:NAME"',
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
            'Element start: "HTTPS://EXAMPLE.COM:NAME", attributes: {"HTTPS:\/\/EXAMPLE.COM:FOO":"10"}',
            'Current path: "SAMPLE/ITEMS/ITEM/HTTPS://EXAMPLE.COM:NAME"',
            'Element data: "bar"',
            'Current path: "SAMPLE/ITEMS/ITEM/HTTPS://EXAMPLE.COM:NAME"',
            'Element end: "HTTPS://EXAMPLE.COM:NAME"',
            'Current path: "SAMPLE/ITEMS/ITEM/HTTPS://EXAMPLE.COM:NAME"',
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
            'Element start: "HTTPS://EXAMPLE.COM:NAME", attributes: {"HTTPS:\/\/EXAMPLE.COM:BAR":"true"}',
            'Current path: "SAMPLE/ITEMS/ITEM/HTTPS://EXAMPLE.COM:NAME"',
            'Element data: "baz"',
            'Current path: "SAMPLE/ITEMS/ITEM/HTTPS://EXAMPLE.COM:NAME"',
            'Element end: "HTTPS://EXAMPLE.COM:NAME"',
            'Current path: "SAMPLE/ITEMS/ITEM/HTTPS://EXAMPLE.COM:NAME"',
            'Element start: "ID", attributes: []',
            'Current path: "SAMPLE/ITEMS/ITEM/ID"',
            'Element data: "3"',
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
final class NamespacedParser extends AbstractSaxParser
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

    protected function onNamespaceDeclarationStart(string $prefix, string $uri): void
    {
        $this->enqueue(\sprintf('Namespace declaration: %s, %s', $prefix, $uri));
    }

    protected function onNamespaceDeclarationEnd(string $prefix): void
    {
        $this->enqueue(\sprintf('End namespace declaration: %s', $prefix));
    }
}
