<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Download extends Model
{
    use Concerns\HasSeo;

    protected $table = 'downloads_extras';

    public $timestamps = false;

    protected $fillable = [
        'titulo',
        'slug',
        'descricao',
        'categoria',
        'versao',
        'tamanho',
        'publico',
        'contador',
        'arquivo_path',
        'data_atualizacao',
        // Digital Products
        'preco',
        'is_paid',
        'requires_login',
        'disponivel_revenda',
    ];

    protected $casts = [
        'publico' => 'boolean',
        'contador' => 'integer',
        'data_atualizacao' => 'datetime',
        'preco' => 'decimal:2',
        'is_paid' => 'boolean',
        'requires_login' => 'boolean',
        'disponivel_revenda' => 'boolean',
    ];

    protected $appends = ['imagem_url']; // Opcional, para serialization

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->data_cadastro = now();
            $model->data_atualizacao = now();

            // Generate Slug
            if (empty($model->slug)) {
                $slug = \Illuminate\Support\Str::slug($model->titulo);
                $count = static::whereRaw("slug RLIKE '^{$slug}(-[0-9]+)?$'")->count();
                $model->slug = $count ? "{$slug}-{$count}" : $slug;
            }

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

    public function versions()
    {
        return $this->hasMany(DownloadVersion::class, 'download_id')->orderBy('data_lancamento', 'desc');
    }

    public function logs()
    {
        return $this->hasMany(DownloadLog::class, 'download_id');
    }

    public function software()
    {
        return $this->hasOne(Software::class, 'id_download_repo');
    }

    public function getImagemUrlAttribute()
    {
        // Se estiver vinculado a um software oficial, tenta pegar a imagem dele
        if ($this->software && $this->software->imagem) {
            $path = $this->software->imagem;
            if (filter_var($path, FILTER_VALIDATE_URL))
                return $path;
            if (str_starts_with($path, 'img/produtos/'))
                return asset('storage/' . $path);
            if (str_starts_with($path, 'img/'))
                return asset($path);
            return asset('storage/' . $path);
        }
        return null;
    }
}
