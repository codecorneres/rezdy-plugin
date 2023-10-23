<?php

namespace CC_RezdyAPI;

class Settings
{

    const COLUMNS = [];

    public static function setupDb( float $db_version=0 ){}

    public static function prepareData( array $args ) : array {}

    public static function insert( array $args ) : int {}

    public static function firstRow() {}

    public static function insertBulk( array $items ) : int {}

    public static function push( string $message ) : int {}

    public static function update( int $id, array $args ) : bool {}

    public static function delete( array $ids ) : int {}

    public static function deleteAll() : int {}

}

