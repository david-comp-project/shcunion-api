<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

use App\Http\Controllers\ChatController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AgendaController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VolunteerController;
use App\Http\Controllers\GeneralDataController;
use App\Http\Requests\StoreRegistrationRequest;
use App\Http\Controllers\ContributionController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ProjectLampiranController;
use App\Http\Controllers\ProjectLocationController;
use App\Http\Controllers\ProjectTimelineController;
use App\Http\Controllers\ProjectEvaluationController;

Route::prefix('v1')->group(function () {
    Route::get('/', function () {
        return response()->json([
            'message' => 'Welcome to the API',
            'version' => '1.0.0'
        ]);
    });

    Route::post('/sign-up',[RegistrationController::class, 'signup']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/auth/{provider}/redirect', [AuthController::class, 'redirectToProvider']);
    Route::get('/auth/{provider}/callback', [AuthController::class, 'handleProviderCallback']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/password/reset', [AuthController::class, 'resetPassword']);
    Route::post('/password/send-reset', [AuthController::class, 'sendResetPassword']);
    Route::get('/up-health', [TestController::class, 'health']);
    //statistic
    Route::get('/statistic', [TestController::class, 'getStatistic']);



    Route::middleware(['auth.api'])->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::middleware(['role:active'])->group(function () {
            Route::post('/email/verification', [AuthController::class, 'sendVerify'])->middleware( 'throttle:6,1')->name('verification.send');
            Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verify'])->name('verification.verify');
        });
        Route::post('/password/change', [AuthController::class, 'changePassword']);
    
        //Dashboard
        Route::prefix('dashboard/user/{user}')->group(function () {
            Route::get('/statistic', [DashboardController::class, 'getStatisticCard']);
            Route::get('/donatur', [DashboardController::class, 'getTopDonatur']);
            Route::get('/project/performance', [DashboardController::class, 'getProjectBestPerformance']);
            Route::get('/donation/statistic', [DashboardController::class, 'getLineChartData']);
            Route::get('/socialmedia/statistic', [DashboardController::class, 'getPieChartData']);
    
    
        });

        //Profile
        Route::prefix('user')->group(function () {
            //Route Tanpa middleware
            Route::get('/{user}/profile', [UserController::class, 'getUserProfile']);
            Route::get('/{user}/notification', [UserController::class, 'getNotificationUserList']);
            Route::put('/{user}/notification/{notification}', [UserController::class, 'updateNotification']);
            Route::get('/{user}/agenda/list', [AgendaController::class, 'getAllAgendas']);
            Route::get('/{user}/contribution/list', [ContributionController::class, 'getContributionList']);

            Route::middleware(['role:admin|verified|active|reported'])->group(function () {
                Route::put('/{user}/profile', [UserController::class, 'updateUserProfile']);
                Route::post('/{user}/agenda', [AgendaController::class, 'storeAgenda']);

            });

            Route::middleware(['role:admin'])->group(function () {
                Route::post('/{user}/suspend', [UserController::class, 'suspendedUser']);
                Route::get('/{user}/reportcase/list', [UserController::class,'getReportedCase']);
                Route::get('/{user}/reportcase/{reportCase}/detail', [UserController::class,'getReportDetail']); 
                Route::put('/{user}/reportcase/update', [UserController::class, 'updateReportCase']);
                Route::put('/{user}/verify', [UserController::class, 'userVerify']);
            });

            Route::middleware(['role:admin|verified|active'])->group(function () {
                Route::post('/{sender}/report', [UserController::class, 'userReport']);

            });

            Route::prefix('{user}/chat')->group(function () {
                Route::get('/groupchat/list', [ChatController::class, 'getListGroupMessage']);
                Route::get('/groupchat/{groupChatId}/chat', [ChatController::class, 'getGroupChatById']);
                Route::get('/sender/{sender}', [ChatController::class, 'getPrivateChatById']);
                Route::get('/private/list', [ChatController::class, 'getListPrivateMessage']);
                Route::get('/{tab}/initial', [ChatController::class, 'getChatMessage']);

               // Middleware untuk role tertentu di chat
                Route::middleware(['role:admin|verified|active|reported'])->group(function () {
                    Route::post('/private/{receiver}', [ChatController::class, 'storePrivateMessage']);
                    Route::post('/groupchat/{groupChatId}', [ChatController::class, 'storeGroupMessage']);
                });

                Route::middleware(['role:admin|verified|active'])->group(function () {
                    Route::delete('/private/message/{privateMessage}', [ChatController::class, 'deletePrivateMessage']);
                    Route::delete('/groupchat/message/{groupMessage}', [ChatController::class, 'deleteGroupMessage']);
                    Route::delete('/{groupChat}/leave', [ChatController::class, 'deleteUserFromGroupChat']);
                });
            });
       
        });
        
        Route::prefix('account/manage/account')->group(function () {
            Route::middleware(['role:admin'])->group(function () {
                Route::get('/list', [UserController::class, 'getAllUsers']);
                Route::get('/statistic', [UserController::class,'getUserStatistic']);
                Route::delete('/{user}', [UserController::class, 'deleteUser']);
            });
        });

        //Evaluasi
        Route::prefix('project')->group(function () {

            Route::middleware(['auth.project'])->group(function () {
                    Route::get('/{project}/evaluation/list', [ProjectEvaluationController::class,'getProjectEvaluation']);
                    Route::put('/{project}/location', [ProjectLocationController::class,'updateProjectLocation']);
                    Route::get('/{project}/detail', [ProjectController::class,'getProjectDetailId']);
           
                    Route::get('/{project}/donatur/list', [DonationController::class, 'getDonaturList']);
                    Route::get('/{project}/volunteer/list', [VolunteerController::class, 'getVolunteers']);


                    Route::middleware(['role:admin|verified|active|reported'])->group(function () {
                        Route::put('/{project}/user/{user}/evaluation', [ProjectEvaluationController::class,'updateProjectEvaluation']);
                        Route::post('/{project}/creator/detail', [ProjectController::class, 'storeProjectCreator']);
                        Route::post('/{project}/beneficiary/detail', [ProjectController::class, 'storeProjectBeneficial']);
                        Route::post('/{project}/lampiran', [ProjectLampiranController::class,'storeProjectLampiran']);
                        Route::post('/{project}/volunteer/store', [VolunteerController::class, 'storeVolunteer']);
    
                    Route::middleware(['role:admin|verified|active'])->group(function () {
                        Route::put('/{project}/detail', [ProjectController::class,'updateProjectDetail']);
                        Route::put('/{project}/volunteer/{volunteerInvolvement}/detail/status', [VolunteerController::class, 'updateStatusVolunteer']);
                        Route::delete('/{project}/lampiran/{projectLampiran}', [ProjectLampiranController::class,'deleteProjectLampiran']);
                        Route::put('/{project}/lampiran/{projectLampiran}', [ProjectLampiranController::class,'updateProjectLampiran']);
                        Route::put('/{project}/timeline', [ProjectTimelineController::class,'updateProjectTimeline']);
                        Route::post('/{project}/timeline', [ProjectTimelineController::class,'storeProjectTimeline']);
                        Route::post('/{project}/timeline/{projectTimeline}/detail', [ProjectTimelineController::class,'storeProjectTimelineDetail']);
                        Route::delete('/{project}/timeline/{projectTimeline}', [ProjectTimelineController::class,'deleteProjectTimeline']);
                        Route::delete('/{project}/timeline/{projectTimeline}/detail/{projectTimelineDetail}', [ProjectTimelineController::class,'deleteProjectTimelineDetail']);
                    });
    
                    Route::middleware('role:admin')->group(function () {
                        Route::post('/{project}/evaluation', [ProjectEvaluationController::class,'storeProjectEvaluation']);
                        Route::put('/{project}/status', [ProjectController::class, 'updateStatusProject']);
            
                        Route::delete('/{project}/delete', [ProjectController::class, 'deleteProjectId']);
                        Route::get('/{project}/donation/withdrawal', [DonationController::class, 'getWithdrawalDonation']);
                        Route::post('/{project}/donation/withdrawal', [DonationController::class, 'storeWithdrawalDonation']);
                        Route::put('/{project}/donation/withdrawal/{withdrawalDonation}/status', [DonationController::class, 'updatewithdrawalDonationStatus']);
    
                    });
                });

            });

            Route::middleware('role:admin')->group(function () {
                Route::get('/manage/project/list', [ProjectController::class,'getAllProjects']);
                Route::get('/manage/project/statistic', [ProjectController::class,'getStatistic']);
                Route::get('/{project}/manage/detail', [ProjectController::class,'getProjectCreateDetail']);
                Route::put('/{projectEvaluation}/evaluation/status', [ProjectEvaluationController::class,'updateStatusProjectEvaluation']);   
                Route::delete('/{projectEvaluation}/evaluation', [ProjectEvaluationController::class,'deleteProjectEvaluation']);    

            });

            Route::middleware(['role:admin|verified|active|reported'])->group(function () {
                Route::post('/create/detail',[ProjectController::class, 'storeProjectDetail']);
                Route::post('/{project}/comment', [ProjectController::class, 'storeProjectComment']);

            });

            Route::get('/{project}/spreader/{user}/share/{socialMedia}', [ProjectController::class, 'projectSocialMediaShare']);
            Route::post('/{project}/donation/snap', [DonationController::class, 'getSnapMidtrans']);
            Route::post('/{project}/donation/{donationPayment}/snap/callback', [DonationController::class, 'handleSnapCallback']); //Menyimpan data di database
            Route::get('/{project}/lampiran/list', [ProjectLampiranController::class,'getProjectLampiran']);
            Route::get('/{project}/timeline', [ProjectTimelineController::class,'getProjectTimeline']);
            Route::get('/{project}/location', [ProjectLocationController::class,'getProjectLocation']);
            Route::get('/{project}/comment', [ProjectController::class,'getCommentProjectId']); 
            Route::get('/list', [ProjectController::class, 'getProjectsList']);
            Route::get('/public/list', [ProjectController::class, 'getPublicProjectList'])->withoutMiddleware('auth.api');
            Route::get('/{project}/public/detail', [ProjectController::class,'getPublicProjectDetailId']);

        });
        
        //General
        Route::prefix('general')->group(function () {
            Route::get('/project/icon/list', [GeneralDataController::class, 'getIconList']);
            Route::get('/project/tag/list', [GeneralDataController::class, 'getProjectTagList']);
            Route::get('/project/status/list', [GeneralDataController::class, 'getProjectStatusList']);
            Route::get('/project/evaluation/status/list', [GeneralDataController::class, 'getProjectEvaluationStatusList']);
            Route::get('/user/status/list', [GeneralDataController::class, 'getUserStatusList']);
            Route::get('/project/donation/status/list', [GeneralDataController::class, 'getDonationTransactionStatusList']);
            Route::get('/project/withdrawal/status/list', [GeneralDataController::class, 'getWithdrawalStatus']);

        });
 
        //Location
        Route::get('/provinsi/list', [ProjectLocationController::class, 'getProvinsi'])->withoutMiddleware('auth.api');
        Route::get('/provinsi/{provinsi}/kabupaten/list', [ProjectLocationController::class, 'getKabupatenByProvinsi'])->withoutMiddleware('auth.api');
        Route::get('/kabupaten/{kabupaten}/kecamatan/list', [ProjectLocationController::class, 'getKecamatanByKabupaten'])->withoutMiddleware('auth.api');
        Route::get('/kecamatan/{kecamatan}/desa/list', [ProjectLocationController::class, 'getDesaByKecamatan'])->withoutMiddleware('auth.api');


        //Donation
        // Route::post('/midtrans/webhook', [DonationController::class, 'handleWebhook']);

        //Core API
        // Route::post('/test-donation/check-payment', [DonationController::class, 'checkPayment']);
        // Route::get('/test-donation/status/{donationPayment}',[DonationController::class, 'checkStatusPayment']);

        
    });

});
