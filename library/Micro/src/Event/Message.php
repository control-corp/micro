<?php

namespace Micro\Event;

class Message
{
    /**
     * @var string
     */
    protected $event;

    /**
     * @var mixed
     */
    protected $params = [];

    /**
     * @var bool
     */
    protected $stopPropagation = \false;

    /**
     * @param string $event
     * @param mixed $params
     */
    public function __construct($event, array $params = [])
    {
        $this->setEvent($event);
        $this->setParams($params);
    }

	/**
     * @return string $event
     */
    public function getEvent ()
    {
        return $this->event;
    }

	/**
     * @param string $event
     * @return \Micro\Event\Message
     */
    public function setEvent ($event)
    {
        $this->event = $event;

        return $this;
    }

	/**
     * @return mixed $params
     */
    public function getParams ()
    {
        return $this->params;
    }

    /**
     * @return mixed $param
     */
    public function getParam ($name)
    {
        if (isset($this->params[$name])) {
            return $this->params[$name];
        }

        return \null;
    }

	/**
     * @param mixed $params
     * @return \Micro\Event\Message
     */
    public function setParams (array $params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * @param bool $value
     * @return \Micro\Event\Message
     */
    public function setStopPropagation($value)
    {
        $this->stopPropagation = (bool) $value;

        return $this;
    }

    /**
     * @return \Micro\Event\Message
     */
    public function stop()
    {
        $this->setStopPropagation(\true);

        return $this;
    }

    /**
     * @return bool
     */
    public function stopped()
    {
        return $this->stopPropagation;
    }
}