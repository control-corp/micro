<?php

namespace Micro\Http\Response;

use Micro\Http\Response;
use Micro\Http\Body;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;

/**
 * HTML response.
 *
 * Allows creating a response by passing an HTML string to the constructor;
 * by default, sets a status code of 200 and sets the Content-Type header to
 * text/html.
 */
class HtmlResponse extends Response
{
    /**
     * Create an HTML response.
     *
     * Produces an HTML response with a Content-Type of text/html and a default
     * status of 200.
     *
     * @param string|StreamInterface $html HTML or stream for the message body.
     * @param int $status Integer status code for the response; 200 by default.
     * @param array $headers Array of headers to use at initialization.
     * @throws InvalidArgumentException if $html is neither a string or stream.
     */
    public function __construct($html = '', $status = 200, array $headers = [])
    {
        parent::__construct(
            $status,
            ($headers + ['Content-Type' => ['text/html; charset=utf-8']]),
            $this->createBody($html)
        );
    }

    /**
     * Create the message body.
     *
     * @param string|StreamInterface $html
     * @return StreamInterface
     * @throws InvalidArgumentException if $html is neither a string or stream.
     */
    private function createBody($html)
    {
        if ($html instanceof StreamInterface) {
            return $html;
        }

        if (! is_string($html)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid content (%s) provided to %s',
                (is_object($html) ? get_class($html) : gettype($html)),
                __CLASS__
            ));
        }

        $body = new Body(fopen('php://temp', 'r+'));
        $body->write($html);
        $body->rewind();
        return $body;
    }
}