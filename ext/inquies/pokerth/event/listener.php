<?php

namespace inquies\pokerth\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
    /**
     * Assign functions defined in this class to event listeners in the core
     *
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return [
            'core.user_setup_after' => 'test',
        ];
    }

    /**
     *
     * @param \phpbb\event\data $event The event object
     */
    public function test($event)
    {

    }
}