<?php

namespace App\Models;

use CodeIgniter\Model;

class Regencies extends Model
{
    protected $table            = 'regencies';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['province_id', 'name'];
}
