<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateKmCategoriesRelationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('km_categories_relations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('entity_id')->nullable();
            $table->string('entity_type')->nullable();
            $table->string('field_name')->nullable();
            $table->integer('category_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('km_categories_relations');
    }
}
