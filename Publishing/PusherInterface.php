<?php

namespace Sidus\PublishingBundle\Publishing;


interface PusherInterface
{
    /**
     * @param mixed $data
     * @return bool
     */
    public function push($data);
}
