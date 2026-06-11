<?php

namespace App\Models;

use Database\Factories\PrescriptionItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $prescription_id
 * @property string $name
 * @property string|null $dosage
 * @property string|null $instructions
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $quantity
 * @property-read Prescription $prescription
 *
 * @method static \Database\Factories\PrescriptionItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrescriptionItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrescriptionItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrescriptionItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrescriptionItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrescriptionItem whereDosage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrescriptionItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrescriptionItem whereInstructions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrescriptionItem whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrescriptionItem wherePrescriptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrescriptionItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrescriptionItem whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['prescription_id', 'name', 'quantity', 'dosage', 'instructions'])]
class PrescriptionItem extends Model
{
    /** @use HasFactory<PrescriptionItemFactory> */
    use HasFactory;

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }
}
