<?php

namespace App\Console\Commands;

use App\Console\Command;
use App\Services\TimeEntryService;

class TrackTimeEntryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'time:track 
        {--user= : User id or email} 
        {--project-id= : Project id} 
        {--description= : Description} 
        {--duration= Duration in minutes}
        {--billable=1 : Is billable (0|1)}';
        {start :  Start time (Y-m-d H:i:s)}
        {end : End time (Y-m-d H:i:s)}

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new time entry for a user and project, without starting the timer';

     public function __construct(private TimeEntryService $timeEntryService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {        
        $user = $this->getUserOption();

        if (! $user) {
            return 1;
        }

        $projectId = $this->option('project-id');

        if (! $projectId) {
            $projects = Project::where('user_id', $user->id)
                ->with('client')
                ->orderBy('name')
                ->get();

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
            'start_time' => $this->argument('start') ? Carbon::parse($this->argument('start')) : now(),
            'end_time' => $this->argument('end') ? Carbon::parse($this->argument('end')) : now(),
            'description' => $this->option('description'),
            'is_billable' => (bool) $this->option('billable'),
        ];

        $entry = $this->timeEntryService->createManualEntry($user->id, (int) $projectId, $data);

        if ($entry) {
            info('Time entry created with ID: '.$entry->id);
            return 0;
        }

        error('Failed to create time entry.');
        return 1;
    }
}
