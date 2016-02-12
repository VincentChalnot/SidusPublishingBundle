<?php

namespace Sidus\PublishingBundle\Publishing;

use Sidus\PublishingBundle\Entity\PublishableInterface;
use Symfony\Component\Serializer\SerializerInterface;

class Publisher implements PublisherInterface
{
    /** @var string */
    protected $code;

    /** @var string */
    protected $entity;

    /** @var string */
    protected $format;

    /** @var SerializerInterface */
    protected $serializer;

    /** @var PusherInterface[] */
    protected $pushers;

    /** @var array */
    protected $options;

    /**
     * @param string $code
     * @param string $entity
     * @param string $format
     * @param SerializerInterface $serializer
     * @param PusherInterface[] $pushers
     * @param array $options
     */
    public function __construct($code, $entity, $format, SerializerInterface $serializer, array $pushers, array $options = [])
    {
        $this->code = $code;
        $this->entity = $entity;
        $this->format = $format;
        $this->serializer = $serializer;
        $this->pushers = $pushers;
        $this->options = $options;
    }

    /**
     * @param PublishableInterface $entity
     */
    public function update(PublishableInterface $entity)
    {
        // TODO: Implement update() method.
    }

    /**
     * @param PublishableInterface $entity
     */
    public function remove(PublishableInterface $entity)
    {
        // TODO: Implement remove() method.
    }

    /**
     * @return bool
     */
    public function push()
    {
        // TODO: Implement push() method.
    }

    /**
     * @param $entity
     * @return bool
     */
    public function isSupported($entity)
    {
        return is_a($entity, $this->entity);
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @return SerializerInterface
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * @return PusherInterface[]
     */
    public function getPushers()
    {
        return $this->pushers;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }
}