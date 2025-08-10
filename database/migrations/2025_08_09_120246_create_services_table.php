<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServicesTable extends Migration
{
    public function up()
    {
        Schema::create('services', function(Blueprint $table){
            $table->id();
            $table->foreignId('provider_id')->constrained('users');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->integer('duration_minutes')->default(60);
            $table->bigInteger('price_cents')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });
    }
    public function down()
    {
        Schema::dropIfExists('services');
    }
}
