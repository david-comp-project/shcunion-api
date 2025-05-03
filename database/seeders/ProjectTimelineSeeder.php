<?php

namespace Database\Seeders;

use App\Models\Icon;
use App\Models\Project;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use App\Models\ProjectTimeline;
use Carbon\Carbon;

class ProjectTimelineSeeder extends Seeder
{
    public function run(): void
    {
        $projects = Project::pluck('project_id')->toArray();
        $icons = Icon::pluck('icon_id')->toArray();

        if (empty($projects) || empty($icons)) {
            $this->command->warn('Data project atau icon masih kosong.');
            return;
        }

        foreach ($projects as $projectId) {
            $baseDate = Carbon::now()->subDays(10);

            for ($i = 0; $i < 10; $i++) {
                $timelineDate = $baseDate->copy()->addDays($i);

                $timeline = ProjectTimeline::create([
                    'project_timeline_id' => Str::uuid(),
                    'project_id' => $projectId,
                    'timeline_date' => $timelineDate->toDateString(),
                ]);

                for ($j = 0; $j < 3; $j++) {
                    $timeline->projectTimelineDetails()->create([
                        'project_timeline_detail_id' => Str::uuid(),
                        'project_timeline_id' => $timeline->project_timeline_id,
                        'description' => fake()->sentence(6),
                        'time' => $timelineDate->copy()->addHours($j)->format('H:i:s'),
                        'icon_id' => $icons[array_rand($icons)],
                    ]);
                }
            }
        }
    }
}
