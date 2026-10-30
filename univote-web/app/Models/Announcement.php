<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'content',
        'type',
        'organization_id',
        'admin_id',
        'target_type',
        'course_id',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
