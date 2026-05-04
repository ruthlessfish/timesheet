<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TimeEntryService;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;

class StopTimeEntryCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'time:stop {--user= : User id or email} {--project-id= : Project id to limit stopping} {--confirm : Skip confirmation}';

    /**
     * @var string
     */
    protected $description = 'Stop an active time entry (timer) for a user';

    public function __construct(private TimeEntryService $timeEntryService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $userArg = $this->option('user');

        if (! $userArg) {
            error('Please provide --user (id or email).');

            return 1;
        }

        $user = User::where('id', $userArg)->orWhere('email', $userArg)->first();

        if (! $user) {
            error('User not found.');

            return 1;
        }

        $active = $this->timeEntryService->getActiveTimer($user->id);

        if (! $active) {
            error('No active timer found for this user.');

            return 1;
        }

        // If project-id provided and doesn't match active, error
        $projectId = $this->option('project-id');
        if ($projectId && (int) $projectId !== $active->project_id) {
            error('Active timer project does not match provided --project-id.');

            return 1;
        }

        if (! $this->option('confirm')) {
            $ok = confirm(
                label: sprintf('Stop timer #%d for project "%s" started at %s?', $active->id, $active->project->name, $active->start_time->toDateTimeString()),
                default: true,
            );

            if (! $ok) {
                $this->info('Aborted.');

                return 1;
            }
        }

        try {
            $stopped = $this->timeEntryService->stopTimer($active);

            $amount = number_format($stopped->amount ?? 0, 2);
            $this->info(sprintf('Stopped timer #%d. Duration: %d minutes. Amount: %s', $stopped->id, $stopped->duration, $amount));

            return 0;
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            return 1;
        }
    }
}
