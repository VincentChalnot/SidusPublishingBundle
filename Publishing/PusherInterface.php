<?php

namespace Sidus\PublishingBundle\Publishing;


interface PusherInterface
{
    /**
     * @param $publicationUuid
     * @param mixed $data
     */
    public function create($publicationUuid, $data);

    /**
     * @param string $publicationUuid
     * @param mixed $data
     */
    public function update($publicationUuid, $data);

    /**
     * @param string $publicationUuid
     * @param mixed $data
     */
    public function delete($publicationUuid, $data);
}
