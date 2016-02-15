<?php

namespace Sidus\PublishingBundle\Publishing;

use JMS\Serializer\SerializerInterface;
use Sidus\PublishingBundle\Entity\PublishableInterface;
use Sidus\PublishingBundle\Event\PublicationEvent;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use UnexpectedValueException;

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

    /** @var string */
    protected $queueDirectory;

    /**
     * @param string $code
     * @param string $entity
     * @param string $format
     * @param SerializerInterface $serializer
     * @param PusherInterface[] $pushers
     * @param array $options
     * @throws UnexpectedValueException
     */
    public function __construct(
        $code,
        $entity,
        $format,
        SerializerInterface $serializer,
        array $pushers,
        array $options = []
    ) {
        $this->code = $code;
        $this->entity = $entity;
        $this->format = $format;
        $this->serializer = $serializer;
        $this->pushers = $pushers;
        $this->options = $options;
        if (!isset($options['queue']['base_directory'])) {
            throw new UnexpectedValueException('The queue.directory option must be set');
        }
        $this->queueDirectory = $options['queue']['base_directory'];
    }

    /**
     * @param PublishableInterface $entity
     * @throws FileException
     * @throws AccessDeniedException
     */
    public function update(PublishableInterface $entity)
    {
        $event = new PublicationEvent($entity, PublicationEvent::UPDATE);
        $serialized = $this->getSerializer()->serialize($event, $this->getFormat());
        $f = $this->getFileName($event);
        if (false === file_put_contents($f, $serialized)) {
            throw new FileException("Unable to write to file {$f}");
        }
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

    /**
     * @param PublicationEvent $event
     * @return string
     * @throws AccessDeniedException
     */
    protected function getFileName(PublicationEvent $event)
    {
        return "{$this->getBaseDirectory()}/{$event->publicationUUID}.{$this->getFormat()}";
    }

    /**
     * @return string
     * @throws AccessDeniedException
     */
    protected function getBaseDirectory()
    {
        $directory = rtrim($this->queueDirectory, '/').'/'.$this->getCode();
        if (!@mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new AccessDeniedException("Unable to create base directory {$directory}");
        }
        return $directory;
    }
}