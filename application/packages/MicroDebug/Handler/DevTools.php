<?php

namespace MicroDebug\Handler;

use Micro\Event\Message;
use Micro\Application\View;
use Micro\Http\Response\HtmlResponse;
use Micro\Http\Stream;

class DevTools
{
    /**
     * @var View
     */
    protected $view;

    public function boot()
    {
        if (!config('micro_debug.handlers.dev_tools', 0)) {
            return;
        }

        app('event')->attach('application.start', [$this, 'onApplicationStart']);
        app('event')->attach('render.start', [$this, 'onRenderStart']);
        app('event')->attach('application.end', [$this, 'onApplicationEnd']);
    }

    public function onApplicationStart(Message $message)
    {
        $this->view = new View('debug');
        $this->view->addPath(package_path('MicroDebug', 'Resources/views'));
    }

    public function onRenderStart(Message $message)
    {
        $view = $message->getParam('view');
        $view->section('styles', (string) $this->view->partial('css'));
    }

    public function onApplicationEnd(Message $message)
    {
        $response = $message->getParam('response');

        if ($response instanceof HtmlResponse) {

            $body = $response->getBody();

            if ($body->isSeekable()) {
                $body->rewind();
            }

            $b = $body->getContents();
            $b = explode('</body>', $b);
            $b[0] .= str_replace(array("\n", "\t", "\r"), "", $this->view->render()) . '</body>';

            $body = new Stream(fopen('php://temp', 'r+'));
            $body->write(implode('', $b));
            $body->rewind();

            $response->withBody($body);
        }
    }
}