<?php

namespace App\Traits;

use App\Models\Instansi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToInstansi
{
    protected static function bootBelongsToInstansi()
    {
        static::addGlobalScope('instansi', function (Builder $builder) {
            if (auth()->check() && auth()->user()->role !== 'superadmin') {
                $builder->where($builder->getQuery()->from . '.instansi_id', auth()->user()->instansi_id);
            }
        });

        static::creating(function ($model) {
            if (auth()->check() && auth()->user()->role !== 'superadmin' && empty($model->instansi_id)) {
                $model->instansi_id = auth()->user()->instansi_id;
            }
        });
    }

    public function instansi(): BelongsTo
    {
        return $this->belongsTo(Instansi::class);
    }
}
