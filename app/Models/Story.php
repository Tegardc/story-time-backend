<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Story extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'title',
        'content',
        'cover',
        'category_id',
        'user_id'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function story_images()
    {
        return $this->hasMany(StoryImage::class);
    }
    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class, 'story_id', 'id');
    }
    public function images()
    {
        return $this->hasMany(StoryImage::class);
    }
    protected $dates = ['deleted_at'];
}
