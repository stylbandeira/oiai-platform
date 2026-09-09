<?php

namespace App\Actions\Auth;

class NoticeAction
{
    public function execute()
    {
        return response([
            'message' => 'Email verification required',
            'verified' => false,
        ], 403);
    }
}
