<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisciplineAction extends Model
{
    protected $fillable = ['discipline_id', 'action_text', 'action_by', 'action_date'];

    protected $casts = ['action_date' => 'datetime'];

    public function record()
    {
        return $this->belongsTo(DisciplineRecord::class, 'discipline_id');
    }

    public function actionBy()
    {
        return $this->belongsTo(User::class, 'action_by');
    }
}
