<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a test user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Create clients
        $client1 = Client::create([
            'user_id' => $user->id,
            'name' => 'Acme Corporation',
            'email' => 'contact@acme.com',
            'phone' => '555-0101',
            'company' => 'Acme Corp',
            'address' => '123 Business St, Tech City, TC 12345',
            'hourly_rate' => 150.00,
            'is_active' => true,
        ]);

        $client2 = Client::create([
            'user_id' => $user->id,
            'name' => 'Global Industries',
            'email' => 'info@globalind.com',
            'phone' => '555-0102',
            'company' => 'Global Industries Inc',
            'address' => '456 Commerce Ave, Metro City, MC 67890',
            'hourly_rate' => 125.00,
            'is_active' => true,
        ]);

        $client3 = Client::create([
            'user_id' => $user->id,
            'name' => 'StartUp Ventures',
            'email' => 'hello@startup.io',
            'phone' => '555-0103',
            'company' => 'StartUp Ventures LLC',
            'hourly_rate' => 100.00,
            'is_active' => true,
        ]);

        // Create projects
        $project1 = Project::create([
            'user_id' => $user->id,
            'client_id' => $client1->id,
            'name' => 'Website Redesign',
            'description' => 'Complete redesign of company website with modern UI/UX',
            'hourly_rate' => 150.00,
            'budget' => 15000.00,
            'status' => 'active',
            'start_date' => now()->subDays(30),
        ]);

        $project2 = Project::create([
            'user_id' => $user->id,
            'client_id' => $client1->id,
            'name' => 'Mobile App Development',
            'description' => 'iOS and Android mobile application',
            'hourly_rate' => 175.00,
            'budget' => 25000.00,
            'status' => 'active',
            'start_date' => now()->subDays(20),
        ]);

        $project3 = Project::create([
            'user_id' => $user->id,
            'client_id' => $client2->id,
            'name' => 'E-commerce Platform',
            'description' => 'Full-featured online store with payment integration',
            'hourly_rate' => 125.00,
            'budget' => 20000.00,
            'status' => 'active',
            'start_date' => now()->subDays(15),
        ]);

        $project4 = Project::create([
            'user_id' => $user->id,
            'client_id' => $client3->id,
            'name' => 'MVP Development',
            'description' => 'Minimum viable product for new SaaS platform',
            'hourly_rate' => 100.00,
            'budget' => 10000.00,
            'status' => 'on_hold',
            'start_date' => now()->subDays(5),
        ]);

        // Create time entries
        $timeEntries = [];

        // Last 2 weeks of time entries
        for ($day = 14; $day >= 1; $day--) {
            $date = now()->subDays($day);

            // Skip weekends
            if ($date->isWeekend()) {
                continue;
            }

            // Morning entry for project 1
            $start = $date->setTime(9, 0);
            $end = $date->setTime(12, 30);
            $duration = $start->diffInMinutes($end);

            $entry1 = TimeEntry::create([
                'user_id' => $user->id,
                'project_id' => $project1->id,
                'description' => 'Working on homepage design and layout',
                'start_time' => $start,
                'end_time' => $end,
                'duration' => $duration,
                'hourly_rate' => 150.00,
                'is_billable' => true,
                'is_invoiced' => $day > 7,
            ]);

            if ($day > 7) {
                $timeEntries[] = $entry1;
            }

            // Afternoon entry for project 2 or 3
            $project = $day % 2 == 0 ? $project2 : $project3;
            $start = $date->setTime(13, 30);
            $end = $date->setTime(17, 0);
            $duration = $start->diffInMinutes($end);

            $entry2 = TimeEntry::create([
                'user_id' => $user->id,
                'project_id' => $project->id,
                'description' => $project->id == $project2->id
                    ? 'Implementing authentication features'
                    : 'Building product catalog',
                'start_time' => $start,
                'end_time' => $end,
                'duration' => $duration,
                'is_billable' => true,
                'is_invoiced' => $day > 7,
            ]);

            if ($day > 7) {
                $timeEntries[] = $entry2;
            }
        }

        // Create an invoice with some time entries
        $invoice = Invoice::create([
            'user_id' => $user->id,
            'client_id' => $client1->id,
            'invoice_number' => 'INV-2026-0001',
            'issue_date' => now()->subDays(3),
            'due_date' => now()->addDays(27),
            'tax_rate' => 10.00,
            'status' => 'sent',
        ]);

        // Add first 5 time entries as invoice items
        $subtotal = 0;
        foreach (array_slice($timeEntries, 0, 5) as $entry) {
            $hours = $entry->duration / 60;
            $rate = $entry->hourly_rate ?? $entry->project->hourly_rate;
            $amount = $hours * $rate;

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'time_entry_id' => $entry->id,
                'description' => $entry->description,
                'quantity' => round($hours, 2),
                'rate' => $rate,
                'amount' => $amount,
            ]);

            $subtotal += $amount;
        }

        // Update invoice totals
        $invoice->subtotal = $subtotal;
        $invoice->tax_amount = $subtotal * ($invoice->tax_rate / 100);
        $invoice->total = $invoice->subtotal + $invoice->tax_amount;
        $invoice->save();

        // Create one active timer (running now)
        TimeEntry::create([
            'user_id' => $user->id,
            'project_id' => $project1->id,
            'description' => 'Implementing contact form functionality',
            'start_time' => now()->subHours(2)->subMinutes(15),
            'end_time' => null,
            'duration' => null,
            'hourly_rate' => 150.00,
            'is_billable' => true,
            'is_invoiced' => false,
        ]);

        $this->command->info('Sample data created successfully!');
        $this->command->info('Login with: test@example.com / password');
    }
}
