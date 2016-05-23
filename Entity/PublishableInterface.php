<?php

namespace Sidus\PublishingBundle\Entity;

/**
 * All entities that can be published must implements this interface
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface PublishableInterface
{
    /**
     * @return string
     */
    public function getPublicationUuid();
}
