<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = ['name','description','date_time','duration','cycle','user_id'];
    protected $casts = ['date_time' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function generateOccurrences($startDate, $endDate)
    {
        $occurrences = [];
        $current = $this->date_time->copy();
        $eventEnd = $endDate->copy();

        while ($current <= $eventEnd) {
            if ($current >= $startDate && $current <= $endDate) {
                $occurrences[] = [
                    'start' => $current->copy(),
                    'end' => $current->copy()->addMinutes($this->duration),
                ];
            }

            switch ($this->cycle) {
                case 'daily':
                    $current->addDay();
                    break;
                case 'weekly':
                    $current->addWeek();
                    break;
                case 'monthly':
                    $current->addMonth();
                    break;
                case 'yearly':
                    $current->addYear();
                    break;
                case 'once':
                default:
                    $current = $eventEnd->copy()->addDay(); // break loop
                    break;
            }
        }

        return $occurrences;
    }

    public function overlaps($otherStart, $otherEnd)
    {
        $eventStart = $this->date_time;
        $eventEnd = $this->date_time->copy()->addMinutes($this->duration);

        return $eventStart < $otherEnd && $eventEnd > $otherStart;
    }
}
