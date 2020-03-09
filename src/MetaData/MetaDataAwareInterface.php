<?php

namespace Bugsnag\MetaData;

interface MetaDataAwareInterface
{
    /**
     * Get meta data.
     *
     * @return array
     */
    public function getMetaData();
}
