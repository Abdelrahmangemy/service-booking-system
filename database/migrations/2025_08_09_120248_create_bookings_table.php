<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingsTable extends Migration
{
    public function up()
    {
        Schema::create('bookings', function(Blueprint $table){
            $table->id();
            $table->foreignId('service_id')->constrained('services');
            $table->foreignId('provider_id')->constrained('users');
            $table->foreignId('customer_id')->constrained('users');
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->enum('status', ['pending','confirmed','cancelled','completed'])->default('pending');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['provider_id', 'start_time', 'end_time']);
        });
    }
    public function down()
    {
        Schema::dropIfExists('bookings');
    }
}
