<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MiningManager\Services\Security\SecretValue;

/**
 * Encrypt the webhook URLs already saved.
 *
 *  webhook_configurations.webhook_url
 *  mining_manager_settings: notifications.slack_webhook_url
 *
 * Anyone holding a webhook URL can post to that channel, so a database dump
 * or a server backup should not contain them in the clear. Values are sealed
 * with SeAT's APP_KEY (see SecretValue). Rows already sealed are left alone,
 * so running this twice is harmless.
 *
 * down() puts the plain URLs back.
 */
class EncryptMiningManagerWebhookUrls extends Migration
{
    public function up(): void
    {
        $this->each(function (?string $value) {
            return SecretValue::seal($value);
        });
    }

    public function down(): void
    {
        $this->each(function (?string $value) {
            return SecretValue::isSealed($value) ? SecretValue::open($value) : $value;
        });
    }

    private function each(callable $transform): void
    {
        if (Schema::hasTable('webhook_configurations')) {
            foreach (DB::table('webhook_configurations')->select('id', 'webhook_url')->get() as $row) {
                $new = $transform($row->webhook_url);

                if ($new !== null && $new !== $row->webhook_url) {
                    DB::table('webhook_configurations')->where('id', $row->id)->update(['webhook_url' => $new]);
                }
            }
        }

        if (Schema::hasTable('mining_manager_settings')) {
            $rows = DB::table('mining_manager_settings')
                ->where('key', 'notifications.slack_webhook_url')
                ->select('id', 'value')
                ->get();

            foreach ($rows as $row) {
                $new = $transform($row->value);

                if ($new !== null && $new !== $row->value) {
                    DB::table('mining_manager_settings')->where('id', $row->id)->update(['value' => $new]);
                }
            }
        }
    }
}
