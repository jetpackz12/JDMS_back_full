<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DeleteExpireTokenJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-expire-token-job';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deleting expired token.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $current_date_time = Carbon::now();
        Log::info('Current date and time ::::: ', ['current_date_time' => $current_date_time]);
        try {
            $deletedRows = PersonalAccessToken::where('expires_at', '<=', $current_date_time)->delete();
            Log::info('Deleted expired tokens.', ['deleted_rows' => $deletedRows]);
        } catch (\Exception $e) {
            Log::error('Failed to delete expired tokens.', ['error' => $e->getMessage()]);
        }
    }
}
