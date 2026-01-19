<?php

namespace App\Http\Controllers\Dashboard\chating;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Storage;

class ChatPageController extends Controller
{
    public function index()
    {
      $user = Auth::user();

      // محاولة فتح محادثة محددة إن تم تمريرها من الإشعار
      $requestedId = request('conversation');
      $conversation = null;

      if ($requestedId) {
          $conversation = Conversation::where('id', $requestedId)
              ->whereHas('users', function ($q) use ($user) {
                  $q->where('users.id', $user->id);
              })
              ->first();
      }

      // في حال لم يتم تمرير معرف أو لم يكن المستخدم ضمنها، اختر أول محادثة خاصة تخص المستخدم
      if (!$conversation) {
          $conversation = Conversation::where('type', 'private')
              ->whereHas('users', function ($q) use ($user) {
                  $q->where('users.id', $user->id);
              })
              ->first();
      }

      $otherUser = $conversation ? $conversation->users->where('id', '!=', $user->id)->first() : null;
      return view('content.dashboard.chating.index', compact('user', 'otherUser', 'conversation'));

    }
    
}
