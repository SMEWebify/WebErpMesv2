<?php 
namespace App\Models\OSH;

use App\Models\User;
use App\Models\Methods\MethodsSection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OSHConformite extends Model
{
    use HasFactory;

    protected $table = 'osh_conformites';

    protected $fillable = [
        'document_type',
        'description',
        'expiration_date',
        'user_id',
        'statut',
        'section_id'
    ];

    // Relation avec l'utilisateur (user)
    public function UserManagement()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relation avec la section
    public function section()
    {
        return $this->belongsTo(MethodsSection::class);
    }
}
