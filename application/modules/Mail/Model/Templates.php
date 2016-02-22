<?php

namespace Mail\Model;

use Micro\Exception\Exception as CoreException;
use Micro\Model\DatabaseAbstract;
use Zend\Mime;
use Zend\Mail\Message;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;

class Templates extends DatabaseAbstract
{
    protected $table = Table\Templates::class;
    protected $entity = Entity\Template::class;

    protected $attachments = array();

    public function addAttachment($attachment)
    {
        if ($attachment instanceof Mime\Part) {
            $this->attachments[] = $attachment;
        }

        if (is_string($attachment) && file_exists($attachment)) {
            $pathinfo        = pathinfo($attachment);
            $at              = new Mime\Part(file_get_contents($attachment));
            $at->disposition = Mime\Mime::DISPOSITION_ATTACHMENT;
            $at->encoding    = Mime\Mime::ENCODING_BASE64;
            $at->filename    = $pathinfo['basename'];
            $this->attachments[] = $at;
        }
    }

    public function getTemplate($alias)
    {
        $this->resetSelect(true);

        if (is_numeric($alias)) {
            $this->addWhere('id', $alias);
        } else {
            $this->addWhere('alias', $alias);
        }

        $row = $this->getTable()->fetchRow($this->getJoinSelect());

        return $this->rowToObject($row);
    }

    public function send($alias, $to, $from = null, array $data = null)
    {
        if (!config('mail.enabled')) {
            return;
        }

        $template = $this->getTemplate($alias);

        if (!$template) {
            throw new CoreException('Cannot find template with alias/id: ' . $alias, 404);
        }

        if ($data === null) {
            $data = array();
        }

        $template->setDescription(
            $this->replaceTemplateContent($template->getDescription(), $data)
        );

        $template->setName(
            $this->replaceTemplateContent($template->getName(), $data)
        );

        $preffix = server_url(base_url());

        $content = preg_replace('#background\: url\((.*)\)#', 'background: url('. $preffix .'$1)', $template->getDescription());
        $content = preg_replace('#\<img src=\"(.*)\"#', '<img src="'. $preffix .'$1"', $content);

        $template->getDescription($content);

        $message = new Message();
        $message->setSubject($template->getName());

        if ($from) {
            $message->setFrom($from);
        }

        $message->addTo($to);

        $content = $template->getDescription();

        $content = '<html><head><title>' . $template->getName() . '</title></head><body>' . $template->getDescription() . '</body></html>';

        $html = new Mime\Part($content);
        $html->setType(Mime\Mime::TYPE_HTML);
        $html->setCharset('utf-8');

        $mimeMessage = new Mime\Message();
        $mimeMessage->addPart($html);

        foreach ($this->attachments as $attachment) {
            $mimeMessage->addPart($attachment);
        }

        $message->setBody($mimeMessage);

        try {
            $transport = new SmtpTransport(
                new SmtpOptions(config('mail.options'))
            );
            $transport->send($message);
        } catch (\Exception $e) {
            throw $e;
        }

        return $this;
    }

    public function replaceTemplateContent($content, $data)
    {
        if (preg_match_all('~\[(\w+)\]~', $content, $matches)) {
            foreach($matches[1] as $m) {
                if (isset($data[$m])) {
                    $pattern = '~\[' . $m . '\]~';
                    $content = preg_replace($pattern, $data[$m], $content);
                }
            }
        }

        return $content;
    }
}