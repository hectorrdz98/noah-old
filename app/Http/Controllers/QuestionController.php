<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function newQuestion(Request $request)
    {
        $question = $request->question;
    }
}
