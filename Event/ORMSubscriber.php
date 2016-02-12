<?php

namespace Sidus\PublishingBundle\Event;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Sidus\PublishingBundle\Entity\PublishableInterface;
use Sidus\PublishingBundle\Publishing\PublisherInterface;

class ORMSubscriber implements EventSubscriber
{
    /** @var PublisherInterface[] */
    protected $publishers;

    /** @var PublisherInterface[] */
    protected $activePublishers = [];

    public function __construct(array $publishers)
    {
        $this->publishers = $publishers;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::postPersist,
            Events::postUpdate,
            Events::postRemove,
        ];
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $this->postUpdate($args);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        if (!$entity instanceof PublishableInterface) {
            return;
        }
        foreach ($this->publishers as $publisher) {
            if ($publisher->isSupported($entity)) {
                $publisher->update($entity);
                $this->activePublishers[] = $publisher;
            }
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        if (!$entity instanceof PublishableInterface) {
            return;
        }
        foreach ($this->publishers as $publisher) {
            if ($publisher->isSupported($entity)) {
                $publisher->remove($entity);
                $this->activePublishers[] = $publisher;
            }
        }
    }

    /**
     * Push all entities on remote
     */
    public function commit()
    {
        foreach ($this->activePublishers as $publisher) {
            $publisher->push();
        }
    }

    /**
     * Trigger commit on kernel terminate
     */
    public function onKernelTerminate()
    {
        $this->commit();
    }
}