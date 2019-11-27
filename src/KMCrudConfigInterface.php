<?php


namespace KM\KMCrud;


interface KMCrudConfigInterface
{
    public static function config();
    public static function saveCallback($entity, $saveType, &$state);
}
