<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Token;
use App\UserData;

class ConversationController extends Controller
{

    const AUTOMATA = [
        [ 'greeting' => [1, 1], 'emoji-smile' => [0.1, 0], ],
        [ 'ex-mark' => [0.2, 1], 'q-mark' => [0.7, 0], 'emoji-smile' => [0.1, 1], ]
    ];

    const GREETINGS = [
        'Encantado', 'Un placer conocerte', 'Saludos', 'Un placer'
    ];

    const PRESENTATIONS = [
        'Soy', 'Mi nombre es', 'Me llamo'
    ];

    const IDONTKNOW = [
        'Mmmm... creo que no sé', 'Ni idea... lo siento', '¿Y si hablamos de otra cosa?',
        '(ve hacia otro lado)'
    ];

    public $state = 0;

    public function chatReceived(Request $request)
    {
        $output = '';
        $input = $request->question;

        $tokens = $this->tokenize($input);
        $tokensTagged = $this->tag($tokens);
        // dd($tokensTagged);
        
        #if ($tokensTagged) $output = $this->respond($tokensTagged);
        if ($tokensTagged) $output = $this->newRespond($tokensTagged, $request->session()->getId());
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
                $cont = 0;
                $detectionType = 0;
                $preStr = '';
                while($cont < strlen($match)) {
                    if (preg_match('/[\wáéíóúñ]/', substr($match, $cont, 1)))
                    {
                        if ($detectionType == 0) {
                            if ($preStr != '')
                                array_push($tokens, mb_strtolower($preStr));
                            $detectionType = 1;
                            $preStr = '';
                        }
                    }
                    else {
                        if ($detectionType == 1) {
                            if ($preStr != '')
                                array_push($tokens, mb_strtolower($preStr));
                            $detectionType = 0;
                            $preStr = '';
                        }
                    }
                    $preStr .= substr($match, $cont, 1);
                    $cont++;
                }
                if ($preStr != '') {
                    array_push($tokens, mb_strtolower($preStr));
                }  
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
                else array_push($tokensTagged, $token);
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
                    if (rand(0, 100) / 100 <= $autoRow[0] || ($respond == '' && $actToken == count($tokensTagged) - 1))
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

    public function newRespond($tokensTagged, $session)
    {
        $userData = UserData::where('session', $session)->first();
        $respond = '';
        
        $actIndex = 0;
        $exit = false;

        while ($actIndex < count($tokensTagged)) {

            $actToken = $tokensTagged[$actIndex];
            $respond .= $respond == '' ? '' : ' ';

            if (!is_string($actToken) || $actToken == '¿') {
                if ($actToken == '¿') { $actIndex++; $actToken = $tokensTagged[$actIndex]; }
                switch ($actToken->tag) {

                    case 'greeting': {
                        $respond .= $this->getRandomSyn($actToken);
                        if ($userData) if (array_key_exists('name', $userData->data)) $respond .= ' '.$userData->data['name'];
                        if ($this->nextTokenIs($tokensTagged, $actIndex, 'greeting')) {
                            $actIndex++;
                        }
                        if ($this->nextTokenIs($tokensTagged, $actIndex, 'ex-mark')) {
                            $actToken = $tokensTagged[++$actIndex];
                            $respond .= $this->getRandomSynWProb($actToken, 0.2);
                        }
                    } break;

                    case 'emoji-smile': {
                        $respond .= $this->getRandomSynWProb($actToken, $respond == '' ? 1 : 0.3);
                    } break;

                    case 'word-iam': {
                        if ($this->nextTokenIsString($tokensTagged, $actIndex)) {
                            $name = '';
                            while ($this->nextTokenIsString($tokensTagged, $actIndex)) {
                                $actIndex++;
                                $name .= $name == '' ? '': ' ';
                                $name .= $tokensTagged[$actIndex];
                            }
                            $respond .= $this->getRandom(ConversationController::GREETINGS).' '.$name;
                            $respond .= rand(0, 100) / 100 <= 0.2 ? ' '.$this->getRandom([':)', ':D', ';)']) : '';
                            $userData = $this->pushToUserData($userData, $session, 'name', $name);
                        }
                    } break;

                    case 'q-how': {
                        if ($this->nextTokenIs($tokensTagged, $actIndex, 'a-prop-me')) {
                            $actIndex++;
                            if ($this->nextTokenIsString($tokensTagged, $actIndex)) {
                                $things = '';
                                while ($this->nextTokenIsString($tokensTagged, $actIndex)) {
                                    $actIndex++;
                                    $things .= $things == '' ? '': ' ';
                                    $things .= $tokensTagged[$actIndex];
                                }
                                $respond .= 'Lo siento, no sé tu '.$things;
                            } else {
                                $actToken = $tokensTagged[++$actIndex];
                                switch ($actToken->tag) {
                                    case 'verb-name': {
                                        if ($userData) {
                                            if(array_key_exists('name', $userData->data)) {
                                                $respond .= 'Tú nombre es '.$userData->data['name'];
                                            } else {
                                                $respond .= 'Lo siento, no sé tu nombre';
                                            }
                                        } else {
                                            $respond .= 'Lo siento, no sé tu nombre';
                                        }
                                    } break;
                                    
                                    default: {
                                        $respond .= 'Lo siento, no sé tu '.$actToken->value;
                                    } break;
                                }
                            }
                        } else if ($this->nextTokenIs($tokensTagged, $actIndex, 'a-prop-you')) {
                            $actIndex++;
                            if ($this->nextTokenIsString($tokensTagged, $actIndex)) {
                                $thing = '';
                                while ($this->nextTokenIsString($tokensTagged, $actIndex)) {
                                    $actIndex++;
                                    $thing .= $thing == '' ? '': ' ';
                                    $thing .= $tokensTagged[$actIndex];
                                }
                                $respond .= 'Lo siento, no sé mi '.$thing;
                            } else {
                                $actToken = $tokensTagged[++$actIndex];
                                switch ($actToken->tag) {
                                    case 'verb-name': {
                                        $respond .= $this->getRandom(ConversationController::PRESENTATIONS).' Noah';
                                    } break;
                                    default: {
                                        $respond .= 'Lo siento, no sé mi '.$actToken->value;
                                    } break;
                                }
                            }
                        }
                    } break;

                    case 'q-which': {
                        if ($this->nextTokenIs($tokensTagged, $actIndex, 'art-is')) { $actIndex++; };
                        if ($this->nextTokenIs($tokensTagged, $actIndex, 'a-pert-me')) {
                            $actIndex++;
                            if ($this->nextTokenIsString($tokensTagged, $actIndex)) {
                                $thing = '';
                                while ($this->nextTokenIsString($tokensTagged, $actIndex)) {
                                    $actIndex++;
                                    $thing .= $thing == '' ? '': ' ';
                                    $thing .= $tokensTagged[$actIndex];
                                }
                                $thingGotten = $this->getUserData($userData, $thing);
                                $respond .= $thingGotten ? 'Tu '.$thing.' es '.$thingGotten : 'Lo siento, no sé tu '.$thing;
                            } else {
                                $actToken = $tokensTagged[++$actIndex];
                                switch ($actToken->tag) {
                                    case 'article-name': {
                                        if ($userData) {
                                            if(array_key_exists('name', $userData->data)) {
                                                $respond .= 'Tú nombre es '.$userData->data['name'];
                                            } else {
                                                $respond .= 'Lo siento, no sé tu nombre';
                                            }
                                        } else {
                                            $respond .= 'Lo siento, no sé tu nombre';
                                        }
                                    } break;
                                    
                                    default: {
                                        $respond .= 'Lo siento, no sé tu '.$actToken->value;
                                    } break;
                                }
                            }
                        } else if ($this->nextTokenIs($tokensTagged, $actIndex, 'a-pert-you')) {
                            $actIndex++;
                            if ($this->nextTokenIsString($tokensTagged, $actIndex)) {
                                $thing = '';
                                while ($this->nextTokenIsString($tokensTagged, $actIndex)) {
                                    $actIndex++;
                                    $thing .= $thing == '' ? '': ' ';
                                    $thing .= $tokensTagged[$actIndex];
                                }
                                $respond .= 'Lo siento, no sé mi '.$thing;
                            } else {
                                $actToken = $tokensTagged[++$actIndex];
                                switch ($actToken->tag) {
                                    case 'article-name': {
                                        $respond .= $this->getRandom(ConversationController::PRESENTATIONS).' Noah';
                                    } break;
                                    default: {
                                        $respond .= 'Lo siento, no sé mi '.$actToken->value;
                                    } break;
                                }
                            }
                        }
                    } break;

                    case 'a-pert-me': {
                        if ($this->nextTokenIsString($tokensTagged, $actIndex)) {
                            $thing = '';
                            while ($this->nextTokenIsString($tokensTagged, $actIndex)) {
                                $actIndex++;
                                $thing .= $thing == '' ? '': ' ';
                                $thing .= $tokensTagged[$actIndex];
                            }
                            if ($this->nextTokenIs($tokensTagged, $actIndex, 'art-is')) {
                                $actIndex++;
                                if ($this->nextTokenIs($tokensTagged, $actIndex, 'aso-the')) { $actIndex++; };
                                $value = '';
                                while ($this->nextTokenIsString($tokensTagged, $actIndex)) {
                                    $actIndex++;
                                    $value .= $value == '' ? '': ' ';
                                    $value .= $tokensTagged[$actIndex];
                                }
                                $userData = $this->pushToUserData($userData, $session, $thing, $value);
                                $respond .= 'Lo recordaré... Tu '.$thing.' es '.$value;
                            }
                        }
                    } break;

                    case 'q-what': {
                        $continue = true;
                        while ($continue) {
                            $thing = '';
                            while ($this->nextTokenIsString($tokensTagged, $actIndex)) {
                                $actIndex++;
                                $thing .= $thing == '' ? '': ' ';
                                $thing .= $tokensTagged[$actIndex];
                            }
                            if ($this->nextTokenIs($tokensTagged, $actIndex, 'art-is') ||
                                $this->nextTokenIs($tokensTagged, $actIndex, 'verb-doing') ||
                                $this->nextTokenIs($tokensTagged, $actIndex, 'verb-is')) { 
                                $actIndex++; 
                            };
                            if ($this->nextTokenIs($tokensTagged, $actIndex, 'art-is')) { $actIndex++; };
                            switch ($thing) {
                                case 'hora': {
                                    $respond .= date('H:i');
                                } break;
                                case 'día': {
                                    $respond .= date('d/m/Y');
                                } break;
                                case 'dia': {
                                    $respond .= date('d/m/Y');
                                } break;
                                
                                default:
                                    # code...
                                    break;
                            }
                            $continue = false;
                            if ($this->nextTokenIs($tokensTagged, $actIndex, 'a-and') || $this->nextTokenIs($tokensTagged, $actIndex, 'c-coma')) { 
                                $actIndex++; 
                                $continue = true; 
                                $respond .= ', '; 
                            };
                            $exit = true;
                        }
                        
                    } break;

                }
            }

            if ($exit) break;

            $actIndex++;
        }

        if ($respond == '') {
            $respond .= $this->getRandom(ConversationController::IDONTKNOW);
        }

        return $respond;
    }

    public function pushToUserData($userData, $session, $key, $value)
    {
        if ($userData) {
            $data = $userData->data;
            $data[$key] = $value;
            $userData->update(['data' => $data]);
        } else {
            $userData = UserData::create([
                'session' => $session,
                'data' => [
                    $key => $value
                ]
            ]); 
        }
        return $userData;
    }

    public function getUserData($userData, $key)
    {
        if ($userData) {
            $data = $userData->data;
            return array_key_exists($key, $data) ? $data[$key] : null;
        }
        return null;
    }

    public function getRandom($array)
    {
        $rndIndex = array_rand($array);
        return $array[$rndIndex];
    }

    public function getRandomSyn($token) 
    {
        $rndIndex = array_rand($token->synonyms);
        return $token->synonyms[$rndIndex];
    }

    public function getRandomSynWProb($token, $prob)
    {
        if (rand(0, 100) / 100 <= $prob) {
            $rndIndex = array_rand($token->synonyms);
            return $token->synonyms[$rndIndex];
        }
        return '';
    }

    public function nextTokenIs($tokens, $actIndex, $tag) 
    {
        if ($actIndex + 1 < count($tokens)) {
            if ($this->nextTokenIsString($tokens, $actIndex)) { return false; }
            else { return $tokens[$actIndex + 1]->tag == $tag ? true : false; }
        }
        return false;
    }

    public function nextTokenIsString($tokens, $actIndex) 
    {
        if ($actIndex + 1 < count($tokens)) {
            return is_string($tokens[$actIndex + 1]);
        }
        return false;
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
