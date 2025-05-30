<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservationsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('Reservation', function (Blueprint $table) {
            $table->id()->autoIncrement(); // Primary key: id (auto-increment)
            $table->string('offerId')->nullable();
            $table->string('status')->nullable();
            $table->string('Hotel_name')->nullable();
            $table->string('Room_type')->nullable();
            $table->decimal('Totel_price', 10, 2)->nullable(); // Adjust precision as needed
            $table->string('currency', 3)->nullable();
            $table->date('Check_in_date')->nullable();
            $table->date('Check_out_date')->nullable();
            $table->string('customer_id');
            $table->string('multi_customer_id')->nullable();
            $table->timestamps(); // includes created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Reservation');
    }
}
