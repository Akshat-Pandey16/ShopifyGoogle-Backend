<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoogleSpreadsheet extends Model
{
    use HasFactory;
    protected $table = 'spreadsheet_data';
    protected $fillable = ['user_id', 'spreadsheet_id', 'spreadsheet_name'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
