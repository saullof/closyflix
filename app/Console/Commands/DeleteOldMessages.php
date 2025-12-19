<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Model\UserMessage;
use Carbon\Carbon;

class DeleteOldMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messages:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove mensagens com mais de 40 dias';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Calcula a data-limite (40 dias atrás)
        $threshold = Carbon::now()->subDays(40);

        // Apaga todas as mensagens cuja coluna created_at seja anterior à threshold
        $deleted = UserMessage::where('created_at', '<', $threshold)->delete();

        $this->info("Mensagens com mais de 40 dias removidas: {$deleted}");
        return 0;
    }
}
