<?php

declare(strict_types=1);

namespace RunOpenCode\Component\XmlParser\Parser;

use Psr\Http\Message\StreamInterface;
use RunOpenCode\Component\XmlParser\Contract\XmlParserInterface;
use RunOpenCode\Component\XmlParser\Exception\LogicException;
use RunOpenCode\Component\XmlParser\Exception\ParseException;
use RunOpenCode\Component\XmlParser\Exception\RuntimeException;

use function RunOpenCode\Component\Stream\stream_to_resource;

/**
 * @template T
 *
 * @phpstan-type TransformerFunction = callable(\DOMNode, string=, string=): (iterable<T>|null|void)
 *
 * @implements XmlParserInterface<T>
 */
abstract class AbstractPullParser implements XmlParserInterface
{
    /**
     * A current stack of processing elements.
     *
     * @var string[]
     */
    public private(set) array $stack = [];

    /**
     * Get current element.
     */
    final public ?string $current {
        get => \count($this->stack) > 0 ? $this->stack[\count($this->stack) - 1] : null;
    }

    /**
     * Get current traversal depth.
     */
    final public int $depth {
        get => \count($this->stack);
    }

    /**
     * Get current element path.
     */
    final public ?string $path {
        get => \count($this->stack) > 0 ? \implode('/', $this->stack) : null;
    }

    /**
     * @var array<string, TransformerFunction>
     */
    private array $listeners = [];

    /**
     * {@inheritdoc}
     */
    final public function parse(StreamInterface $stream): iterable
    {
        $previousUseInternalErrors = \libxml_use_internal_errors(true);
        $resource                  = stream_to_resource($stream);

        try {
            $reader = \XMLReader::fromStream($resource);
        } catch (\Exception $exception) {
            throw new RuntimeException('Unable to open stream.', $exception);
        }

        $this->onDocumentStart($stream);

        try {
            yield from $this->traverse($reader);

            $this->onDocumentEnd($stream);
        } finally {
            try {
                $reader->close();
            } catch (\Exception) {
                // noop.
            }

            \libxml_clear_errors();
            \libxml_use_internal_errors($previousUseInternalErrors);
        }

        $reader->close();
    }

    /**
     * Document start handler, executed when parsing process started.
     *
     * Override this method, should you need to execute custom logic when parsing of document started.
     *
     * @param StreamInterface $stream Stream being parsed.
     */
    protected function onDocumentStart(StreamInterface $stream): void
    {
        // noop.
    }

    /**
     * Document end handler, executed when parsing process ended.
     *
     * Override this method, should you need to execute custom logic when parsing of document ended.
     *
     * @param StreamInterface $stream Stream being parsed.
     */
    protected function onDocumentEnd(StreamInterface $stream): void
    {
        // noop.
    }

    /**
     * Parsing error handler.
     *
     * Override this method, should you need to execute custom logic on parsing exception.
     */
    protected function onParseError(string $message, int $code, int $lineno): void
    {
        throw new ParseException($message, $code, $lineno);
    }

    /**
     * Register element listener.
     *
     * @param string              $name        Name of the element or path to the element.
     * @param TransformerFunction $transformer Transformer function to execute when element is encountered.
     */
    final protected function onElement(string $name, callable $transformer): void
    {
        $name = \strtoupper($name);

        if (isset($this->listeners[$name])) {
            throw new LogicException(\sprintf('Element listener for "%s" is already registered.', $name));
        }

        $this->listeners[$name] = $transformer;
    }

    /**
     * @return iterable<T>
     */
    private function traverse(\XMLReader $reader): iterable
    {
        $document = new \DOMDocument();

        while ($reader->read()) {
            if ($error = \libxml_get_last_error()) {
                $this->onParseError($error->message, $error->code, $error->line);
            }

            if ($reader->nodeType === \XMLReader::ELEMENT) {
                $name = \sprintf(
                    '%s%s',
                    $reader->namespaceURI ? \sprintf('%s:', \strtoupper(\rtrim($reader->namespaceURI, '/'))) : '',
                    \strtoupper($reader->name)
                );

                $this->stack[] = $name;

                $listener = $this->listeners[$name] ?? $this->listeners[$this->path] ?? null;

                if (null !== $listener) {
                    /** @var string $path */
                    $path = $this->path;
                    $node = $reader->expand($document);

                    \assert($node instanceof \DOMNode);

                    /**
                     * @var iterable<T>|null $result
                     */
                    $result = $listener($node, $name, $path);

                    if (null !== $result) {
                        yield from $result;
                    }
                }
            }

            if ($reader->isEmptyElement || \XMLReader::END_ELEMENT === $reader->nodeType) {
                \array_pop($this->stack);
            }
        }

        if ($error = \libxml_get_last_error()) {
            $this->onParseError($error->message, $error->code, $error->line);
        }
    }
}
