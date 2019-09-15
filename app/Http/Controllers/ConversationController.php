<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Token;

class ConversationController extends Controller
{

    const AUTOMATA = [
        [ 'greeting' => [1, 1], 'emoji-smile' => [0.1, 0], ],
        [ 'ex-mark' => [0.2, 1], 'q-mark' => [0.7, 0], 'emoji-smile' => [0.1, 1], ]
    ];

    public $state = 0;

    public function chatReceived(Request $request)
    {
        $output = '';
        $input = $request->question;

        $tokens = $this->tokenize($input);
        $tokensTagged = $this->tag($tokens);
        
        
        if ($tokensTagged) $output = $this->respond($tokensTagged);
        else $output = 'Lo siento, no entendí';

        return $output;
    }

    public function tokenize($sentence) 
    {
        $tokens = [];

        mb_internal_encoding('UTF-8');

        $matches = preg_split('/\s+/', $sentence);
        
        if ($matches)
            foreach ($matches as $key => $match) {
                preg_match_all('/[\w|áéíóú]{2,}/', $match, $insideWords);
                if ($insideWords[0] != []) {
                    $newString = $match;
                    foreach ($insideWords as $key => $word) {
                        array_push($tokens, mb_strtolower($word[0]));
                        $newString = str_replace($word[0], '', $newString);
                    }
                    if ($newString != '') array_push($tokens, mb_strtolower($newString));
                } else array_push($tokens, mb_strtolower($match));
            }

        return $tokens;
    }

    public function tag($tokens) 
    {
        $tokensTagged = [];
        foreach ($tokens as $key => $token) {
            $dbToken = Token::where('value', $token)->first();
            if ($dbToken) {
                array_push($tokensTagged, $dbToken);
            } else {
                $dbToken = Token::where('synonyms', 'LIKE', '%'.$token.'%')->first();
                if ($dbToken) array_push($tokensTagged, $dbToken);
            }
        }
        return $tokensTagged;
    }

    public function respond($tokensTagged)
    {
        $respond = '';

        $actToken = 0;

        while ($actToken < count($tokensTagged)) {
            $flag = false;
            $actualStateRow = ConversationController::AUTOMATA[$this->state];

            foreach ($actualStateRow as $autoTag => $autoRow) {
                if ($tokensTagged[$actToken]->tag == $autoTag) {
                    if (rand(0, 1) <= $autoRow[0] || ($respond == '' && $actToken == count($tokensTagged) - 1))
                        if ($tokensTagged[$actToken]->synonyms) {
                            $rndIndex = array_rand($tokensTagged[$actToken]->synonyms);
                            if ($respond != '' && strlen($tokensTagged[$actToken]->synonyms[$rndIndex]) > 1) $respond .= ' ';
                            $respond .= $tokensTagged[$actToken]->synonyms[$rndIndex];
                        }
                    $this->state = $autoRow[1];
                    $actToken++;
                    $flag = true;
                    break;
                }
            }

            if (!$flag) {
                $respond = 'Lo siento, no entendí';
                break;
            }
        }

        return $respond;
    }

    public function addTokens() 
    {
        $tokens = Token::orderBy('tag', 'asc')->get();
        return view('addTokens', ['tokens' => $tokens]);
    }

    public function newToken(Request $request)
    {
        if (!$request->input('tag') || !$request->input('value'))
            return back()->with(['error' => 'Data missing']);
        $synonyms = $request->input('synonyms') ? preg_split('/#/', $request->synonyms) : [];
        $token = Token::create([
            'value' => $request->value,
            'tag' => $request->tag,
            'synonyms' => $synonyms
        ]);

        return back()->with(['alert' => 'Token "'.$token->value.'" created']);
    }

}
