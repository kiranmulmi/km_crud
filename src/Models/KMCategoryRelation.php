<?php


namespace KM\KMCrud\Models;


use Illuminate\Database\Eloquent\Model;

class KMCategoryRelation extends Model
{
    protected $table = 'km_categories_relations';
    protected $fillable = ['entity_id', 'entity_type', 'field_name', 'category_id'];
    public $timestamps = false;
}
