<?php

namespace Webkul\Email\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Email\InboundEmailProcessor\Contracts\InboundEmailProcessor;

class ProcessInboundEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inbound-emails:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will process the incoming emails from the mail server.';

    /**
     * Handle.
     *
     * @return void
     */
    public function handle(InboundEmailProcessor $inboundEmailProcessor)
    {
        $this->info('Processing the incoming emails.');

        $inboundEmailProcessor->processMessagesFromAllFolders();

        $this->info('Incoming emails processed successfully.');
    }
}
