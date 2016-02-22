<?php

namespace Sidus\PublishingBundle\Publishing;


interface PusherInterface
{
    /**
     * @param mixed $data
     * @return bool
     */
    public function post($data);

    /**
     * @param string $publicationUuid
     * @param mixed $data
     * @return bool
     */
    public function put($publicationUuid, $data);

    /**
     * @param string $publicationUuid
     * @return bool
     */
    public function delete($publicationUuid);
}
