<?php

namespace App\Models;

use CodeIgniter\Model;

class Villages extends Model
{
    protected $table            = 'villages';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['id', 'district_id', 'name'];
}
