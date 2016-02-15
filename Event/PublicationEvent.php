<?php

namespace Sidus\PublishingBundle\Event;


use Sidus\PublishingBundle\Entity\PublishableInterface;

class PublicationEvent
{
    const UPDATE = 'update';
    const REMOVE = 'remove';

    /** @var PublishableInterface */
    public $entity;

    /** @var string */
    public $event;

    public $publicationUUID;

    /**
     * PublicationEvent constructor.
     * @param PublishableInterface $entity
     * @param string $event
     */
    public function __construct(PublishableInterface $entity, $event)
    {
        $this->entity = $entity;
        $this->event = $event;
        $this->publicationUUID = $entity->getPublicationUUID();
    }
}
