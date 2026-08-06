<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Support\Number;
use App\Models\Workflow\Orders;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Quotes;
use App\Models\Workflow\QuoteLines;
use App\Models\Products\Products;
use App\Models\Workflow\Invoices;
use App\Models\Products\StockMove;
use App\Models\Workflow\Deliverys;
use App\Models\Companies\Companies;
use App\Models\Workflow\Opportunities;
use Illuminate\Database\Eloquent\Model;
use App\Models\Purchases\PurchaseReceipt;
use App\Models\Quality\QualityNonConformity;
use App\Services\Files\FileKindResolver;
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
        'kind',
        'extension',
        'disk',
        'path',
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
        'as_photo' => 'boolean',
        'size' => 'integer',
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
     * Whether the file predates the private storage migration and therefore
     * still lives under public/.
     */
    public function getIsLegacyAttribute(): bool
    {
        return blank($this->path);
    }

    /**
     * Authorized URL streaming the file inline, for the viewers.
     */
    public function getViewUrlAttribute(): string
    {
        return route('files.raw', ['file' => $this->id]);
    }

    /**
     * Authorized URL forcing a download.
     */
    public function getDownloadUrlAttribute(): string
    {
        return route('files.download', ['file' => $this->id]);
    }

    /**
     * Whether the front-end knows how to render this file inline.
     */
    public function getIsViewableAttribute(): bool
    {
        return FileKindResolver::isViewable($this->kind ?? FileKindResolver::KIND_OTHER);
    }

    /**
     * Font Awesome icon matching the file kind.
     */
    public function getIconAttribute(): string
    {
        return FileKindResolver::icon($this->kind ?? FileKindResolver::KIND_OTHER);
    }

    /**
     * Restrict the query to a given functional kind.
     */
    public function scopeOfKind($query, string|array $kind)
    {
        return $query->whereIn('kind', (array) $kind);
    }

    /**
     * Define a polymorphic many-to-many relationship with the Companies model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function companies()
    {
        return $this->morphedByMany(Companies::class, 'fileable')->withPivot(['role', 'is_primary']);
    }

    /**
     * Define a polymorphic many-to-many relationship with the Opportunities model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function opportunities()
    {
        return $this->morphedByMany(Opportunities::class, 'fileable')->withPivot(['role', 'is_primary']);
    }

    /**
     * Define a polymorphic many-to-many relationship with the Quotes model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function quotes()
    {
        return $this->morphedByMany(Quotes::class, 'fileable')->withPivot(['role', 'is_primary']);
    }

    /**
     * Polymorphic many-to-many with QuoteLines (per-line attachments).
     */
    public function quoteLines()
    {
        return $this->morphedByMany(QuoteLines::class, 'fileable')->withPivot(['role', 'is_primary']);
    }

    /**
     * Define a polymorphic many-to-many relationship with the Orders model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function orders()
    {
        return $this->morphedByMany(Orders::class, 'fileable')->withPivot(['role', 'is_primary']);
    }

    /**
     * Polymorphic many-to-many with OrderLines (per-line attachments).
     */
    public function orderLines()
    {
        return $this->morphedByMany(OrderLines::class, 'fileable')->withPivot(['role', 'is_primary']);
    }

    /**
     * Define a polymorphic many-to-many relationship with the Deliverys model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function deliverys()
    {
        return $this->morphedByMany(Deliverys::class, 'fileable')->withPivot(['role', 'is_primary']);
    }

    /**
     * Define a polymorphic many-to-many relationship with the Invoices model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function invoices()
    {
        return $this->morphedByMany(Invoices::class, 'fileable')->withPivot(['role', 'is_primary']);
    }

    /**
     * Define a polymorphic many-to-many relationship with the Products model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function products()
    {
        return $this->morphedByMany(Products::class, 'fileable')->withPivot(['role', 'is_primary']);
    }

    /**
     * Define a polymorphic many-to-many relationship with the PurchaseReceipt model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function purchaseReceipt()
    {
        return $this->morphedByMany(PurchaseReceipt::class, 'fileable')->withPivot(['role', 'is_primary']);
    }

    /**
     * Define a polymorphic many-to-many relationship with the StockMove model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function stockMove()
    {
        return $this->morphedByMany(StockMove::class, 'fileable')->withPivot(['role', 'is_primary']);
    }

    /**
     * Define a polymorphic many-to-many relationship with the QualityNonConformity model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function qualityNonConformity()
    {
        return $this->morphedByMany(QualityNonConformity::class, 'fileable')->withPivot(['role', 'is_primary']);
    }

    /**
     * Define a belongs-to relationship with the User model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'user_id');
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
