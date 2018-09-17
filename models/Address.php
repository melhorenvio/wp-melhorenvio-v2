<?php

namespace Models;

class Address {

    private $name;
    private $number;

    public function __construct() {
        
    }

    public function setAttribute($attribute, $value) {
        $this->{$attribute} = $value;
    }
}