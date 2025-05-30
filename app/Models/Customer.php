<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    // Table name (optional if following Laravel naming conventions)
    protected $table = 'customers';
    protected $primaryKey = 'id';

    // Mass assignable attributes
    protected $fillable = [
        'id',
        'full_name',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'nationality',
        'passport_number',
        'address',
        'city',
        'country',
        'postal_code',
    ];

    public function user()
    {
        return $this->hasOne(User::class);
    }
}
