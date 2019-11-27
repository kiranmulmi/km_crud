<?php


namespace KM\KMCrud\Models;


use Illuminate\Database\Eloquent\Model;

class GenericRelationship extends Model
{
    protected $table = "generic_relationships";
    protected $fillable = [
        'entity_type',
        'meta_key',
        'from_id',
        'to_id',
    ];

    public $timestamps = false;
}
