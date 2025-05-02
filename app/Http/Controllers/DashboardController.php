<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Desa;
use App\Models\User;
use App\Models\Project;
use Carbon\CarbonPeriod;
use Illuminate\Support\Str;
use App\Models\ProjectShare;
use Illuminate\Http\Request;
use App\Models\DonationPayment;
use App\Enums\ProjectStatusEnum;
use Illuminate\Support\Facades\DB;
use App\Models\VolunteerInvolvement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{   

    protected $currentMonth;
    protected $lastMonth;

    public function __construct()
    {
        $this->currentMonth = Carbon::now('UTC');
        $this->lastMonth    = Carbon::now('UTC')->subMonth();
    }

    public function getProgressPercentageMonth($donation_percentage, $volunteer_percentage) {
        return (($donation_percentage) + ($volunteer_percentage)) / 2;
    }

    public function getPercentage($last_amount, $current_amount) {
        // Hitung percentage dengan pengecekan pembagi (lastDonationCount atau lastVolunteerCount) agar tidak terjadi division by zero
        if ($last_amount == 0) {
            // Jika bulan lalu tidak ada data, misal: jika data bulan ini juga 0, maka 0%, jika ada, bisa dianggap 100% kenaikan.
            $percentage = ($current_amount > 0) ? 100 : 0;
        } else {
            $percentage = (($current_amount - $last_amount) / $last_amount) * 100;
        }

        return $percentage;
    }

    public function getAmount($model, String $type, $field) {


        if ($type === 'sum') {        
            $currentAmount = $model
                ->whereMonth('created_at', $this->currentMonth->month)
                ->whereYear('created_at', $this->currentMonth->year)
                ->sum($field);



            $lastAmount = $model
                ->whereMonth('created_at', $this->lastMonth->month)
                ->whereYear('created_at', $this->lastMonth->year)
                ->sum($field);
        } elseif ($type === 'count') {
            $currentAmount = $model
                ->whereMonth('created_at', $this->currentMonth->month)
                ->whereYear('created_at', $this->currentMonth->year)
                ->count();

            $lastAmount = $model
                ->whereMonth('created_at', $this->lastMonth->month)
                ->whereYear('created_at', $this->lastMonth->year)
                ->count();
        }


        return [$currentAmount, $lastAmount];
    }

    public function getStatisticCard(User $user) {
        if ($user->user_id !== Auth('api')->user()->user_id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $donationCountModel = $user->projects()->where('project_category', 'donation');
        $volunteerCountModel = $user->projects()->where('project_category', 'volunteer');


        list($currentDonationCount, $lastDonationCount) = $this->getAmount($donationCountModel, 'count', null);
        list($currentVolunteerCount, $lastVolunteerCount) = $this->getAmount($volunteerCountModel, 'count', null);

        $donation_percentage = $this->getPercentage($lastDonationCount, $currentDonationCount);
        $volunteer_percentage = $this->getPercentage($lastVolunteerCount, $currentVolunteerCount);

        $percentage_progress = $this->getProgressPercentageMonth($donation_percentage, $volunteer_percentage);

        $donationContributionModel = DonationPayment::where('donatur_id', $user->user_id);
        $volunterContributionModel = VolunteerInvolvement::where('volunteer_id', $user->user_id);

        list($current_donation_contibution_sum, $last_donation_contibution_sum) = $this->getAmount($donationContributionModel, 'count', null);
        list($current_volunteer_contibution_sum, $last_volunteer_contibution_sum) = $this->getAmount($volunterContributionModel, 'count', null);

        $donation_contribution_percentage = $this->getPercentage($last_donation_contibution_sum, $current_donation_contibution_sum);
        $volunteer_contribution_percentage = $this->getPercentage($last_volunteer_contibution_sum, $current_volunteer_contibution_sum);

        $contribution_percentage_progress = $this->getProgressPercentageMonth($donation_contribution_percentage, $volunteer_contribution_percentage);

        //Get sum of donation and sum of volunteer hours
        $donationSumModel = DonationPayment::where('donatur_id', $user->user_id);
        // dd($donationSumModel->sum('donation_'));
        $volunteerSumModel = VolunteerInvolvement::where('volunteer_id', $user->user_id);

        list($current_donation_sum, $last_donation_sum) = $this->getAmount($donationSumModel, 'sum', 'donation_amount');
        // dd($current_donation_sum);
        list($current_volunteer_sum, $last_volunteer_sum) = $this->getAmount($volunteerSumModel, 'sum', 'volunteer_hours');
        
        $donation_sum_percentage = $this->getPercentage($last_donation_sum, $current_donation_sum);
        $volunteer_sum_percentage = $this->getPercentage($last_volunteer_sum, $current_volunteer_sum);

        //Modify the response to return the data
        $total_project = [
            'statistic_id' => Str::uuid(),
            'statistic_name' => "Total Projects",
            'statistic_number' => $currentDonationCount . ' / ' . $currentVolunteerCount,
            'statistic_percentage' => $percentage_progress,
            'statistic_status' => $percentage_progress > 0 ? 'up' : 'down',
            'statistic_icon' => "uil uil-plus-circle",
        ];

        $total_contribution = [
            'statistic_id' => Str::uuid(),
            'statistic_name' => "Total Kontribusi",
            'statistic_number' => $current_donation_contibution_sum . ' / ' . $current_volunteer_contibution_sum,
            'statistic_percentage' => $contribution_percentage_progress,
            'statistic_status' => $contribution_percentage_progress > 0 ? 'up' : 'down',
            'statistic_icon' => "uil uil-users-alt",

        ];

        $total_donation_amount = [
            'statistic_id' => Str::uuid(),
            'statistic_name' => "Total Donasi",
            'statistic_number' => $current_donation_sum,
            'statistic_percentage' => $donation_sum_percentage,
            'statistic_status' => $donation_sum_percentage > 0 ? 'up' : 'down',
            'statistic_icon' => "uil uil-money-withdrawal",

        ];

        $total_volunteer_hours = [
            'statistic_id' => Str::uuid(),
            'statistic_name' => "Total Jam Volunteer",
            'statistic_number' => $current_volunteer_sum,
            'statistic_percentage' => $volunteer_sum_percentage,
            'statistic_status' => $volunteer_sum_percentage > 0 ? 'up' : 'down',
            'statistic_icon' => "uil uil-clock",
        ];


        return response()->json([
            'message' => 'Statistic Card Succerfully',
            'project_statistic' => [$total_project, $total_contribution, $total_donation_amount, $total_volunteer_hours]
        ], 200);

    }

    public function getTopDonatur(Request $request, User $user) {
        $filter = $request->input('filter', 'all');

        if ($user->user_id !== Auth('api')->user()->user_id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }


        //Get Project Id
        $project_id = $user->projects()->where('project_category', 'donation')->pluck('project_id')->toArray();


        //Get top donatur for each project where filter is month
        if ($filter === 'month') {
            $top_donatur = DonationPayment::whereIn('project_id', $project_id)
                ->join('users', 'donation_payments.donatur_id', '=', 'users.user_id')
                ->selectRaw('users.user_id as donatur_id, users.profile_picture as avatar, users.full_name as donatur_name, sum(donation_amount) as total_donation')
                ->whereMonth('donation_payments.created_at', $this->currentMonth->month)
                ->whereYear('donation_payments.created_at', $this->currentMonth->year)
                ->groupBy('users.user_id', 'users.profile_picture', 'users.full_name')
                ->orderBy('total_donation', 'desc')
                ->limit(10)
                ->get();
        } else {
            $top_donatur = DonationPayment::whereIn('project_id', $project_id)
                ->join('users', 'donation_payments.donatur_id', '=', 'users.user_id')
                ->selectRaw('users.user_id as donatur_id, users.profile_picture as avatar, users.full_name as donatur_name, sum(donation_amount) as total_donation')
                ->groupBy('users.user_id', 'users.profile_picture', 'users.full_name')
                ->orderBy('total_donation', 'desc')
                ->limit(10)
                ->get();
        }
        //Get top donatur for each project where filter is all time

    

        //Modify the response to return the data
        $top_donatur = $top_donatur->map(function ($donatur) {
            return [
                'donatur_id' => $donatur->donatur_id,
                'donatur_avatar' => $donatur->avatar ? asset(Storage::url($donatur->avatar)) : null,
                'donatur_name' => $donatur->donatur_name,
                'total_donation' => round($donatur->total_donation),
            ];
        });



        if ($top_donatur->isEmpty()) {
            return response()->json([
                'message' => 'Record Top Donatur Not Found',
            ], 404);
        }

        return response()->json([
            'message' => 'Top Donatur Succesfullt Retrieved',
            'top_donatur' => $top_donatur,
        ], 200);


    }

    public function getProjectBestPerformance(Request $request, User $user) {
        if ($user->user_id !== Auth('api')->user()->user_id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        if ($user->user_id !== Auth('api')->user()->user_id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        //Get sort project by its progress -> target_amout/sum(donationPayment(donation_amount)) *100 or target_amout/sum(volunteerInvolvement(donation_amount))
        $projects = Project::with(['projectDonations', 'projectVolunteers', 'desa.kecamatan.kabupaten.provinsi'])
            ->where('creator_id', $user->user_id)
            ->whereMonth('created_at', $this->currentMonth->month)
            ->whereYear('created_at', $this->currentMonth->year)
            ->get()
            ->map(function ($p) {
                list($target_amount, $progress_amount, $progress_percentage, $progress_donatur) = $this->getProgressAmount($p);
                return [
                    'project_id' => $p->project_id,
                    'project_title' => $p->project_title,
                    'project_target_amount' => $p->project_target_amount,
                    'project_category' => $p->project_category,
                    'progress_percentage' => $progress_percentage,
                    'provinsi'            => optional(optional($p->desa)->kecamatan)->kabupaten
                    ? optional(optional($p->desa)->kecamatan->kabupaten)->provinsi->nama_provinsi 
                    : null,
                    'status' => $p->project_status
                ];
            })->where('status', ProjectStatusEnum::IN_PROGRESS->value)->sortByDesc('progress_percentage')->values();


            if ($projects->isEmpty()) {
                return response()->json([
                    'message' => 'Record Project Best Performance Not Found',
                ], 404);
            }

            return response()->json([
                'message' => 'Project Best Performance Successfully Retrieved',
                'projects' => $projects
            ], 200);
        
    }

    public function getProgressAmount(Project $p) {
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

    public function getPieChartData(User $user)
    {
        if ($user->user_id !== Auth('api')->user()->user_id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        } 
        
        try {
            // Ambil semua project_id milik user
        $project_ids = $user->projects()->pluck('project_id');
    
        // Query: Ambil data share dari project_shares dan join ke social_media_configs
        $project_shares = ProjectShare::whereIn('project_id', $project_ids)
            ->join('social_medias', 'project_shares.social_media_id', '=', 'social_medias.social_media_id')
            ->select(
                'social_medias.social_media_id as social_media_id',
                'social_medias.social_media_name as sosmed_name',
                'social_medias.icon as icon',
                'social_medias.background_color as background_color',
                'social_medias.hover_color as hover_color',
                DB::raw('COUNT(*) as count')
            )
            ->groupBy(
                'social_medias.social_media_id', // Perbaikan di sini
                'sosmed_name',
                'icon',
                'background_color',
                'hover_color',
            )
            ->get();
    
        // Hitung total share dari semua project
        $totalShares = $project_shares->sum('count');
    
        // Hitung persentase untuk setiap media dan format respons
        $data = $project_shares->map(function ($share) use ($totalShares) {
            $percentage = $totalShares > 0 ? round(($share->count / $totalShares) * 100, 2) : 0;
            return [
                'social_media_id'  => $share->social_media_id,
                'sosmed_name'      => $share->sosmed_name,
                'icon'             => $share->icon,
                'background_color' => $share->background_color,
                'hover_color'      => $share->hover_color,
                'label'            => $share->label,
                'count'            => $share->count,
                'percentage'       => $percentage
            ];
        });
    
        return response()->json([
            'message' => 'Pie Chart Data Successfully Retrieved',
            'project_social_media'    => $data,
        ], 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
        
    }

    public function getLineChartData(Request $request, User $user) {
    if ($user->user_id !== Auth('api')->user()->user_id) {
        return response()->json([
            'message' => 'Unauthorized',
        ], 401);
    }

    $projectIdList = Project::where('creator_id', $user->user_id)->pluck('project_id')->toArray();

    // Gunakan currentMonth dari properti atau default ke Carbon::now()
    $currentMonth = $this->currentMonth ?? Carbon::now();

    // ----------------------------
    // 1. Data Bulanan (selama 12 bulan)
    // ----------------------------
    $donationAmount = DonationPayment::whereIn('project_id', $projectIdList)
                                ->selectRaw('SUM(donation_amount) as donation_amount, EXTRACT(MONTH FROM created_at) as month')
                                ->groupBy('month')
                                ->get();


    // Ubah collection menjadi array asosiatif dengan key = month (angka)
    $donationAmountByMonth = $donationAmount->keyBy('month');

    $monthlyData = [];
    // Kita tampilkan data untuk bulan 1 s.d. 12
    for ($m = 1; $m <= 12; $m++) {
        // Dapatkan nama bulan menggunakan Carbon (pastikan locale di-set ke 'id' untuk bahasa Indonesia)
        $monthName = Carbon::createFromDate(null, $m, 1)->translatedFormat('F');
        $monthlyData[] = [
            'month'           => $monthName,
            'donation_amount' => isset($donationAmountByMonth[$m]) ? round($donationAmountByMonth[$m]->donation_amount) : 0,
        ];
    }

    // ----------------------------
    // 2. Data Mingguan (untuk bulan berjalan)
    // ----------------------------
    $donationAmountWeek = DonationPayment::whereIn('project_id', $projectIdList)
        ->selectRaw('SUM(donation_amount) as donation_amount, EXTRACT(WEEK FROM created_at) as week')
        ->whereRaw('EXTRACT(MONTH FROM created_at) = ?', [$currentMonth->month])
        ->groupBy('week')
        ->get();

    $donationAmountByWeek = $donationAmountWeek->keyBy('week');

    // Tentukan periode minggu dalam bulan berjalan
    $startOfMonth = $currentMonth->copy()->startOfMonth();
    $endOfMonth   = $currentMonth->copy()->endOfMonth();
    $periodWeeks  = CarbonPeriod::create($startOfMonth, '1 week', $endOfMonth);

    $weeks = [];
    foreach ($periodWeeks as $date) {
        // Ambil week number dari tanggal tersebut (ini week of year)
        $weeks[] = $date->week;
    }
    // Pastikan minggu akhir bulan juga dimasukkan jika belum ada
    if (!in_array($endOfMonth->week, $weeks)) {
        $weeks[] = $endOfMonth->week;
    }
    sort($weeks);

    $weeklyData = [];
    $counter = 1;
    foreach ($weeks as $w) {
        $weeklyData[] = [
            'week'            => 'Week ' . $counter,
            'donation_amount' => isset($donationAmountByWeek[$w]) ? round($donationAmountByWeek[$w]->donation_amount) : 0,
        ];
        $counter++;
    }

    // ----------------------------
    // 3. Data Harian (untuk minggu berjalan)
    // ----------------------------
    $weekStart = $currentMonth->copy()->startOfWeek();
    $weekEnd   = $currentMonth->copy()->endOfWeek();

    // $donationAmountDay =DonationPayment::whereIn('project_id', $projectIdList)
    //     ->selectRaw('SUM(donation_amount) as donation_amount, DAY(created_at) as day')
    //     ->whereBetween('created_at', [$weekStart, $weekEnd])
    //     ->groupBy('day')
    //     ->get();
        
    $donationAmountDay = DonationPayment::whereIn('project_id', $projectIdList)
        ->selectRaw('SUM(donation_amount) as donation_amount, EXTRACT(DAY FROM created_at) as day')
        ->whereBetween('created_at', [$weekStart, $weekEnd])
        ->groupBy('day')
        ->get();
        
    $donationAmountByDay = $donationAmountDay->keyBy('day');

    $dailyData = [];
    $periodDays = CarbonPeriod::create($weekStart, $weekEnd);
    foreach ($periodDays as $day) {
        $dayNum = $day->day; // nomor hari
        // Dapatkan nama hari (misal: Senin, Selasa, dll.) dengan translatedFormat('l')
        $dayName = $day->translatedFormat('l');
        $dailyData[] = [
            'day'             => $dayName,
            'donation_amount' => isset($donationAmountByDay[$dayNum]) ? round($donationAmountByDay[$dayNum]->donation_amount) : 0,
        ];
    }

    return response()->json([
        'message'                => 'Line Chart Data Successfully Retrieved',
        'donation_amount_monthly'=> $monthlyData,
        'donation_amount_weekly' => $weeklyData,
        'donation_amount_daily'  => $dailyData,
    ], 200);
}
    
}
