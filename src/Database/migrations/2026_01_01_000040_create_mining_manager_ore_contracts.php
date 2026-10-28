<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ore tax contracts the plugin has looked at.
 *
 * In ore mode members pay their tax with an item exchange contract to the
 * corporation. Each such contract SeAT has fetched gets a row here: waiting
 * while it is outstanding, credited once accepted (with what it was worth and
 * where that went), or set aside when it expired, was refused, or holds
 * nothing to credit. One row per contract, so a contract is never credited
 * twice.
 */
class CreateMiningManagerOreContracts extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mining_manager_ore_contracts')) {
            return;
        }

        Schema::create('mining_manager_ore_contracts', function (Blueprint $table) {
            $table->unsignedBigInteger('contract_id')->primary();
            $table->unsignedBigInteger('corporation_id');
            $table->unsignedBigInteger('issuer_id')->comment('Character who made the contract');
            $table->string('status', 20)->comment('waiting, credited, refused, empty');
            $table->string('contract_status', 30)->nullable()->comment('As ESI last reported it');
            $table->string('title', 255)->nullable();
            $table->string('tax_code', 64)->nullable()->comment('Tax code found in the title');
            $table->unsignedBigInteger('mining_tax_id')->nullable()->comment('Bill named by the code, or the first one it paid');
            $table->json('items')->nullable()->comment('type_id, raw_type_id, units, unit_value, value, on_bill');
            $table->decimal('value', 20, 2)->default(0)->comment('ISK the ore was worth');
            $table->decimal('credited', 20, 2)->default(0)->comment('ISK put towards bills');
            $table->decimal('held', 20, 2)->default(0)->comment('ISK kept as balance');
            $table->text('notes')->nullable();
            $table->dateTime('date_issued')->nullable();
            $table->dateTime('date_completed')->nullable();
            $table->timestamp('credited_at')->nullable();
            $table->timestamps();

            $table->index(['corporation_id', 'status'], 'idx_mm_ore_contract_corp_status');
            $table->index('issuer_id', 'idx_mm_ore_contract_issuer');
            $table->index('mining_tax_id', 'idx_mm_ore_contract_tax');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mining_manager_ore_contracts');
    }
}
