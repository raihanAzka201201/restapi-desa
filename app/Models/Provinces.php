<?php

namespace App\Models;

use CodeIgniter\Model;

class Provinces extends Model
{
    protected $table            = 'provinces';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['name'];
}
