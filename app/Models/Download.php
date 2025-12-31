<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Download extends Model
{
    protected $table = 'downloads_extras';

    public $timestamps = false;

    protected $fillable = [
        'titulo',
        'descricao',
        'categoria',
        'versao',
        'tamanho',
        'publico',
        'contador',
        'arquivo_path',
        'data_atualizacao',
    ];

    protected $casts = [
        'publico' => 'boolean',
        'contador' => 'integer',
        'data_atualizacao' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->data_cadastro = now();
            $model->data_atualizacao = now();
            self::calculateSize($model);
        });

        static::updating(function ($model) {
            $model->data_atualizacao = now();
            self::calculateSize($model);
        });
    }

    protected static function calculateSize($model)
    {
        if (empty($model->tamanho) && !empty($model->arquivo_path)) {
            try {
                $bytes = \Illuminate\Support\Facades\Storage::disk('public')->size($model->arquivo_path);
                if ($bytes >= 1073741824)
                    $model->tamanho = number_format($bytes / 1073741824, 2) . ' GB';
                elseif ($bytes >= 1048576)
                    $model->tamanho = number_format($bytes / 1048576, 2) . ' MB';
                elseif ($bytes >= 1024)
                    $model->tamanho = number_format($bytes / 1024, 2) . ' KB';
                else
                    $model->tamanho = $bytes . ' bytes';
            } catch (\Exception $e) {
                // Ignore if file not found (e.g. testing)
            }
        }
    }
}
