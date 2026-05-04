<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\User;
use App\Services\TimeEntryService;
use Illuminate\Console\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\search;

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
            error('Please provide --user (id or email).');

            return 1;
        }

        $user = User::where('id', $userArg)->orWhere('email', $userArg)->first();

        if (! $user) {
            error('User not found.');

            return 1;
        }

        // Resolve project
        $projectId = $this->option('project-id');

        if (! $projectId) {
            $projects = Project::where('user_id', $user->id)->with('client')->orderBy('name')->get();

            if ($projects->isEmpty()) {
                error('No projects found for this user. Create a project first.');

                return 1;
            }

            $projectId = search(
                label: 'Select a project',
                options: function (string $value) use ($projects) {
                    return $projects
                        ->when(
                            $value !== '',
                            fn ($col) => $col->filter(fn ($p) => str_contains(strtolower($p->name), strtolower($value)))
                        )
                        ->mapWithKeys(fn ($p) => [$p->id => $p->name.' ('.($p->client->name ?? 'No client').')'])
                        ->all();
                },
                placeholder: 'Search projects...',
            );
        } else {
            $project = Project::where('user_id', $user->id)->find($projectId);
            if (! $project) {
                error('Project not found for this user.');

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
