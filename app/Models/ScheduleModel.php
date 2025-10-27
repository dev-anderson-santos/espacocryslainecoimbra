<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ScheduleModel extends Model
{
    use SoftDeletes;

    protected $table = 'schedules';
    protected $primaryKey = 'id';
    
    // Especifica que a chave primária é string (UUID)
    protected $keyType = 'string';
    
    // Desabilita o auto-increment já que usaremos UUID
    public $incrementing = false;
    
    // Laravel 7 usa $dates ao invés de $casts para datas
    public $dates = ['deleted_at', 'finalizado_em'];
    
    protected $fillable = [
        'id',
        'user_id', 
        'room_id', 
        'created_by',
        'hour_id', 
        'date',
        'status',
        'tipo',
        'faturado',
        'finalizado_em',
        'data_nao_faturada_id',
        'valor'
    ];

    /**
     * Gera automaticamente um UUID quando o modelo é criado
     * Funciona perfeitamente no Laravel 7
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Str::uuid()->toString();
            }
        });
    }

    /**
     * Para Laravel 7, use $casts apenas para outros tipos
     * As datas já estão definidas em $dates
     */
    protected $casts = [
        'id' => 'string'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function room()
    {
        return $this->belongsTo(RoomModel::class, 'room_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function hour()
    {
        return $this->belongsTo(HourModel::class, 'hour_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
