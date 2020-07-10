<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{

    /**
     * 模型的主键字段
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * BaseModel constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }
}
