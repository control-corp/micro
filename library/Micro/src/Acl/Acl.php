<?php

namespace Micro\Acl;

class Acl implements AclInterface
{
    protected $data;

    protected static $resolver;

    public function __construct(array $data = [])
    {
        if (empty($data)) {
            $this->data = static::$resolver !== \null
                          ? call_user_func(static::$resolver)
                          : [];
        } else {
            $this->data = $data;
        }
    }

    /**
     * (non-PHPdoc)
     * @see \Micro\Acl\AclInterface::isAllowed()
     */
    public function isAllowed($role = \null, $resource = \null, $privilege = \null)
    {
        if ($role === \null || $resource === \null) {
            return \true;
        }

        if (!isset($this->data[$role])) {
            return \false;
        }

        $item = $this->data[$role];

        do {

            if (isset($item['resources'][$resource]) && $item['resources'][$resource] === $privilege) {
                return \true;
            }

            if (!isset($item['parent']) || $item['parent'] === \null || !isset($this->data[$item['parent']])) {
                break;
            }

            $item = $this->data[$item['parent']];

        } while (\true);

        return \false;
    }

    /**
     * Call
     * @param callable $resolver
     */
    public static function setResolver($resolver)
    {
        static::$resolver = $resolver;
    }
}