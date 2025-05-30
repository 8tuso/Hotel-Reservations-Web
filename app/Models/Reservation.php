<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $table = 'Reservation';
    protected $primaryKey = 'id'; 
    protected $fillable = [
        'id',
        'offerId',
        'status',
        'Hotel_name',
        'Room_type',
        'img',
        'Totel_price',
        'currency',
        'Check_in_date',
        'Check_out_date',
        'Customer_id',
        'multi_customer_id',
        'created_at'
    ];
}
