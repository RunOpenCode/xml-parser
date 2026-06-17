<?php

declare(strict_types=1);

namespace RunOpenCode\Component\XmlParser\Contract;

use Psr\Http\Message\StreamInterface;

/**
 * @template T of mixed
 */
interface XmlParserInterface
{
    /**
     * Parse XML document and yield parsed values.
     *
     * @param StreamInterface $stream Stream to parse.
     *
     * @return iterable<T> Iterable of parsed values.
     */
    public function parse(StreamInterface $stream): iterable;
}
