<?php

namespace Sidus\PublishingBundle\Publishing;


use Sidus\PublishingBundle\Entity\PublishableInterface;

interface PublisherInterface
{
    /**
     * @param PublishableInterface $entity
     */
    public function update(PublishableInterface $entity);

    /**
     * @param PublishableInterface $entity
     */
    public function remove(PublishableInterface $entity);

    /**
     * @return bool
     */
    public function push();

    /**
     * @param PublishableInterface $entity
     * @return bool
     */
    public function isSupported($entity);
}
