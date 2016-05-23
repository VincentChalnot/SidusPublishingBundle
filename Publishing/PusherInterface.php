<?php

namespace Sidus\PublishingBundle\Publishing;

/**
 * Pusher services must implements this interface
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
interface PusherInterface
{
    /**
     * @param string $publicationUuid
     * @param mixed  $data
     */
    public function create($publicationUuid, $data);

    /**
     * @param string $publicationUuid
     * @param mixed  $data
     */
    public function update($publicationUuid, $data);

    /**
     * @param string $publicationUuid
     * @param mixed  $data
     */
    public function delete($publicationUuid, $data);
}
