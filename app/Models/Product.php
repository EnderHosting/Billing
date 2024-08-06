<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;
    protected $table = 'products';
    protected $fillable = [
        'name',
        'description',
        'category_id',
        'image',
        'extension_id',
        'stock',
        'stock_enabled',
        'allow_quantity',
        'order',
        'limit',
        'hidden',
        'upgrade_configurable_options',
    ];

    // Relación con la categoría
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Relación con la extensión
    public function extension()
    {
        return $this->belongsTo(Extension::class, 'extension_id');
    }

    // Relación con las configuraciones del producto
    public function settings()
    {
        return $this->hasMany(ProductSetting::class, 'product_id');
    }

    // Relación con los precios del producto
    public function prices()
    {
        return $this->hasOne(ProductPrice::class);
    }

    // Relación con las actualizaciones del producto
    public function upgrades()
    {
        return $this->hasMany(ProductUpgrade::class, 'product_id');
    }

    // Método para obtener el precio del producto
    public function price($type = null)
    {
        $prices = $this->prices;

        if ($prices->type == 'one-time') {
            if ($type == 'setup') {
                return $prices->monthly_setup;
            } else {
                return $prices->monthly;
            }
        } else if ($prices->type == 'free') {
            return 0;
        } else {
            if ($type == 'setup') {
                return $prices->{$prices->type . '_setup'};
            } else if ($type) {
                return $prices->{$type};
            } else {
                // Aquí agregamos la opción para semanal
                return $prices->weekly ?? $prices->monthly ?? $prices->quarterly ?? $prices->semi_annually ?? $prices->annually ?? $prices->biennially ?? $prices->triennially;
            }
        }
    }

    // Método para obtener los grupos configurables
    public function configurableGroups()
    {
        // Check all groups products array
        $groups = ConfigurableGroup::all();
        $configurableGroups = [];
        foreach ($groups as $group) {
            $products = $group->products;
            if (in_array($this->id, $products)) {
                $configurableGroups[] = $group;
            }
        }
        return $configurableGroups;
    }

    // Nuevo método para verificar si un producto es renovable
    public function isRenewable($billingCycle)
    {
        // Retorna false si el ciclo de facturación es semanal
        return $billingCycle !== 'weekly';
    }
}