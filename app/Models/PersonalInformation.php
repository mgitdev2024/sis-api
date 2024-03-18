<?php

namespace App\Models;

use App\Models\Dashboard\Memoranda;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalInformation extends Model
{
    use HasFactory;
    protected $table = 'personal_informations';

    protected $fillable = [
        'employee_id',
        'prefix',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'alias',
        'gender',
        'birth_date',
        'age',
        'marital_status',
        'personal_email',
        'company_email',
        'status',
    ];

    public function credential()
    {
        return $this->belongsTo(Credential::class, 'employee_id', 'employee_id');
    }
    /*   public function memoranda()
      {
          return $this->hasMany(Memoranda::class);
      } */
}
