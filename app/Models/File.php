<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Support\Number;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Models\Products\Products;
use App\Models\Workflow\Invoices;
use App\Models\Products\StockMove;
use App\Models\Workflow\Deliverys;
use App\Models\Companies\Companies;
use App\Models\Workflow\Opportunities;
use Illuminate\Database\Eloquent\Model;
use App\Models\Purchases\PurchaseReceipt;
use App\Models\Quality\QualityNonConformity;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class File extends Model
{
    use HasFactory;

    // Fillable attributes for mass assignment
    protected $fillable = [
        'user_id',
        'name',
        'original_file_name',
        'type',
        'size',
        'comment',
        'hashtags',
        'as_photo',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'hashtags' => 'array',
    ];

    /**
     * Get the file size in kilobytes, rounded to 2 decimal places.
     *
     * @return string The formatted file size.
     */
    public function getFormattedSizeAttribute()
    {
        return Number::fileSize($this->size);
    }

    /**
     * Define a polymorphic many-to-many relationship with the Companies model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function companies()
    {
        return $this->morphedByMany(Companies::class, 'fileable');
    }

    /**
     * Define a polymorphic many-to-many relationship with the Opportunities model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function opportunities()
    {
        return $this->morphedByMany(Opportunities::class, 'fileable');
    }

    /**
     * Define a polymorphic many-to-many relationship with the Quotes model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function quotes()
    {
        return $this->morphedByMany(Quotes::class, 'fileable');
    }

    /**
     * Define a polymorphic many-to-many relationship with the Orders model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function orders()
    {
        return $this->morphedByMany(Orders::class, 'fileable');
    }

    /**
     * Define a polymorphic many-to-many relationship with the Deliverys model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function deliverys()
    {
        return $this->morphedByMany(Deliverys::class, 'fileable');
    }

    /**
     * Define a polymorphic many-to-many relationship with the Invoices model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function invoices()
    {
        return $this->morphedByMany(Invoices::class, 'fileable');
    }

    /**
     * Define a polymorphic many-to-many relationship with the Products model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function products()
    {
        return $this->morphedByMany(Products::class, 'fileable');
    }

    /**
     * Define a polymorphic many-to-many relationship with the PurchaseReceipt model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function purchaseReceipt()
    {
        return $this->morphedByMany(PurchaseReceipt::class, 'fileable');
    }

    /**
     * Define a polymorphic many-to-many relationship with the StockMove model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function stockMove()
    {
        return $this->morphedByMany(StockMove::class, 'fileable');
    }

    /**
     * Define a polymorphic many-to-many relationship with the QualityNonConformity model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function qualityNonConformity()
    {
        return $this->morphedByMany(QualityNonConformity::class, 'fileable');
    }

    /**
     * Define a belongs-to relationship with the User model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    /**
     * Get the formatted creation date of the line.
     *
     * This accessor method returns the creation date of line
     * formatted as 'day month year' (e.g., '01 January 2023').
     *
     * @return string The formatted creation date.
     */
    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }

}
