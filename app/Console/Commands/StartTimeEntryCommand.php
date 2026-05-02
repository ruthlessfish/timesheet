<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\User;
use App\Services\TimeEntryService;
use Illuminate\Console\Command;

class StartTimeEntryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * We require --user for now and provide --project-id or interactive selection.
     *
     * @var string
     */
    protected $signature = 'time:start {--user= : User id or email} {--project-id= : Project id} {--description= : Description} {--billable=1 : Is billable (0|1)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start a new time entry (timer) for a user and project';

    public function __construct(private TimeEntryService $timeEntryService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $userArg = $this->option('user');

        if (! $userArg) {
            $this->error('Please provide --user (id or email).');

            return 1;
        }

        $user = User::where('id', $userArg)->orWhere('email', $userArg)->first();

        if (! $user) {
            $this->error('User not found.');

            return 1;
        }

        // Resolve project
        $projectId = $this->option('project-id');

        if (! $projectId) {
            $projects = Project::where('user_id', $user->id)->orderBy('name')->get();

            if ($projects->isEmpty()) {
                $this->error('No projects found for this user. Create a project first.');

                return 1;
            }

            $choices = $projects->mapWithKeys(fn ($p) => [$p->id => $p->name.' ('.($p->client->name ?? 'No client').')'])->toArray();

            $selected = $this->choice('Select a project', $choices, array_key_first($choices));

            // choice returns the selected value; we need the id => name map, so find id by value
            $projectId = array_search($selected, $choices, true);
        } else {
            $project = Project::where('user_id', $user->id)->find($projectId);
            if (! $project) {
                $this->error('Project not found for this user.');

                return 1;
            }
        }

        $data = [
            'description' => $this->option('description'),
            'is_billable' => (bool) $this->option('billable'),
        ];

        try {
            $entry = $this->timeEntryService->startTimer($user->id, (int) $projectId, $data);

            $this->info(sprintf('Started timer #%d for project #%d (%s) at %s', $entry->id, $entry->project_id, $entry->project->name, $entry->start_time));

            return 0;
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            return 1;
        }
    }
}
