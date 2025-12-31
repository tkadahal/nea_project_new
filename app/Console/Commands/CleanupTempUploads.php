<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TempUpload;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupTempUploads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-temp-uploads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        TempUpload::where('expires_at', '<', now())->each(function ($temp) {
            Storage::delete($temp->path);
            $temp->delete();
        });
    }
}
