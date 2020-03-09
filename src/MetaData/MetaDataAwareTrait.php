<?php

namespace Bugsnag\MetaData;

trait MetaDataAwareTrait
{
    private $metaData = [];

    /**
     * Get meta data.
     *
     * @return array
     */
    public function getMetaData()
    {
        return $this->metaData;
    }

    /**
     * Add meta data.
     *
     * @param array $metaData
     */
    public function addMetaData(array $metaData)
    {
        $this->metaData = array_merge_recursive($this->metaData, $metaData);
    }
}
