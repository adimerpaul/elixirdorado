<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SixpackComponente extends Model
{
    protected $table = 'sixpack_componentes';

    protected $fillable = ['sixpack_id', 'producto_id', 'cantidad'];

    protected $casts = ['cantidad' => 'integer'];

    public function sixpack(): BelongsTo
    {
        return $this->belongsTo(Sixpack::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
