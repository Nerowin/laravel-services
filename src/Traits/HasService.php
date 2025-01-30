<?php

namespace Nerow\Services\Traits;

use Nerow\Services\Service;

trait HasService
{
    public function getService(): string
    {
        return '\\App\\Services\\' . class_basename(get_class($this)) . 'Service';
    }

    public function service(): Service
    {
        return new $this->getService();
    }

    public function getFillable()
    {
        return array_keys($this->getService()::rules());
    }
}
