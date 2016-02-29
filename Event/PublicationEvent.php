<?php

namespace Sidus\PublishingBundle\Event;


use Sidus\PublishingBundle\Entity\PublishableInterface;

class PublicationEvent
{
    const CREATE = 'create';
    const UPDATE = 'update';
    const DELETE = 'delete';

    /** @var PublishableInterface */
    public $data;

    /** @var string */
    public $event;

    /** @var string */
    public $publicationUuid;

    /**
     * PublicationEvent constructor.
     * @param PublishableInterface $data
     * @param string $event
     */
    public function __construct(PublishableInterface $data, $event)
    {
        $this->data = $data;
        $this->event = $event;
        $this->publicationUuid = $data->getPublicationUuid();
    }
}
