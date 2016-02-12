<?php

namespace Sidus\PublishingBundle\Entity;


interface PublishableInterface
{
    /**
     * @return string
     */
    public function getPublicationUUID();
}