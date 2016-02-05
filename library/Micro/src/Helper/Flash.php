<?php

namespace Micro\Helper;

use Micro\Session\SessionNamespace;

class Flash
{
    /**
     * @var \Micro\Session\SessionNamespace
     */
    protected static $session;

    public function __construct()
    {
        self::$session = new SessionNamespace('flashMessage');

        if (!isset(self::$session->messages)) {
            self::$session->messages = [];
        }
    }

    /**
     * @param string $message
     * @param string $type
     * @return \Micro\Helper\FlashMessage
     */
    public function setMessage($message = \null, $type = 'success')
    {
        if ($message === \null) {
            $message = 'Информацията е записана';
        }

        self::$session->messages[] = [
            'message' => $message,
            'type'    => $type
        ];

        return $this;
    }

    /**
     * @return string
     */
    public function getMessages()
    {
        $messages = [];

        if (isset(self::$session->messages)) {
            $messages = self::$session->messages;
            self::$session->unsetAll();
        }

        return $messages;
    }
}