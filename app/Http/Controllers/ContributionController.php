<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Project;
use Illuminate\Http\Request;
use App\Models\DonationPayment;
use App\Traits\HasFiltersTrait;
use App\Models\VolunteerInvolvement;
use App\Traits\HasFileTrait;
use Illuminate\Support\Facades\Storage;

class ContributionController extends Controller
{

    use HasFiltersTrait, HasFileTrait;

    public function getContributionList(Request $request, User $user) {
        // $filters = $request->only(['status', 'sort', 'category', 'search']);

        // return $filters;

        $user_id = Auth('api')->user()->user_id;

        $project_donations = DonationPayment::with(['project'])->where('donatur_id', $user_id)->get();
        $project_volunteers = VolunteerInvolvement::with(['project'])->where('volunteer_id', $user_id)->get();

        $project_donation_list = $this->getDonationList($project_donations);
        $project_volunteer_list = $this->getVolunteerList($project_volunteers);

        
        $project_list = $project_donation_list->concat($project_volunteer_list)->unique('project_id');

        $filtered_projects = $this->filterProjects($project_list, $request);

        return response()->json([
            'message' => 'succes', 
            'projects' => $filtered_projects,
            'projects_count' => $filtered_projects->count()
        ], 200);
    }

    public function getDonationList($project_donations) {
        $project_donation_list = $project_donations->map(function ($donation) {
            list($target_amount, $progress_amount, $progress_percentage, $_) = $this->getAmount($donation->project);
            $lpj = $donation->project->projectLampirans()->where('tag', 'lpj')->first();
            return [
                "project_id" => $donation->project->project_id,
                "project_title" => $donation->project->project_title,
                "project_description" => $donation->project->project_description,
                "project_image" => $donation->project->project_image_path ? asset(Storage::url($donation->project->project_image_path)) : null,
                "project_category" => $donation->project->project_category,
                "project_start_date" => $donation->project->project_start_date,
                "project_end_date" => $donation->project->project_end_date,
                "project_target_amount" => $target_amount,
                "project_progress_amount" => $progress_amount,
                "project_progress_percentage" => $progress_percentage > 100 ? 100 : $progress_percentage,
                "project_status" => $donation->project->project_status,

                "donation_amount" => $donation->donation_amount,
                "channel_payment" => $donation->channel_payment,
                "status_payment" => $donation->status,

                "volunteer_hours" => null,
                "volunteer_role" => null,
                "involvement_date" => null,

                "project_file_lpj" => $lpj ? $this->getUrlFile($lpj->path_lampiran) : null

            ];
        });

        return $project_donation_list;
    }

    public function getVolunteerList($project_volunteers) {
        $project_volunteer_list = $project_volunteers->map(function ($volunteer) {
            list($target_amount, $progress_amount, $progress_percentage, $_) = $this->getAmount($volunteer->project);
            $lpj = $volunteer->project->projectLampirans()->where('tag', 'lpj')->first();
            return [
                "project_id" => $volunteer->project->project_id,
                "project_title" => $volunteer->project->project_title,
                "project_description" => $volunteer->project->project_description,
                "project_image" => $volunteer->project->project_image_path ? asset(Storage::url($volunteer->project->project_image_path)) : null,
                "project_category" => $volunteer->project->project_category,
                "project_start_date" => $volunteer->project->project_start_date,
                "project_end_date" => $volunteer->project->project_end_date,
                "project_target_amount" => $target_amount,
                "project_progress_amount" => $progress_amount,
                "project_progress_percentage" => $progress_percentage > 100 ? 100 : $progress_percentage,
                "project_status" => $volunteer->project->project_status,
                
                "donation_amount" => null,
                "channel_payment" => null,
                "status_payment" => null,

                "volunteer_hours" => $volunteer->volunteer_hours,
                "volunteer_role" => $volunteer->role,
                "involvement_date" => $volunteer->involvement_date,

                "project_file_lpj" => $lpj ? $this->getUrlFile($lpj->path_lampiran) : null
            ];
        });

        return $project_volunteer_list;
    }
    

    public function getAmount(Project $p) {
        // echo ($p->project_id);
        // echo("\n");
        if ($p->project_category == 'donation') {
            $target_amount = $p->project_target_amount;
            $progress_amount = round($p->projectDonations->sum('donation_amount'),2);
            $progress_donatur = $p->projectDonations->count();
            $progress_percentage = round(($progress_amount / $target_amount) * 100);
        } else {
            $target_amount = $p->project_target_amount;
            $progress_amount = $p->projectVolunteers->count();
            $progress_donatur = $p->projectVolunteers->count();
            $progress_percentage = round(($progress_amount / $target_amount) * 100);
        }  


        return [$target_amount, $progress_amount, $progress_percentage, $progress_donatur];
    }

}
