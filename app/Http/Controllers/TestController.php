<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\User;
use App\Models\GroupChat;
use Illuminate\Http\Request;
use App\Models\MessageGroupChat;
use App\Models\MessagePrivateChat;
use Illuminate\Support\Facades\DB;

class TestController extends Controller
{
    public function health () {
        return response()->json(['message' => 'sehat'],200);

    }

    public function getStatistic() {
        //total project
        $totalProject = DB::table('projects')->count();
        // total dana terkumpul
        $totalDana = DB::table('donation_payments')->sum('donation_amount');
        // total donatur
        $totalDonatur = DB::table('donation_payments')->count('donatur_id');
        // total relawan
        $totalRelawan = DB::table('volunteer_involvements')->count('volunteer_id');

        return response()->json([
            'statistic' => [
                'total_project' => $totalProject,
                'total_dana' => $totalDana,
                'total_donatur' => $totalDonatur,
                'total_volunteer' => $totalRelawan
            ]
        
        ], 200);
    }

    
    //GroupChat List by user
    public function test(User $user) {
        $userId = $user->user_id;
    
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
    
        // Ambil group chats yang dimiliki oleh user dengan relasi messageGroupChat dan chats
        $groupChats = GroupChat::with(['messageGroupChat.chats'])
            ->whereHas('users', function ($query) use ($userId) {
                $query->where('users.user_id', $userId);
            })
            ->get();

        $messageGroupChat = MessageGroupChat::with('chats')
                ->where('group_chat_id', $groupChats[0]->group_chat_id)
                ->get();

        $chats = $messageGroupChat->flatMap(function ($mgc) {
            $chat = $mgc->chats->map(function ($c) use($mgc) {
                return [
                    'chat_id' => $c->chat_id,
                    'sender_name' => $mgc->sender->first_name,
                    'sender_id' => $mgc->sender->user_id,
                    'chat_text' => $c->chat_text,
                    'chat_send_time' => $c->created_at,
                ];
            });

            return $chat;

        });

    
        // Proses setiap group chat untuk mendapatkan chat terbaru
        $groupChatsWithLatestChat = $groupChats->map(function ($groupChat) {
            $latestChat = $groupChat->messageGroupChat->chats->sortByDesc('created_at')->first();
    
            return [
                'group_chat_id' => $groupChat->group_chat_id,
                'group_chat_name' => $groupChat->group_chat_name, // Ganti sesuai nama kolom untuk nama group chat
                'latest_chat' => $latestChat->chat_text ?? 'Belum ada chat', // Ganti sesuai kolom untuk isi pesan
                'latest_time_chat' => $latestChat->created_at,
                'avatar' => '../../assets/images/avatars/t1.jpg',
                // 'chats' => $groupChat->messageGroupChat->chats
            ];
        });
        
        // Format respons JSON
        return response()->json([
            'user_id' => $user->user_id,
            'group_chats' => $groupChatsWithLatestChat,
            'chats' => $chats
        ]);
    }


    //Chat List of Group Chat by Id groupchat
    public function testId($groupChatId) {
        $messageGroupChats = MessageGroupChat::with('chats')
        ->where('group_chat_id', $groupChatId)
        ->get(); // Ambil semua MessageGroupChat terkait
        

        // Ambil dan format semua chats dalam satu tingkat array
        $chats = $messageGroupChats->flatMap(function ($message) {
            return $message->chats->map(function ($chat) use($message) {
                return [
                    "sender_id" => $message->sender_id,
                    "sender_name" => $message->sender->first_name,
                    "chat_id" => $chat->chat_id,
                    "chat_text" => $chat->chat_text,
                    "chat_send_time" => $chat->created_at,
                    "avatar" => '../../assets/images/avatars/t1.jpg',
                ];
            });
        });

        return response()->json([
            'chats' => $chats,
        ]);
    }

    //List Private Chat
    public function testprivate(User $user) {
        // Ambil data MessagePrivateChat dengan relasi user dan chats
        $privateChats = MessagePrivateChat::with([
            'user',
            'chats' => function ($query) {
                $query->orderBy('created_at', 'desc'); // Urutkan chats berdasarkan waktu terbaru
            },
            'sender'
        ])
        ->where('user_id', $user->user_id)
        ->get();
        

        // return $privateChats;
        $messagePrivateChat = MessagePrivateChat::with('chats')
                                    ->where('sender_id', $privateChats[1]->sender->user_id)
                                    ->get();

        $chats = $messagePrivateChat->flatMap(function ($mpc) {
            $chat = $mpc->chats->map(function ($c) use($mpc) {
                return [
                    'chat_id' => $c->chat_id,
                    'sender_name' => $mpc->sender->first_name,
                    'sender_id' => $mpc->sender->user_id,
                    'chat_text' => $c->chat_text,
                    'chat_send_time' => $c->created_at,
                ];
            
            });
            return $chat;
        });                          
        

        $privateChats = $privateChats->map(function ($privateChat) {
            return [
                'message_private_chat_id' => $privateChat->message_private_chat_id,
                'sender_id'=> $privateChat->sender_id,
                'sender_name' => $privateChat->sender->first_name,
                'latest_chat' => $privateChat->chats->first()->chat_text,
                'latest_time_chat' => $privateChat->chats->first()->created_at,
                'avatar' => "../../assets/images/avatars/t2.jpg",
                // 'chats' => $c
            ];
        });
    
        return response()->json([
            "user_id" => $user->user_id,
            "private_chat" => $privateChats,
            "chats" => $chats
        ]);
    }
    
    public function getBodyChat(User $user, $tab) {

        if($tab == 'group-chat'){
            $group_chat = $user->groupChats->pluck('group_chat_id');

            // return $group_chat;
            $group_chat_id = MessageGroupChat::latest()
                                            ->where('')
                                            ->get()->first()->group_chat_id;
            // return $group_chat_id;
            
            $messageGroupChat = MessageGroupChat::with('chats')
                    ->where('group_chat_id', $group_chat_id)
                    ->get();

            // return $messageGroupChat;
            $chats = $messageGroupChat->flatMap(function ($mgc) {
                    $chat = $mgc->chats->map(function ($c) use($mgc) {
                        return [
                            'chat_id' => $c->chat_id,
                            'sender_name' => $mgc->sender->first_name,
                            'sender_id' => $mgc->sender->user_id,
                            'chat_text' => $c->chat_text,
                            'chat_send_time' => $c->created_at,
                        ];
                    });

                    return $chat;

            });

        }
        elseif ($tab == 'private-chat') {
            $private_chat_id = MessagePrivateChat::latest()->get()->first()->sender_id;


            $messagePrivateChat = MessagePrivateChat::with('chats')
                                        ->where('sender_id', $private_chat_id)
                                        ->get();

            // return $messagePrivateChat;


            $chats = $messagePrivateChat->flatMap(function ($mpc) {
                $chat = $mpc->chats->map(function ($c) use($mpc) {
                    return [
                        'chat_id' => $c->chat_id,
                        'sender_name' => $mpc->sender->first_name,
                        'sender_id' => $mpc->sender->user_id,
                        'chat_text' => $c->chat_text,
                        'chat_send_time' => $c->created_at,
                    ];
                
                });
                return $chat;
            }); 


        }


        return response()->json([
            "chats" => $chats
        ]);
    }
    
}
