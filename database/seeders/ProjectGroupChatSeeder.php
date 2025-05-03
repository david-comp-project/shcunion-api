<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\GroupChat;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;

class ProjectGroupChatSeeder extends Seeder
{
    public function run(): void
    {
        $projects = Project::all();

        foreach ($projects as $project) {
            // Buat slug dari judul project
            $groupName = Str::slug($project->project_title, '-');

            $groupChat = GroupChat::create([
                // 'group_chat_id' => Str::uuid(),
                'group_chat_name' => $groupName,
                'initiator_user_id' => $project->creator_id,
                'project_id' => $project->project_id,
                'group_avatar' => 'project/avatar.jpg'
            ]);

            // Tambahkan creator sebagai member grup (jika creator_id ada)
            if ($project->creator_id) {
                $groupChat->users()->attach($project->creator_id);
            }
        }
    }
}
