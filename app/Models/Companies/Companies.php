<?php

namespace App\Models\Companies;

use App\Models\File;
use App\Models\User;
use App\Models\Workflow\Leads;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Models\Products\Products;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\Deliverys;
use App\Models\Purchases\Purchases;
use Illuminate\Database\Eloquent\Model;
use App\Models\Companies\SupplierRating;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Quality\QualityNonConformity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Companies extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected static function booted(): void
    {
        static::creating(function (self $company) {
            if (empty($company->uuid)) {
                $company->uuid = Str::uuid()->toString();
            }
            if (empty($company->code)) {
                $company->code = strtoupper(Str::random(5));
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable= [
                            'uuid',
                            'code', 
                            'client_type',
                            'civility',
                            'label',
                            'last_name',
                            'website',
                            'fbsite',
                            'twittersite', 
                            'lkdsite', 
                            'siren', 
                            'naf_code', 
                            'intra_community_vat',
                            'electronic_address',
                            'electronic_address_scheme',
                            'statu_customer',
                            'discount',
                            'user_id',
                            'account_general_customer',
                            'account_auxiliary_customer',
                            'statu_supplier',
                            'account_general_supplier',
                            'account_auxiliary_supplier',
                            'recept_controle',
                            'comment',
                            'active',
                            'barcode_value',
                            'longitude',
                            'latitude',
                            'delivery_constraint',
                            'tolerance_days',
                            'quoted_delivery_note',
                            'csv_file_name',
                        ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['label', 'last_name', 'client_type', 'active', 'statu_customer', 'statu_supplier'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => "Entreprise {$event}");
    }

    /**
     * Get the label attribute for the company.
     *
     * If the company is an individual (client_type == '2'), the label will be
     * formatted as "Civility Label Last Name". Otherwise, it will return the
     * original label.
     *
     * @return string The formatted label or the original label.
     */
    public function getLabelAttribute()
    {
        if ($this->client_type == '2') { // If it is an individual
            return "{$this->civility} {$this->attributes['label']} {$this->last_name}";
        }

        // Otherwise, return the original label
        return $this->attributes['label'];
    }

    /**
     * Get the Addresses associated with the company.
     *
     * This function defines a one-to-many relationship between the Company model
     * and the CompaniesAddresses model. It indicates that a company can have multiple Addresses.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Addresses()
    {
        return $this->hasMany(CompaniesAddresses::class);
    }

    /**
     * Get the count of addresses associated with the company.
     *
     * This accessor method returns the total number of addresses
     * related to the company by calling the count method on the
     * Addresses relationship.
     *
     * @return int The count of addresses.
     */
    public function getAddressesCountAttribute()
    {
        return $this->Addresses()->count();
    }

    /**
     * Get the Contacts associated with the company.
     *
     * This function defines a one-to-many relationship between the Company model
     * and the CompaniesContacts model. It indicates that a company can have multiple Contacts.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Contacts()
    {
        return $this->hasMany(CompaniesContacts::class);
    }

    /**
     * Get the count of Contacts associated with the company.
     *
     * This accessor method returns the total number of Contacts
     * related to the company by calling the count method on the
     * Contacts relationship.
     *
     * @return int The count of Contacts.
     */
    public function getContactsCountAttribute()
    {
        return $this->Contacts()->count();
    }

    /**
     * Get the user management associated with the company.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the leads associated with the company.
     *
     * This function defines a one-to-many relationship between the Company model
     * and the Leads model. It indicates that a company can have multiple leads.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Leads()
    {
        return $this->hasMany(Leads::class, 'companies_id');
    }

    /**
     * Get the count of leads associated with the company.
     *
     * This accessor method returns the total number of leads
     * related to the company by calling the count method on the
     * Leads relationship.
     *
     * @return int The count of leads.
     */
    public function getLeadsCountAttribute()
    {
        return $this->Leads()->count();
    }

    /**
     * Get the quotes associated with the company.
     *
     * This function defines a one-to-many relationship between the Company model
     * and the Quotes model. It indicates that a company can have multiple quotes.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Quotes()
    {
        return $this->hasMany(Quotes::class, 'companies_id');
    }

    /**
     * Get the count of quotes associated with the company.
     *
     * This accessor method returns the total number of quotes
     * related to the company by calling the count method on the
     * Quotes relationship.
     *
     * @return int The count of quotes.
     */
    public function getQuotesCountAttribute()
    {
        return $this->Quotes()->count();
    }

    /**
     * Get the non-conformities associated with the company.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function NonConformity()
    {
        return $this->hasMany(QualityNonConformity::class, 'companies_id');
    }

    /**
     * Get the orders associated with the company.
     *
     * This function defines a one-to-many relationship between the Company model
     * and the Orders model. It indicates that a company can have multiple orders.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Orders()
    {
        return $this->hasMany(Orders::class, 'companies_id');
    }

    /**
     * Get the count of orders associated with the company.
     *
     * This accessor method returns the total number of orders
     * related to the company by calling the count method on the
     * Orders relationship.
     *
     * @return int The count of orders.
     */
    public function getOrdersCountAttribute()
    {
        return $this->Orders()->count();
    }

    /**
     * Get the deliveries associated with the company.
     *
     * This function defines a one-to-many relationship between the Company model
     * and the Delivery model. It indicates that a company can have multiple deliveries.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Deliverys()
    {
        return $this->hasMany(Deliverys::class, 'companies_id');
    }

    /**
     * Get the count of deliveries associated with the company.
     *
     * This accessor method returns the total number of deliveries
     * related to the company by calling the count method on the
     * Deliverys relationship.
     *
     * @return int The count of deliveries.
     */
    public function getDeliverysCountAttribute()
    {
        return $this->Deliverys()->count();
    }

    /**
     * Get the invoices associated with the company.
     *
     * This function defines a one-to-many relationship between the Company model
     * and the Invoices model. It indicates that a company can have multiple invoices.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Invoices()
    {
        return $this->hasMany(Invoices::class, 'companies_id');
    }

    /**
     * Get the count of invoices associated with the company.
     *
     * This accessor method returns the total number of invoices
     * related to the company by calling the count method on the
     * Invoices relationship.
     *
     * @return int The count of invoices.
     */
    public function getInvoicesCountAttribute()
    {
        return $this->Invoices()->count();
    }

    /**
     * Get the purchases associated with the company.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Purchases()
    {
        return $this->hasMany(Purchases::class, 'companies_id');
    }

    /**
     * Get the count of purchases associated with the company.
     *
     * @return int The number of purchases.
     */
    public function getPurchasesCountAttribute()
    {
        return $this->Purchases()->count();
    }

    /**
     * Define a polymorphic many-to-many relationship.
     *
     * This method establishes a polymorphic relationship between the current model
     * and the File model, allowing the current model to be associated with multiple files.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function files()
    {
        return $this->morphToMany(File::class, 'fileable')->withPivot(['role', 'is_primary']);
    }
    
    /**
     * Get the supplier ratings associated with the company.
     *
     * This function defines a one-to-many relationship between the Company model
     * and the SupplierRating model. It indicates that a company can have multiple
     * supplier ratings.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function rating()
    {
        return $this->hasMany(SupplierRating::class);
    }
    
    /**
     * Calculate the average rating for the company.
     *
     * This method retrieves the average value of the 'rating' column
     * from the related ratings.
     *
     * @return float|null The average rating, or null if there are no ratings.
     */
    public function averageRating()
    {
        return $this->rating()->avg('rating');
    }

    /**
     * Get the products associated with this supplier.
     *
     * This function defines a many-to-many relationship between the Company model
     * and the Products model through the 'products_preferred_suppliers' pivot table.
     * It indicates that a company (acting as a supplier) can be associated with multiple products.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function productsfromThisSupplier() {
        return $this->belongsToMany(Products::class, 'products_preferred_suppliers', 'companies_id', 'product_id')->withTimestamps();
    }

    /**
     * Get the formatted creation date of the company.
     *
     * This accessor method returns the creation date of the company
     * formatted as 'day month year' (e.g., '01 January 2023').
     *
     * @return string The formatted creation date.
     */
    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
