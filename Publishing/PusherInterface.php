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
     * @param string $publicationId
     * @param mixed $data
     * @return bool
     */
    public function put($publicationId, $data);

    /**
     * @param string $publicationId
     * @return bool
     */
    public function delete($publicationId);
}
