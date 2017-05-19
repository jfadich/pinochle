<?php

namespace jfadich\Pinochle\Contracts;


interface Store
{
    public function has($key);

    public function get($key);

    public function set($key, $value);
}