<?php

namespace Services;

class QengineService
{
    const URL = 'https://q-engine.melhorenvio.com';

    public function calculate($body)
    {
        return (new RequestService())->request(
            self::URL . '/api/v1/calculate',
            'POST',
            $body,
            true
        );
    }
}