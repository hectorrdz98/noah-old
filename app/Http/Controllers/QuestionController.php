<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Thing;

class QuestionController extends Controller
{
    const MODIFIERS = [
        'es', 'que', 'qué', 'cuál', 'cual', 'cuantos', 'cuantas',
        'cuántos', 'cuántas', 'tiene', 'tienen'
    ];

    const PRESENCE_MODIFIERS = [ 'tiene', 'tienen' ];
    const COUNT_MODIFIERS = [ 'cuantos', 'cuantas','cuántos', 'cuántas' ];

    public function newQuestion(Request $request)
    {
        $output = '';

        $question = $request->question;

        $thingNames = $this->getAllThingNames();
        $thingTypes = $this->getAllThingTypes();
        $thingRels = $this->getAllThingRels();

        $words = preg_split('/\s+/', $question);

        $words_cleaned = [];
        foreach ($words as $key => $word) {
            preg_match_all('/[\w|áéíóú]+/', $word, $matches);
            if ($matches)
                foreach ($matches[0] as $key => $match) {
                    $match = mb_strtolower($match);
                    if (!in_array($match, $words_cleaned)) array_push($words_cleaned, $match);
                }
        }
        
        $interNames = array_intersect($words_cleaned, $thingNames);
        $interTypes = array_intersect($words_cleaned, $thingTypes);
        $interRels = array_intersect($words_cleaned, $thingRels);
        $interMod = array_intersect($words_cleaned, QuestionController::MODIFIERS);

        if ($interNames != []) {
            foreach ($interNames as $key => $name) {
                if ($interRels != []) {
                    foreach ($interRels as $key => $rel) {
                        $modded = false;
                        if ($interMod != []) {
                            if (array_intersect(QuestionController::COUNT_MODIFIERS, $interMod) != []) {
                                $count = $this->getThingRelCount($name, $rel);
                                if ($count) {
                                    if ($output != '') $output .= '. ';
                                    $output .= $name.' tiene '.$count.' '.$rel;
                                    $modded = true;
                                }
                            } else if (array_intersect(QuestionController::PRESENCE_MODIFIERS, $interMod) != []) {
                                if ($this->isRelInThing($name, $rel)) {
                                    if ($output != '') $output .= '. ';
                                    $output .= 'Si, '.$name.' tiene '.$rel;
                                    $modded = true;
                                }
                            }
                        } 
                        if (!$modded) {
                            $value = $this->getThingRelValue($name, $rel);
                            if ($value) {
                                if (is_array($value)) {
                                    foreach ($value as $key => $minvalue) {
                                        if ($output != '') $output .= ', ';
                                        $output .= $minvalue;
                                    }
                                } else {
                                    if ($output != '') $output .= ', ';
                                    $output .= $value;
                                }
                            }
                        }
                    }
                    if ($output == '') $output = 'Lo siento, no se';
                } else {
                    if ($interTypes != []) {
                        if ($interMod != []) {
                            if (in_array('es', $interMod)) {
                                foreach ($interTypes as $key => $type) {
                                    if ($this->isTypeInThing($name, $type)) {
                                        if ($output != '') $output .= '. ';
                                        $output .= 'Si, '.$name.' es un '.$type;
                                    } else {
                                        if ($output != '') $output .= '. ';
                                        $output .= 'No, '.$name.' no es un '.$type;
                                    }
                                }
                            }
                        }
                    } else {
                        if ($interMod != []) {
                            if (in_array('es', $interMod)) {
                                if (in_array('que', $interMod) || in_array('qué', $interMod)) {
                                    if ($output != '') $output .= '. ';
                                    $output .= $name.' es un ';
                                    foreach ($this->getThingTypes($name) as $key => $type) {
                                        if ($output != $name.' es un ') $output .= ', ';
                                        $output .= $type;
                                    }
                                } else {
                                    if ($output == '') $output = 'Lo siento, no se';
                                }
                            } else {
                                if ($output == '') $output = 'Lo siento, no se';
                            }
                        } else {
                            if ($output != '') $output .= '. ';
                            $output .= $name.' es un ';
                            foreach ($this->getThingTypes($name) as $key => $type) {
                                if ($output != $name.' es un ') $output .= ', ';
                                $output .= $type;
                            }
                        }
                    }
                }
            }
        } else {
            $output = 'Lo siento, no se';
        }
        
        return $output;
    }

    public function getAllThingNames()
    {
        $thingNames = [];
        foreach (Thing::all() as $key => $thing) {
            array_push($thingNames, $thing->thingName);
        }
        return $thingNames;
    }

    public function getAllThingTypes()
    {
        $thingTypes = [];
        foreach (Thing::all() as $key => $thing) {
            foreach ($thing->thingTypes as $key => $type) {
                if (!in_array($type, $thingTypes)) array_push($thingTypes, $type);
            }
        }
        return $thingTypes;
    }

    public function getAllThingRels()
    {
        $thingRels = [];
        foreach (Thing::all() as $key => $thing) {
            foreach ($thing->thingRels as $rel => $value) {
                if (!in_array($rel, $thingRels)) array_push($thingRels, $rel);
            }
        }
        return $thingRels;
    }

    public function getThingTypes($thingToSearch)
    {
        $thing = Thing::where('thingName', $thingToSearch)->first();
        return $thing->thingTypes;
    }

    public function isTypeInThing($thingToSearch, $typeToSearch)
    {
        $thing = Thing::where('thingName', $thingToSearch)->first();
        if ($thing)
            foreach ($thing->thingTypes as $key => $type) {
                if ($typeToSearch == $type) return true;
            }
        return false;
    }

    public function isRelInThing($thingToSearch, $relToSearch)
    {
        $thing = Thing::where('thingName', $thingToSearch)->first();
        if ($thing)
            foreach ($thing->thingRels as $rel => $value) {
                if ($relToSearch == $rel) return true;
            }
        return false;
    }

    public function getThingRelValue($thingToSearch, $relToSearch)
    {
        $thing = Thing::where('thingName', $thingToSearch)->first();
        if ($thing)
            foreach ($thing->thingRels as $rel => $value) {
                if ($relToSearch == $rel) return $value;
            }
        return null;
    }

    public function getThingRelCount($thingToSearch, $relToSearch)
    {
        $thing = Thing::where('thingName', $thingToSearch)->first();
        if ($thing)
            foreach ($thing->thingRels as $rel => $value) {
                if ($relToSearch == $rel) return count($value);
            }
        return null;
    }

    public function getThingFromRelValue($relToSearch, $valueToSearch)
    {
        foreach (Thing::all() as $key => $thing) {
            foreach ($thing->thingRels as $rel => $value) {
                if (is_array($rel)) {}
                else if ($relToSearch == $rel && $valueToSearch == $value) return $thing;
            }
        }
        return null;
    }

    public function testInput()
    {
        /*$thing = Thing::create([
            'thingName' => 'hidalgo',
            'thingTypes' => [ 'estado' ],
            'thingRels' => [ 
                'capital' => 'pachuca de soto',
                'población' => '2,858 millones',
                'municipios' => [ 
                    'Pachuca de Soto', 'Acatlán', 'Acaxochitlán', 'Actopan', 'Agua Blanca', 'Ajacuba',
                    'Alfajayucan', 'Almoloya', 'Apan', 'Atitalaquia', 'Atlapexco', 'Atotonilco de Tula', 
                    'Atotonilco el Grande', 'Calnali', 'Cardonal', 'Chapantongo', 'Chapulhuacán', 'Chilcuautla',
                    'Cuautepec de Hinojosa', 'El Arenal', 'Eloxochitlan', 'Emiliano Zapata', 'Epazoyucan',
                    'Francisco I. Madero', 'Huasca de Ocampo', 'Huautla', 'Huazalingo', 'Huehuetla', 'Huejutla de Reyes',
                    'Huichapan', 'Ixmiquilpan', 'Jacala de Ledezma', 'Jaltocán', 'Juárez Hidalgo', 'La Misión',
                    'Lolotla', 'Metepec', 'Metztitlán', 'Mineral de la Reforma', 'Mineral del Chico', 'Mineral del Monte',
                    'Mixquiahuala de Juárez', 'Molango', 'Nicolás Flores', 'Nopala de Villagrán', 'Omitlán de Juárez',
                    'Pacula', 'Pisaflores', 'Progreso de Obregón', 'San Agustín Metzquititlán', 'San Agustín Tlaxiaca',
                    'San Bartolo Tutotepec', 'San Felipe Orizatlán', 'San Salvador', 'Santiago de Anaya',
                    'Santiago Tulantepec de Lugo Guerrero', 'Singuilucan', 'Tasquillo', 'Tecozautla', 'Tenango de Doria',
                    'Tepeapulco', 'Tepehuacán de Guerrero', 'Tepeji del Río de Ocampo', 'Tepetitlán', 'Tetepango', 'Tezontepec de Aldama',
                    'Tianguistengo', 'Tizayuca', 'Tlahuelilpan', 'Tlahuiltepa', 'Tlanalapa', 'Tlanchinol', 'Tlaxcoapan', 'Tolcayuca',
                    'Tula de Allende', 'Tulancingo de Bravo', 'Villa de Tezontepec', 'Xochiatipan', 'Xochicoatlán', 'Yahualica',
                    'Zacualtipán de Ángeles', 'Zapotlán de Juárez', 'Zempoala', 'Zimapán'
                ]
            ]
        ]);*/
        /*$thing = Thing::create([
            'thingName' => 'perro',
            'thingTypes' => [ 'animal', 'mamífero', 'inteligente' ],
            'thingRels' => [ 
                'pelaje' => 'una capa de pelos que cubre el cuerpo',
                'cola' => [
                    'recta', 'recta hacia arriba', 'forma de hoz', 'rizada', 'tirabuzón'
                ],
                'inteligencia' => 'la habilidad de un perro de procesar la información que recibe a través de 
                    sus sentidos para aprender, adaptarse y resolver problemas',
                'tipos' => [ 
                    'pastor', 'boyeros', 'boyeros', 'suizos', 'pinscher', 'schnauzer',
                    'molosoides', 'tipo montaña', 'boyeros suizos', 'terriers', 'teckels',
                    'spitz', 'primitivo', 'sabueso', 'de rastreo', 'de muestra', 'cobradores',
                    'levantadores de caza', 'de agua', 'de compañía', 'lebreles'
                ]
            ]
        ]);
        dd($thing);*/
        //return Thing::first();
    }
}
