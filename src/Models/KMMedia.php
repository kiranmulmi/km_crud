<?php


namespace KM\KMCrud\Models;


use Illuminate\Database\Eloquent\Model;

class KMMedia extends Model
{
    protected $table = 'km_media';
    protected $fillable = [
        'entity_id',
        'entity_type',
        'field_name',
        'name',
        'short_description',
        'description',
        'media_type',
        'extension',
        'path',
        'size',
        'status',
        'uri',
    ];
}
