<?php

namespace App\Enums;

/**
 * Nature d'un équipement : methods_ressources.equipment_kind, à défaut
 * methods_services.equipment_kind (null = non précisé).
 *
 * Sert à décrire l'atelier ; le jumeau numérique en déduit le modèle 3D
 * (champ `kind` du snapshot, liste identique dans le package factory-twin).
 * Une valeur ajoutée ici doit l'être aussi dans le package et dans
 * resources/lang/{fr,en}/equipment.php.
 */
enum EquipmentKind: string
{
    case LaserSheet     = 'laser_sheet';
    case LaserTube      = 'laser_tube';
    case Punch          = 'punch';
    case PressBrake     = 'press_brake';
    case Press          = 'press';
    case Rolling        = 'rolling';
    case Saw            = 'saw';
    case Lathe          = 'lathe';
    case Milling        = 'milling';
    case Welding        = 'welding';
    case Deburring      = 'deburring';
    case DeburringManual = 'deburring_manual';
    case Paint          = 'paint';
    case Polishing      = 'polishing';
    case Assembly       = 'assembly';
    case Packing        = 'packing';
    case Inspection     = 'inspection';
    case Office         = 'office';
    case Other          = 'generic';

    public function label(): string
    {
        return __('equipment.kinds.' . $this->value);
    }

    /** @return array<string, string> valeur => libellé, dans l'ordre de la liste */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $kind) => [$kind->value => $kind->label()])->all();
    }

    /** Type retenu : celui de la ressource, sinon celui du poste. */
    public static function resolve(?string $resource, ?string $service): ?self
    {
        return self::tryFrom((string) $resource) ?? self::tryFrom((string) $service);
    }
}
