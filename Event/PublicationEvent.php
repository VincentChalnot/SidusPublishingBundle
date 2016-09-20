<?php

namespace Sidus\PublishingBundle\Event;

use Sidus\PublishingBundle\Entity\PublishableInterface;

/**
 * Instantiated during the publication of an entity
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class PublicationEvent implements PublicationEventInterface
{
    /** @var PublishableInterface */
    public $data;

    /** @var string */
    public $event;

    /** @var string */
    public $publicationUuid;

    /**
     * @param PublishableInterface $data
     * @param string               $event
     *
     * @return PublicationEvent
     */
    public static function build(PublishableInterface $data, $event)
    {
        return new self($data, $event);
    }

    /**
     * @param PublishableInterface $data
     * @param string               $event
     */
    public function __construct(PublishableInterface $data, $event)
    {
        $this->data = $data;
        $this->event = $event;
        $this->publicationUuid = $data->getPublicationUuid();
    }
}
