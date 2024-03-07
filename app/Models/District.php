<?php

namespace App\Models;

use CodeIgniter\Model;

class District extends Model
{
    protected $table            = 'districts';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['regency_id', 'name'];
}
