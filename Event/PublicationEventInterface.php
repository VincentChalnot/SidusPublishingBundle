<?php

namespace Sidus\PublishingBundle\Event;

use Sidus\PublishingBundle\Entity\PublishableInterface;

/**
 * Interface for all publication events
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface PublicationEventInterface
{
    const CREATE = 'create';
    const UPDATE = 'update';
    const DELETE = 'delete';

    /**
     * @param PublishableInterface $data
     * @param string               $event
     *
     * @return PublicationEventInterface
     */
    public static function build(PublishableInterface $data, $event);
}
