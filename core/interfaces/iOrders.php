<?php

namespace Interfaces;

interface iOrders
{
    /**
     * @param Array $attributes
     * @return void
     */
    public function setAttributes(Array $attributes) : void;


    /**
     * @return Array
     */
    public function retrieveOne() : Array;

    /**
     * @param Array $filters
     * @return Array
     */
    public static function retrieveMany(Array $filters = NULL) : Array;

    /**
     * @param Array $data
     * @return Array
     */
    public function update(Array $data) : Array;
}