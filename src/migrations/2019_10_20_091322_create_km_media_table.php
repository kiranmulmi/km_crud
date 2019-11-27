<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateKmMediaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('km_media', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('entity_id')->nullable();
            $table->string('entity_type')->nullable();
            $table->string('field_name')->nullable();
            $table->string('name')->nullable();
            $table->text('short_description')->nullable();
            $table->text('description')->nullable();
            $table->string('media_type')->nullable();
            $table->string('extension')->nullable();
            $table->text('path')->nullable();
            $table->integer('size')->nullable();
            $table->integer('status')->nullable();
            $table->text('uri')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('km_media');
    }
}
