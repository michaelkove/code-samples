<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $table = 'invoices';

    protected $fillable = [
        'pool_id',
        'rate_id',
        'tax',
        'sub_total',
        'total',
        'admin_override',
        'paid',
        'paid_at',
        'notes',
        'name',
        'description',
        'status',
    ];
	
	
    public function pool(){
        return $this->belongsTo(Pool::class, 'pool_id', 'id');
    }

    public function rate(){
        return $this->belongsTo(Rate::class, 'rate_id', 'id');
    }

    public function payment(){
        return $this->hasOne(Payment::class, 'invoice_id', 'id');

    }

    public function discounts(){
        return $this->hasMany(InvoiceDiscount::class, 'invoice_id', 'id');
    }
    //
}
