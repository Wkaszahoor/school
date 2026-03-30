<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProficiencyTestSection extends Model
{
    protected $fillable = [
        'test_id', 'name', 'instructions', 'passage', 'order', 'marks',
    ];

    public function test()
    {
        return $this->belongsTo(ProficiencyTest::class, 'test_id');
    }

    public function questions()
    {
        return $this->hasMany(ProficiencyTestQuestion::class, 'section_id')->orderBy('order');
    }
}
