<?php

declare(strict_types=1);

namespace RunOpenCode\Component\XmlParser\Parser;

use Psr\Http\Message\StreamInterface;
use RunOpenCode\Component\XmlParser\Contract\XmlParserInterface;
use RunOpenCode\Component\XmlParser\Exception\ParseException;

/**
 * @template T of mixed
 *
 * @phpstan-type SaxParserOptions array{
 *     buffer_size: int,
 *     case_folding: bool,
 *     separator: string,
 *     encoding: string,
 *     skip_tagstart: int|null,
 *     skip_white: bool|null,
 *     parse_huge: bool|null,
 * }
 *
 * @implements XmlParserInterface<T>
 */
abstract class AbstractSaxParser implements XmlParserInterface
{
    /**
     * Declared namespaces in document.
     *
     * @var array<string, string>
     */
    public private(set) array $namespaces = [];

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
     * @var SaxParserOptions
     */
    private readonly array $options;

    /**
     * A queue of parsing results to yield.
     *
     * @var T[]
     */
    private array $queue = [];

    /**
     * @param array{
     *     buffer_size?: int,
     *     case_folding?: bool,
     *     separator?: string,
     *     encoding?: string,
     *     skip_tagstart?: null|int,
     *     skip_white?: null|bool,
     *     parse_huge?: null|bool,
     *  } $options
     */
    public function __construct(array $options = [])
    {
        $this->options = \array_merge([
            'buffer_size'   => 4096,
            'case_folding'  => true,
            'separator'     => ':',
            'encoding'      => 'UTF-8',
            'skip_tagstart' => null,
            'skip_white'    => null,
            'parse_huge'    => null,
        ], $options);
    }

    /**
     * {@inheritdoc}
     */
    final public function parse(StreamInterface $stream): iterable
    {
        $encoding  = $this->options['encoding'];
        $separator = $this->options['separator'];
        $parser    = \xml_parser_create_ns($encoding, $separator);

        if (false === $this->options['case_folding']) {
            \xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        }

        if (null !== $this->options['skip_tagstart']) {
            \xml_parser_set_option($parser, XML_OPTION_SKIP_TAGSTART, $this->options['skip_tagstart']);
        }

        if (null !== $this->options['skip_white']) {
            \xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, $this->options['skip_white']);
        }

        if (null !== $this->options['parse_huge']) {
            \xml_parser_set_option($parser, XML_OPTION_PARSE_HUGE, $this->options['parse_huge']);
        }

        $onElementStart = \Closure::bind(function(\XMLParser $parser, string $name, array $attributes): void {
            $name          = $this->normalize($name);
            $this->stack[] = $name;

            $normalizedAttributes = [];

            foreach ($attributes as $attribute => $value) {
                $normalizedAttributes[$this->normalize($attribute)] = $value;
            }

            $this->onElementStart($name, $normalizedAttributes); // @phpstan-ignore-line
        }, $this);

        $onElementEnd = \Closure::bind(function(\XMLParser $parser, string $name): void {
            $name = $this->normalize($name);

            $this->onElementEnd($name);

            \array_pop($this->stack);
        }, $this);

        $onElementData = \Closure::bind(function(\XMLParser $parser, ?string $data): void {
            $data = null !== $data ? \trim($data) : null;

            if (null !== $data && '' !== $data) {
                $this->onElementData($data);
            }
        }, $this);

        $onNamespaceDeclarationStart = \Closure::bind(function(\XMLParser $parser, string $prefix, string $uri): void {
            $uri                       = \rtrim($uri, '/');
            $this->namespaces[$prefix] = $uri;

            $this->onNamespaceDeclarationStart($prefix, $uri);
        }, $this);

        $onNamespaceDeclarationEnd = \Closure::bind(function(\XMLParser $parser, string $prefix): void {
            $this->onNamespaceDeclarationEnd($prefix);
        }, $this);

        \xml_set_element_handler($parser, $onElementStart, $onElementEnd);
        \xml_set_character_data_handler($parser, $onElementData);
        \xml_set_start_namespace_decl_handler($parser, $onNamespaceDeclarationStart);
        \xml_set_end_namespace_decl_handler($parser, $onNamespaceDeclarationEnd);

        $this->onDocumentStart($stream);

        while (!$stream->eof()) {
            $chunk = $stream->read($this->options['buffer_size']);

            if (1 !== \xml_parse($parser, $chunk, $stream->eof())) {
                $this->onParseError(
                    \xml_error_string(\xml_get_error_code($parser)) ?? 'Unknown error occurred during parse.',
                    \xml_get_error_code($parser),
                    \xml_get_current_line_number($parser),
                );
            }

            yield from $this->queue;

            $this->queue = [];
        }

        $this->onDocumentEnd($stream);

        yield from $this->queue;

        $this->queue = [];
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
     * Element start handler, executed when XML tag is entered.
     *
     * Override this method, should you need to execute custom logic when XML tag is entered.
     *
     * Stack is alredy updated, element is on stack.
     *
     * @param string   $name       Name of the element, uppercased.
     * @param string[] $attributes Hashmap of attributes of the element, with uppercased keys.
     */
    protected function onElementStart(string $name, array $attributes): void
    {
        // noop.
    }

    /**
     * Element CDATA handler, executed when XML tag CDATA is parsed.
     *
     * Override this method, should you need to execute custom logic when XML tag CDATA is parsed.
     */
    protected function onElementData(string $data): void
    {
        // noop.
    }

    /**
     * Element end handler, executed when XML tag is leaved.
     *
     * Override this method, should you need to execute custom logic when XML tag is leaved.
     *
     * After this method is executed, stack is updated, element is removed from the stack.
     *
     * @param string $name Name of the element, uppercased.
     */
    protected function onElementEnd(string $name): void
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
     * Start namespace declaration handler, executed when namespace declaration started.
     *
     * Override this method, should you need to execute custom logic on namespace declaration start.
     *
     * @param string $prefix Namespace prefix.
     * @param string $uri    Namespace URI.
     */
    protected function onNamespaceDeclarationStart(string $prefix, string $uri): void
    {
        // noop
    }

    /**
     * End namespace declaration handler, executed when namespace declaration ended.
     *
     * Override this method, should you need to execute custom logic on namespace declaration end.
     *
     * @param string $prefix Namespace prefix.
     */
    protected function onNamespaceDeclarationEnd(string $prefix): void
    {
        // noop
    }

    /**
     * Enqueue values to yield as parsing results.
     *
     * During the parsing process, you can enqueue values to yield as parsing results.
     *
     * This method allows you to add multiple values to the queue, which will be yielded later in the parsing process.
     *
     * @param T ...$value
     *
     * @return void
     */
    final protected function enqueue(mixed ...$value): void
    {
        $this->queue = \array_merge(
            $this->queue,
            \array_values($value),
        );
    }

    /**
     * Normalize namespaced tag name.
     *
     * It just removes trailing slash from the namespace prefix, if any.
     */
    private function normalize(string $name): string
    {
        return \str_replace('/:', ':', $name);
    }
}
