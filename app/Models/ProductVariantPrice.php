<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantPrice extends Model
{
    public function variant_one ()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_one');
    }

    public function variant_two ()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_two');
    }

    public function variant_three ()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_three');
    }
}
