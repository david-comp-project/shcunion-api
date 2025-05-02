<?php

namespace App\Http\Controllers;

use App\Enums\DonationTransactionStatusEnum;
use App\Enums\ProjectEvaluationEnum;
use App\Models\Tag;
use App\Models\Icon;
use App\Models\ProjectTag;
use Illuminate\Http\Request;
use App\Enums\ProjectStatusEnum;
use App\Enums\UserStatusEnum;
use App\Enums\VolunteerStatusEnum;
use App\Enums\WithdrawalStatusEnum;

class GeneralDataController extends Controller
{
    public function getIconList() {
        $icons = Icon::all();

        return response()->json([
            'message' => 'Icon Succesfully Send',
            'icons' => $icons
        ], 200);
    }

    public function getProjectTagList() {
        $projectTags = Tag::all();

        return response()->json([
            'message' => 'Icon Succesfully Send',
            'project_tags' => $projectTags
        ], 200);
    }
    
    public function getProjectStatusList() {
        $statuses = ProjectStatusEnum::values();
    
        return response()->json([
            'message' => 'Project Status Successfully Retrieved',
            'statuses' => $statuses
        ], 200);
    }
    
    public function getProjectEvaluationStatusList() {
        $statuses = ProjectEvaluationEnum::values();
    
        return response()->json([
            'message' => 'Project Evaluation Status Successfully Retrieved',
            'statuses' => $statuses
        ], 200);
    }
    
    public function getUserStatusList() {
        $statuses = UserStatusEnum::values();
    
        return response()->json([
            'message' => 'User Status Successfully Retrieved',
            'statuses' => $statuses
        ], 200);
    }
    
    public function getDonationTransactionStatusList() {
        $statuses = DonationTransactionStatusEnum::values();
    
        return response()->json([
            'message' => 'Donation Transaction Status Successfully Retrieved',
            'statuses' => $statuses
        ], 200);
    }
    
    public function getWithdrawalStatus() {
        $statuses = WithdrawalStatusEnum::values();
    
        return response()->json([
            'message' => 'Withdrawal Status Successfully Retrieved',
            'statuses' => $statuses
        ], 200);
    }

    public function getVplunteerStatusList() {
        $statuses = VolunteerStatusEnum::values();
    
        return response()->json([
            'message' => 'Volunteer Status Successfully Retrieved',
            'statuses' => $statuses
        ], 200);
    }
    
}
