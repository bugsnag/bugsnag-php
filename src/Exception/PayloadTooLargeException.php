<?php

namespace Bugsnag\Exception;

class PayloadTooLargeException extends \RuntimeException {

    private $payload;

    public function __construct(array $payload) {
        parent::__construct('Payload too large', 0, null);

        $this->payload = $payload;
    }

    /**
     * @return array
     */
    public function getPayload() {
        return $this->payload;
    }


}