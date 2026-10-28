<?php

namespace MiningManager\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An ore tax contract: an item exchange contract a member made to the
 * corporation to pay mining tax in ore. See OreContractService.
 */
class OreContract extends Model
{
    public const WAITING = 'waiting';   // outstanding in game, not accepted yet
    public const CREDITED = 'credited'; // accepted, and its value put towards bills
    public const REFUSED = 'refused';   // expired, rejected, deleted or cancelled
    public const EMPTY = 'empty';       // accepted, but nothing in it could be valued
    public const IGNORED = 'ignored';   // no tax code and no bill to pay: not treated as tax

    /**
     * Payments enter the payment ledger with a transaction id. Contract ids
     * and wallet journal ids are separate ESI number spaces, so a contract's
     * payment id is set far above any journal id to keep them from meeting.
     */
    public const PAYMENT_ID_BASE = 9_000_000_000_000_000_000;

    protected $table = 'mining_manager_ore_contracts';
    protected $primaryKey = 'contract_id';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'contract_id', 'corporation_id', 'issuer_id', 'status', 'contract_status',
        'title', 'tax_code', 'mining_tax_id', 'items', 'value', 'credited', 'held',
        'notes', 'date_issued', 'date_completed', 'credited_at',
    ];

    protected $casts = [
        'items' => 'array',
        'value' => 'float',
        'credited' => 'float',
        'held' => 'float',
        'date_issued' => 'datetime',
        'date_completed' => 'datetime',
        'credited_at' => 'datetime',
    ];

    public static function paymentIdFor(int $contractId): int
    {
        return self::PAYMENT_ID_BASE + $contractId;
    }

    public static function isContractPaymentId(?int $transactionId): bool
    {
        return $transactionId !== null && $transactionId >= self::PAYMENT_ID_BASE;
    }

    public function tax()
    {
        return $this->belongsTo(MiningTax::class, 'mining_tax_id');
    }
}
