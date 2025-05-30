<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    //

    public function rooms() {
        return $this->hasMany(Room::class);
    }

    public function reservations() {
        return $this->hasManyThrough(Reservation::class, Room::class);
    }

    public function reviews() {
        return $this->hasMany(Review::class);
    }
}

