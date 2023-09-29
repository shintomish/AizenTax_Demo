<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\ImageUpload as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Kyslik\ColumnSortable\Sortable;         //追記 並び替えをcolumn-sortable使って

class ImageUpload extends Model
{
    use HasFactory, Notifiable;
    use Sortable;                   //追記

    // public $samples;
    // public function __construct($value)
    // {
    //     // 親コンストラクタを呼び出す
    //     parent::__construct();
    //     $this->sampleValue = $value;
    // }

    // 参照させたいSQLのテーブル名を指定
    protected $table = 'imageuploads';

    public $sortable = ['id','filename','filesize','created_at'];//追記(ソートに使うカラムを指定
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'filename',
        'filesize',
        'organization_id',
        'user_id',
        'customer_id',
        'created_at',
    ];
    protected $dates = [
        'created_at',
    ];
}
