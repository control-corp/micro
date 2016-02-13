<?php

namespace Micro\Event;

class Manager
{
    /**
     * @var array
     */
    protected $events = [];

    /**
     * @return array
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * @param string $event
     * @param callable $callable
     * @param int $priority
     * @return Manager
     */
    public function attach($event, $callable, $priority = 10)
    {
        if (!isset($this->events[$event])) {
            $this->events[$event] = [];
        }

        $priority = (int) $priority;

        if (!isset($this->events[$event][$priority])) {
            $this->events[$event][$priority] = [];
        }

        $this->events[$event][$priority][] = $callable;

        return $this;
    }

    /**
     * @param string $event
     * @param array $params
     * @return mixed
     */
    public function trigger($event, array $params = [])
    {
        if (!isset($this->events[$event])) {
            return \null;
        }

        $r = \null;

        $listeners = $this->events[$event];

        krsort($listeners, SORT_NUMERIC);

        $message = new Message($event, $params);

        foreach ($listeners as $priority => $callbacks) {

            foreach ($callbacks as $callback) {

                $r = call_user_func($callback, $message);

                if ($message->stopped()) {
                    break 2;
                }
            }
        }

        return $r;
    }
}