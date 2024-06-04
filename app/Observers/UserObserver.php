<?php

namespace App\Observers;

use Illuminate\Support\Facades\Mail;

class UserObserver {
    public function created($user) {
        Mail::send('emails.welcome', ['user'=>$user], function($message) use ($user){
            $message->to($user->email, $user->first_name.' '. $user->last_name)->subject('Welcome to My Awesome App, '.$user->first_name.'!');
        });
    }
}