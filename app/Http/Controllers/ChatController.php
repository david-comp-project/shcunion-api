<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\GroupChat;
use App\Models\Notification;
use App\Traits\HasFileTrait;
use Illuminate\Http\Request;
use App\Events\SendChatEvent;
use App\Events\DeleteChatEvent;
use App\Models\MessageGroupChat;
use App\Events\NotificationEvent;
use App\Models\MessagePrivateChat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{

    use HasFileTrait;
  
    public function getListPrivateMessage(User $user)
    {
        // Ambil ID user yang sedang login
        $currentUserId = (string) $user->user_id;
    
        // Buat key cache
        $cacheKey = "user_{$currentUserId}_private_list";
    
        // Subquery untuk mendapatkan chat terbaru untuk setiap pasangan unik
        $latestChats = MessagePrivateChat::selectRaw('
            GREATEST(user_id, sender_id) as user_key_1,
            LEAST(user_id, sender_id) as user_key_2,
            MAX(created_at) as latest_time
        ')
        ->where(function ($query) use ($currentUserId) {
            $query->where('user_id', $currentUserId)
                  ->orWhere('sender_id', $currentUserId);
        })
        ->groupByRaw('GREATEST(user_id, sender_id), LEAST(user_id, sender_id)');
    
        $chats = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($latestChats, $currentUserId) {
            // Gabungkan dengan chat terbaru
            return MessagePrivateChat::query()
            ->joinSub($latestChats, 'latest_chats', function ($join) {
                $join->on('message_private_chats.created_at', '=', 'latest_chats.latest_time')
                    ->whereRaw('
                        (GREATEST(message_private_chats.user_id, message_private_chats.sender_id) = latest_chats.user_key_1 AND 
                        LEAST(message_private_chats.user_id, message_private_chats.sender_id) = latest_chats.user_key_2)
                    ');
            })
            ->join('users as sender', function ($join) use ($currentUserId) {
                $join->on('sender.user_id', '=', DB::raw("
                    CASE 
                        WHEN message_private_chats.user_id = '$currentUserId'
                        THEN message_private_chats.sender_id 
                        ELSE message_private_chats.user_id 
                    END
                "));
            })
            ->select(
                'message_private_chats.message_private_chat_id',
                DB::raw("
                    CASE 
                        WHEN message_private_chats.user_id = '$currentUserId'
                        THEN message_private_chats.sender_id 
                        ELSE message_private_chats.user_id 
                    END as sender_id
                "),
                'sender.first_name as sender_name',
                'message_private_chats.private_chat_text as latest_chat',
                'message_private_chats.created_at as latest_time_chat',
                'sender.profile_picture as avatar_url'
            )
            ->orderBy('latest_time_chat', 'desc')
            ->get()
            ->transform(function ($chat) {
                $chat->avatar = app(User::class)->getUrlFile($chat->avatar_url);
                return $chat;
            });

        });
    
        return response()->json([
            'private_chat' => $chats,
            'chat_count' => $chats->count()
        ]);
    }
    



    public function getChatMessage(User $user, $tab) {
        // dd($user);
        if ($tab == 'private-chat'){
            //Mendapatkan Message Private list Terbaru
            $messagePrivateList = $user->messagePrivateChats()
                                        ->orderByDesc('created_at')
                                        ->first(); // Ambil satu pesan terbaru            
            //Mendapatkan sender_id nya
            $latest_sender = $messagePrivateList->sender;

            $latest_sender_id = $latest_sender->user_id;

            $chat_name = $latest_sender->userFullName;

            $chat_avatar = $this->getUrlFile($latest_sender->profile_picture);
        
            // Mendapatkan semua Message Private chat berdasarkan pasangan user_id dan sender_id
            $messagePrivateListChats = MessagePrivateChat::where(function ($query) use ($user, $latest_sender_id) {
                            $query->where('user_id', $user->user_id)
                                ->where('sender_id', $latest_sender_id);
                        })->orWhere(function ($query) use ($user, $latest_sender_id) {
                            $query->where('user_id', $latest_sender_id)
                                ->where('sender_id', $user->user_id);
                        })->orderBy('created_at', 'asc') // Mengurutkan berdasarkan waktu
                        ->get();

            // return $messagePrivateListChats;
            //Modifikasi Response
            $messagePrivateListChats = $messagePrivateListChats->map(function ($message) {
                $imagePath = $message->media_path;
                return [
                    'chat_id' => $message->message_private_chat_id,
                    'sender_id' => $message->sender_id,
                    'sender_name' => $message->sender->fullName,
                    'chat_text' => $message->private_chat_text,
                    // 'image_path' => $imagePath ? Storage::url($imagePath) : null,
                    'image_path' => $this->getUrlFile($imagePath),
                    'chat_send_time' => $message->dateFormat,
                    'avatar' => $this->getUrlFile($message->sender->profile_picture),
                ];
            });

            // return $messagePrivateListChats;


            //Diurutkan Berdasarkan waktu kirim terbaru
            $chats = $messagePrivateListChats->sortBy('chat_send_time')->values();

            $chat_id = $latest_sender_id;
            
            // return $messagePrivateLists;
        }

        else if($tab == 'group-chat'){
            // dd($user);
            $group_chat_id = $user->groupChats->pluck('group_chat_id'); //ambil semua group_chat_id

            if ($group_chat_id->count() == 0) {
                return response()->json([
                    'chats' => null,
                    'message' => 'User does not belong to Any Group Chat.',
                ], 202);
            }

            // dd($user->groupChats);
            // return $group_chat_id;

            $latest_group_activities = DB::table('message_group_chats')
                            ->select('group_chat_id', DB::raw('MAX(updated_at) as last_activity_time'))
                            ->whereIn('group_chat_id', $group_chat_id) // Filter berdasarkan daftar group_chat_id
                            ->groupBy('group_chat_id') // Kelompokkan berdasarkan group_chat_id
                            ->orderBy('last_activity_time', 'asc') // Urutkan berdasarkan waktu terbaru
                            ->first();

            // return $latest_group_activities; 

            // **Tangani jika tidak ada aktivitas sama sekali**
            if (!$latest_group_activities) {
                return response()->json([
                    'chats' => null,
                    'message' => 'No messages found in any Group Chat.',
                ], 202);
            }
            
            $latest_group_id_activities = $latest_group_activities->group_chat_id;


            $chat_id = GroupChat::where('group_chat_id', $latest_group_id_activities)->first();

            $chat_name = $chat_id->group_chat_name;

            $chat_avatar = $this->getUrlFile($chat_id->group_avatar);

            $group_chat_id = MessageGroupChat::latest()
                                            ->where('group_chat_id', $latest_group_id_activities)
                                            ->get()->first()->group_chat_id;
            // return $group_chat_id;
            
    

            $messageGroupChat = MessageGroupChat::where('group_chat_id', $group_chat_id)
                    ->orderBy('updated_at', 'asc')
                    ->get();


            $chats = $messageGroupChat->map(function ($mgc) {
                $imagePath = $mgc->media_path;

                return [
                    'chat_id' => $mgc->message_group_chat_id,
                    'sender_name' => $mgc->sender->fullName,
                    'sender_id' => $mgc->sender->user_id,
                    'chat_text' => $mgc->group_chat_text ?? 'Initial',
                    // 'image_path' => $imagePath ? Storage::url($imagePath) : null,
                    'image_path' => $this->getUrlFile($imagePath),
                    'chat_send_time' => $mgc->dateFormat,
                    'avatar' => $this->getUrlFile($mgc->sender->profile_picture)
                ];
            });

            $chat_id = $latest_group_id_activities;

        }

        return response()->json([
            "chats" => $chats,
            "chat_id" => $chat_id,
            "chat_name" => $chat_name,
            'chat_avatar' => $chat_avatar
        ]);
    }

    public function getListGroupMessage(User $user) {
        $userId = $user->user_id;

        $cacheKey = "user_{$userId}_group_list";
    
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
    
        // Ambil group chats yang dimiliki oleh user dengan relasi messageGroupChat dan chats
        $groupChats = $user->groupChats;   

        // return $groupChats;


        $groupChatsWithLatestChat = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($userId, $groupChats) {
            // Proses setiap group chat untuk mendapatkan chat terbaru
            $groupChatsWithLatestChat = $groupChats->map(function ($groupChat) {
                $latestChat = $groupChat->messageGroupChat->sortByDesc('created_at')->values()->first();
                // Log::info('Latest Chat : ', [$latestChat]);
                return [
                    'group_chat_id' => $groupChat->group_chat_id,
                    'group_chat_name' => $groupChat->group_chat_name, // Ganti sesuai nama kolom untuk nama group chat
                    'latest_chat' => $latestChat->group_chat_text ?? null, // Ganti sesuai kolom untuk isi pesan
                    'latest_time_chat' => $latestChat->created_at ??  null,
                    'avatar' => $this->getUrlFile($groupChat->group_avatar),
                    // 'chats' =>  $groupChat->messageGroupChat->sortByDesc('created_at')->values()
                ];
            })->sortBy('latest_time_chat')->values();

            return $groupChatsWithLatestChat;
            
        });
        
        // Format respons JSON
        return response()->json([
            'user_id' => $user->user_id,
            'group_chats' => $groupChatsWithLatestChat,
            'chats_cout'=> $groupChatsWithLatestChat->count()
        ]);
    }


    //Chat List of Group Chat by Id groupchat
    public function getGroupChatById(User $user, $groupChatId) {

        $messageGroupChats = MessageGroupChat::where('group_chat_id', $groupChatId)->get(); // Ambil semua MessageGroupChat terkait
        
        $groupChat = GroupChat::where('group_chat_id', $groupChatId)->first();


        // Ambil dan format semua chats dalam satu tingkat array
        $chats = $messageGroupChats->map(function ($message) {
                $imagePath = $message->media_path;
                return [
                    "sender_id" => $message->sender_id,
                    "sender_name" => $message->sender->fullName,
                    "chat_id" => $message->message_group_chat_id,
                    "chat_text" => $message->group_chat_text,
                    // "image_path" => $imagePath ? Storage::url($imagePath) : null,
                    "image_path" => $this->getUrlFile($imagePath),
                    "chat_send_time" => $message->dateFormat,
                    // "avatar" => $message->sender->profile_picture,
                    'avatar' => $this->getUrlFile($message->sender->profile_picture),
                ];
        })->sortBy('chat_send_time')->values();

        return response()->json([
            'chats' => $chats,
            'chat_name' => $groupChat->group_chat_name,
            'chat_avatar' => $this->getUrlFile($groupChat->group_avatar)
        ]);
    }

  
    

    public function getPrivateChatById(User $user, User $sender) {

        // return [
        //     $user,
        //     $sender
        // ];
        $senderId = $sender->user_id;
        $receiverId = $user->user_id;

        $receiverName = $sender->userFullName;
        
        
        $messagePrivateChats = MessagePrivateChat::where(function ($query) use ($senderId, $receiverId) {
            $query->where('sender_id', $senderId)
                  ->where('user_id', $receiverId);
        })->orWhere(function ($query) use ($senderId, $receiverId) {
            $query->where('sender_id', $receiverId)
                  ->where('user_id', $senderId);
        })->orderBy('created_at', 'asc') // Mengurutkan pesan berdasarkan waktu
          ->get();

        $chats = $messagePrivateChats->map(function ($message) {
            $imagePath = $message->media_path;
            return [
                "sender_id" => $message->sender_id,
                "sender_name" => $message->sender->fullName,
                "chat_id" => $message->message_private_chat_id,
                "chat_text" => $message->private_chat_text,
                // "image_path" => $imagePath ? Storage::url($imagePath) : null,
                "image_path" => $this->getUrlFile($imagePath),
                "chat_send_time" => $message->dateFormat,
                // "avatar" => $message->sender->profile_picture,
                'avatar' => $this->getUrlFile($message->sender->profile_picture),
            ];
        })->sortBy('chat_send_time')->values();

        return response()->json([
            'chats' => $chats,
            'chat_name' => $receiverName,
            'chat_avatar' => $this->getUrlFile($sender->profile_picture)
        ]);
    }

    public function storePrivateMessage(Request $request, User $user, User $receiver) {
        $sender = $user;
        $user = $receiver;
        // Log::info('user : ', [$user]);

        // $sender = User::where('user_id', $request->input('sender_id'))->get();

        // Log::info('sender : ', [$sender]);

        // dd($user);
    
        // Validasi menggunakan Validator::make()
        $validator = Validator::make($request->all(), [
            'sender_id' => 'required|uuid',
            'private_chat_text' => 'nullable|string|max:500',
            'image' => [
                'nullable', 
                'file', 
                'mimes:jpeg,png,jpg,gif,webp', // Hanya menerima jenis file gambar tertentu
                'max:2048' // Maksimal ukuran file 2MB
            ],
        ]);
    
        // Cek jika validasi gagal
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

    
        // Ambil input yang sudah divalidasi
        $validatedData = $validator->validated();
        
        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            // $fileName = uniqid() .'.'. $file->getClientOriginalExtension();
            // $imagePath = $file->storeAs('uploads/images', $fileName, 'public');

            $imagePath = $this->getPathFile($file, 'uploads/images', 'public');
        }


        // Simpan data ke database
        $messagePrivateChat = MessagePrivateChat::create([
            'user_id' => $user->user_id,
            'sender_id' => $validatedData['sender_id'], 
            'private_chat_text' => $validatedData['private_chat_text'],
            'media_path' => $imagePath,
        ]);

        $notification = Notification::create([
            'notification_title' => 'Pesan Baru!',
            'notification_icon' => 'ui uil-chat',
            'notification_text' => $sender->full_name . ' mengirimkan pesan baru kepada anda',
            'notification_url' => "/dashboard/chat",
            'target_id' => $user->user_id
        ]);
        
        $cacheKeyReceiver = "user_{$user->user_id}_private_list";
        $cacheKeySender = "user_{$sender->user_id}_private_list";

        Cache::forget($cacheKeyReceiver);
        Cache::forget($cacheKeySender);

        broadcast(new NotificationEvent($user->user_id, $notification))->via('pusher');

        broadcast(new SendChatEvent($messagePrivateChat, $user->user_id, null))->via('pusher');

        return response()->json([
            'message' => "Chat Berhasil Disimpan",
            'chat' => $messagePrivateChat
        ], 200);
    }

    public function storeGroupMessage(Request $request, User $user, $groupChatId) {
        // dd($groupChatId);
        $validator = Validator::make($request->all(), [
            'sender_id' => 'required|uuid',
            'group_chat_text' => 'nullable|string|max:500',
            // 'group_chat_id' => 'nullable|string',
            'image' => [
                'nullable', 
                'file', 
                'mimes:jpeg,png,jpg,gif,webp', // Hanya menerima jenis file gambar tertentu
                'max:2048' // Maksimal ukuran file 2MB
            ],
        ]);
    
        // Cek jika validasi gagal
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Ambil input yang sudah divalidasi
        $validatedData = $validator->validated();

        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            // $fileName = uniqid() .'.'. $file->getClientOriginalExtension();
            // $imagePath = $file->storeAs('uploads/images', $fileName, 'public');

            $imagePath = $this->getPathFile($file, 'uploads/images', 'public');
        }


        // Simpan data ke database
        $messageGroupChat = MessageGroupChat::create([
            // 'sender_id' => $validatedData[''],
            'group_chat_id' => $groupChatId,
            'sender_id' => $validatedData['sender_id'], 
            'group_chat_text' => $validatedData['group_chat_text'],
            'media_path' => $imagePath,
        ]);

        $sender = User::find($validatedData['sender_id']);

        $groupChat = GroupChat::find($groupChatId);

        if (!$groupChat) {
            return response()->json([
                'message' => 'Group chat tidak ditemukan.'
            ], 404);
        }
    

        $notification = Notification::create([
            'notification_title' => 'Pesan Baru!',
            'notification_icon' => 'ui uil-chat',
            'notification_text' => $sender->full_name . ' mengirimkan pesan baru di ' . $groupChat->group_chat_name,
            'notification_url' => "/dashboard/chat",
            'notification_section' => 'group',
            'target_id' => $groupChat->group_chat_id
        ]);

        $usersGroupChat = $groupChat->users()
                        ->where('users.user_id', '!=', $sender->user_id) // Exclude sender
                        ->pluck('users.user_id')
                        ->toArray();
                        
        foreach ($usersGroupChat as $userGroup) {
            broadcast(new NotificationEvent($userGroup, $notification))->via('pusher');

        }


        broadcast(new SendChatEvent($messageGroupChat, null, $groupChatId))->via('pusher');


        return response()->json([
            'message' => "Chat Berhasil Disimpan",
            'chat' =>$messageGroupChat
        ], 200);

    }


    public function deletePrivateMessage(User $user, MessagePrivateChat $privateMessage) {
        if ($privateMessage->sender_id !== $user->user_id) {
            return response()->json([
                'message' => 'Chat not found or does not belong to the user.',
            ], 404);
        }
    
        $privateMessage->delete();

        $PrivateMessageId = $privateMessage->message_private_chat_id;


        broadcast(new DeleteChatEvent($user, $PrivateMessageId))->via('pusher');
    
        return response()->json([
            'message' => 'Chat successfully deleted.',
        ], 200);
    }

    public function deleteGroupMessage(User $user, MessageGroupChat $groupMessage) {
        if($groupMessage->sender_id != $user->user_id) {
            return response()->json([
                'message' => 'Chat not found or does not belong to the user.',
            ], 404);
        }


          // Hapus pesan
          $groupMessage->delete();

          $groupMessageId = $groupMessage->message_group_chat_id;

          broadcast(new DeleteChatEvent($user, $groupMessageId))->via('pusher');

    
          return response()->json([
              'message' => 'Chat successfully deleted.',
          ], 200);
          
    }


    public function deleteUserFromGroupChat(User $user, GroupChat $groupChat)
    {
        // Cek apakah user ada di dalam grup
        $isMember = $groupChat->users()->where('users.user_id', $user->user_id)->exists();


        if (!$isMember) {
            return response()->json([
                'message' => 'User is not a member of the group chat.',
            ], 404);
        }

        // Hapus user dari grup chat
        $groupChat->users()->detach($user->user_id);

        return response()->json([
            'message' => 'User successfully removed from the group chat.',
        ], 200);
    }
    
    
}
