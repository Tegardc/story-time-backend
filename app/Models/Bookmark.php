<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PDO;

class Bookmark extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'story_id'];

    // public function bookmarks()
    // {
    //     return $this->hasMany(Bookmark::class);
    // }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function story()
    {
        return $this->belongsTo(Story::class, 'story_id', 'id');
    }
}
