<?php

namespace Micro\Event;

class Manager
{
    /**
     * @var array
     */
    protected $events = [];

    /**
     * @var int
     */
    protected $serial = PHP_INT_MAX;

    /**
     * @return \SplPriorityQueue
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * @param string $event
     * @param callable $callable
     * @param number $priority
     * @return \Micro\Event\Manager
     */
    public function attach($event, $callable, $priority = 10)
    {
        if (empty($this->events[$event])) {
            $this->events[$event] = new \SplPriorityQueue();
        }

        $this->events[$event]->insert(
            $callable,
            [$priority, $this->serial--]
        );

        return $this;
    }

    /**
     * @param string $event
     * @param array $params
     * @return mixed
     */
    public function trigger($event, array $params = [])
    {
        if (!array_key_exists($event, $this->events)) {
            $this->events[$event] = new \SplPriorityQueue();
        }

        $r = \null;

        foreach (clone $this->events[$event] as $callback) {

            $e = new Message($event, $params);

            $r = call_user_func($callback, $e);

            if ($e->stopped()) {
                break;
            }
        }

        return $r;
    }
}