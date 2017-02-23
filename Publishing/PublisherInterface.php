<?php

namespace Sidus\PublishingBundle\Publishing;

use Sidus\PublishingBundle\Entity\PublishableInterface;

/**
 * Interface for custom publishers
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface PublisherInterface
{
    /**
     * @param PublishableInterface $entity
     */
    public function create(PublishableInterface $entity);

    /**
     * @param PublishableInterface $entity
     */
    public function update(PublishableInterface $entity);

    /**
     * @param PublishableInterface $entity
     */
    public function delete(PublishableInterface $entity);

    /**
     * @return bool
     */
    public function publish();

    /**
     * @param PublishableInterface $entity
     *
     * @return bool
     */
    public function isSupported($entity);
}
