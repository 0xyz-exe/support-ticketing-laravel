<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = ['user_id', 'assignee_id', 'subject', 'description', 'status', 'priority'];

    public function requester() { return $this->belongsTo(User::class, 'user_id'); }
    public function assignee() { return $this->belongsTo(User::class, 'assignee_id'); }
    public function comments() { return $this->hasMany(TicketComment::class); }
}
