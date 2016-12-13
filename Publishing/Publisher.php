<?php

namespace Sidus\PublishingBundle\Publishing;

use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Sidus\PublishingBundle\Entity\PublishableInterface;
use Sidus\PublishingBundle\Event\PublicationEventInterface;
use Sidus\PublishingBundle\Exception\PublicationException;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use UnexpectedValueException;

/**
 * This class handle the publication of entities through configured pushers
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class Publisher implements PublisherInterface
{
    /** @var string */
    protected $code;

    /** @var string */
    protected $entityName;

    /** @var string */
    protected $format;

    /** @var SerializerInterface */
    protected $serializer;

    /** @var LoggerInterface */
    protected $logger;

    /** @var PusherInterface[] */
    protected $pushers;

    /** @var array */
    protected $options;

    /** @var string */
    protected $queueDirectory;

    /** @var string */
    protected $publicationEventClass;

    /** @var bool */
    protected $enabled;

    /**
     * @param string              $code
     * @param string              $entityName
     * @param string              $format
     * @param SerializerInterface $serializer
     * @param LoggerInterface     $logger
     * @param PusherInterface[]   $pushers
     * @param array               $options
     *
     * @throws UnexpectedValueException
     */
    public function __construct(
        $code,
        $entityName,
        $format,
        SerializerInterface $serializer,
        LoggerInterface $logger,
        array $pushers,
        array $options = []
    ) {
        $this->code = $code;
        $this->entityName = $entityName;
        $this->format = $format;
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->pushers = $pushers;
        $this->options = $options;
        if (!isset($options['queue']['base_directory'])) {
            throw new UnexpectedValueException('The queue.directory option must be set');
        }
        $this->queueDirectory = $options['queue']['base_directory'];
        if (!isset($options['publication_event_class'])) {
            throw new UnexpectedValueException('The publication_event_class option must be set');
        }
        $this->publicationEventClass = $options['publication_event_class'];
        $this->enabled = (bool) $this->options['enabled'];
    }

    /**
     * @param PublishableInterface $entity
     *
     * @throws FileException
     * @throws AccessDeniedException
     */
    public function create(PublishableInterface $entity)
    {
        $this->handlePublication($entity, PublicationEventInterface::CREATE);
    }

    /**
     * @param PublishableInterface $entity
     *
     * @throws FileException
     * @throws AccessDeniedException
     */
    public function update(PublishableInterface $entity)
    {
        $this->handlePublication($entity, PublicationEventInterface::UPDATE);
    }

    /**
     * @param PublishableInterface $entity
     *
     * @throws AccessDeniedException
     * @throws FileException
     */
    public function delete(PublishableInterface $entity)
    {
        $this->handlePublication($entity, PublicationEventInterface::DELETE);
    }

    /**
     * @param bool $crashOnError
     *
     * @throws AccessDeniedException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws PublicationException
     *
     * @return bool
     */
    public function publish($crashOnError = false)
    {
        if (!$this->enabled) {
            return false;
        }

        foreach ($this->getEventTypes() as $eventType) {
            $finder = new Finder();
            /** @var SplFileInfo[] $files */
            $files = $finder
                ->in($this->getBaseDirectory($eventType))
                ->name('*.'.$this->format)
                ->sortByModifiedTime()
                ->files();

            foreach ($files as $file) {
                $this->publishFile($file, $eventType, $crashOnError);
            }
        }

        return true;
    }

    /**
     * @param mixed $entity
     *
     * @return bool
     */
    public function isSupported($entity)
    {
        return is_a($entity, $this->getEntityName());
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
    public function getEntityName()
    {
        return $this->entityName;
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
     * @param PublishableInterface $entity
     * @param string               $eventName
     *
     * @throws AccessDeniedException
     *
     * @throws FileException
     */
    protected function handlePublication(PublishableInterface $entity, $eventName)
    {
        if (!$this->enabled) {
            return;
        }
        $class = $this->publicationEventClass;
        $event = new $class($entity, $eventName);

        $serialized = $this->getSerializer()->serialize($event, $this->getFormat());
        $f = $this->getFileName($eventName, $entity->getPublicationUuid());
        if (false === file_put_contents($f, $serialized)) {
            throw new FileException("Unable to write to file {$f}");
        }
    }

    /**
     * @param string $eventName
     * @param string $publicationUuid
     *
     * @throws AccessDeniedException
     *
     * @return string
     */
    protected function getFileName($eventName, $publicationUuid)
    {
        return "{$this->getBaseDirectory($eventName)}/{$publicationUuid}.{$this->getFormat()}";
    }

    /**
     * @param string $eventType
     *
     * @throws AccessDeniedException
     *
     * @return string
     */
    protected function getBaseDirectory($eventType = null)
    {
        $directory = rtrim($this->queueDirectory, '/').'/'.$this->getCode();
        if ($eventType) {
            $directory .= '/'.$eventType;
        }
        if (!@mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new AccessDeniedException("Unable to create base directory {$directory}");
        }

        return $directory;
    }

    /**
     * @param string $eventType
     *
     * @throws AccessDeniedException
     *
     * @return string
     */
    protected function getErrorDirectory($eventType = null)
    {
        $directory = $this->getBaseDirectory().'/error';
        if ($eventType) {
            $directory .= '/'.$eventType;
        }
        if (!@mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new AccessDeniedException("Unable to create error directory {$directory}");
        }

        return $directory;
    }

    /**
     * @return array
     */
    protected function getEventTypes()
    {
        return [
            PublicationEventInterface::CREATE,
            PublicationEventInterface::UPDATE,
            PublicationEventInterface::DELETE,
        ];
    }

    /**
     * @param SplFileInfo $file
     * @param string      $eventType
     * @param bool        $crashOnError
     *
     * @throws PublicationException
     * @throws AccessDeniedException
     * @throws \RuntimeException
     */
    protected function publishFile(SplFileInfo $file, $eventType, $crashOnError = false)
    {
        $publicationUuid = substr($file->getBasename(), 0, -strlen($this->getFormat()) - 1);
        $errorFilePath = "{$this->getErrorDirectory($eventType)}/{$publicationUuid}.{$this->getFormat()}";

        $hasError = false;
        foreach ($this->getPushers() as $pusher) {
            try {
                $pusher->$eventType($publicationUuid, $file->getContents());
            } catch (PublicationException $e) {
                $r = $e->getResponse();
                if ($r && $r->getContent()) {
                    file_put_contents($errorFilePath, $r->getContent());
                    $e->addMessage("Check {$errorFilePath} for more information");

                    if ($crashOnError) {
                        throw $e;
                    } else {
                        $this->logger->error($e->getMessage(), [
                            'eventType' => $eventType,
                            'publicationUUid' => $publicationUuid,
                        ]);
                        $hasError = true;
                    }
                }
            }
        }

        if (!$hasError) {
            unlink($file->getRealPath());
            if (file_exists($errorFilePath)) {
                unlink($errorFilePath);
            }
        }
    }
}
