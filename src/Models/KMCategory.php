<?php


namespace KM\KMCrud\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KMCategory extends Model
{
    use SoftDeletes;
    protected $table = 'km_categories';
    protected $fillable = [
        'name',
        'short_description',
        'description',
        'parent_id',
        'status',
        'category_type',
        'creator_id',
        'editor_id',
        'weight',
    ];

    public function parentCategory()
    {
        return $this->hasOne('KM\KMCrud\Models\KMCategory', 'id', 'parent_id');
    }
}
