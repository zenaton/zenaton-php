<?php

namespace Zenaton\Common\Traits;

use Zenaton\Common\Exceptions\ZenatonException;

trait GetAttributeTrait
{
    protected function getAttribute($data, $key, $mandatory = true, $default = null)
    {
        if (property_exists($data, $key)) {
            return $data->{$key};
        }

        if ($mandatory) {
            throw new ZenatonException('Missing "'.$key.'" attribute in '.json_encode($data));
        }

        return $default;
    }
}
