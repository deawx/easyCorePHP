<?php

namespace App\Models;

use Core\Model;

class Todo extends Model {
    protected $table = 'todos';
    protected $fillable = ['title', 'description', 'completed'];
}