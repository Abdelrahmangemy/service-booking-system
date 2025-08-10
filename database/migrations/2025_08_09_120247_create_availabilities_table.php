<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAvailabilitiesTable extends Migration
{
    public function up()
    {
        Schema::create('availabilities', function(Blueprint $table){
            $table->id();
            $table->foreignId('provider_id')->constrained('users');
            $table->enum('type', ['recurring','custom']);
            $table->tinyInteger('day_of_week')->nullable(); // 0=Sunday..6=Saturday
            $table->date('date')->nullable(); // for custom
            $table->time('start_time');
            $table->time('end_time');
            $table->string('timezone')->default('UTC');
            $table->timestamps();
        });
    }
    public function down()
    {
        Schema::dropIfExists('availabilities');
    }
}
