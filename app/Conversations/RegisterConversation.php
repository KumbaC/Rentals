<?php

namespace App\Conversations;

use App\Models\Contract;
use App\Models\Property;
use App\Models\departure_time;
use App\Models\TypeCurrency;
use App\Models\working_time;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;
use Illuminate\Support\Facades\View;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\BotMan;
use Carbon\Carbon;
use BotMan\BotMan\Telegram\TelegramDriver;
use Barryvdh\DomPDF\Facade\Pdf;
use BotMan\BotMan\Messages\Attachments\File;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Api;
use Luecano\NumeroALetras\NumeroALetras;

class RegisterConversation extends Conversation


{
    /**
     * Start the conversation.
     *
     * @return mixed
     *
     */

     public function run()
    {
        $this->start();
    }

    protected $userData = [];
    protected $propiedadData = [];


    public function start()
    {
        $this->say('Hello! I am the IconicMind bot to create contracts .');

        $this->showMenu();
    }

    private function showMenu()
    {
        $user = [];
        $question = Question::create('¿Qué deseas hacer?')
            ->fallback('Lo siento, no puedo ayudarte con eso')
            ->callbackId('menu')
            ->addButtons([
                Button::create('Create New Properties')->value('registerproperties'),
                Button::create('Create New Contract')->value('register'),
            ]);

        $this->ask($question, function (Answer $answer) use ($user) {
            $res = $answer->getValue();
            switch ($res){
                case 'register':
                    return $this->register($user);
                    break;
                case 'registerproperties':
                    return $this->registerProperties($user);
                    break;
                default:
                    return $this->repeat('No puedo entenderte, ¿puedes intentarlo de nuevo?');
            }
        });
    }

    private function registerProperties($user)
    {
        $this->askForNewProperties($user);
    }

    private function askForNewProperties($user)
    {
        $this->ask('Please select the property name', function (Answer $answer) use ($user) {
            $start = $answer->getText();
            $this->propiedadData['name'] = $start;
            
            $question = Question::create("You entered the property name {$start}. Is this correct?")
            ->fallback('Unable to ask question')
            ->callbackId('ask_confirmation')
            ->addButtons([
                Button::create('Yes')->value('yes'),
                Button::create('No')->value('no'),
            ]);
    
        $this->ask($question, function (Answer $answer) use ($user) {
            if ($answer->isInteractiveMessageReply()) {
                if ($answer->getValue() === 'yes') {
                    $this->askForPropAddress($user);
                } else {
                    $this->askForNewProperties($user);  // Ask for the start date again
                }
            }
        });
        });
    }

    private function askForPropAddress($user)
    {
        $this->ask('Please write the property address.', function (Answer $answer) use ($user) {
            $start = $answer->getText();
            $this->propiedadData['name'] = $start;
            
            $question = Question::create("You entered the property address {$start}. Is this correct?")
            ->fallback('Unable to ask question')
            ->callbackId('ask_confirmation')
            ->addButtons([
                Button::create('Yes')->value('yes'),
                Button::create('No')->value('no'),
            ]);
    
        $this->ask($question, function (Answer $answer) use ($user) {
            if ($answer->isInteractiveMessageReply()) {
                if ($answer->getValue() === 'yes') {
                    $this->askForRooms($user);
                } else {
                    $this->askForNewProperties($user);  // Ask for the start date again
                }
            }
          });
        });
    }

    private function askForRooms($user)
    {
        $this->ask('Please write the description of the room.', function (Answer $answer) use ($user) {
            $start = $answer->getText();
            $this->propiedadData['description'] = $start;
            
            $question = Question::create("You entered the property address {$start}. Is this correct?")
            ->fallback('Unable to ask question')
            ->callbackId('ask_confirmation')
            ->addButtons([
                Button::create('Yes')->value('yes'),
                Button::create('No')->value('no'),
            ]);
    
        $this->ask($question, function (Answer $answer) use ($user) {
            if ($answer->isInteractiveMessageReply()) {
                if ($answer->getValue() === 'yes') {
                    $this->askForRooms($user);
                } else {
                    $this->askForNewProperties($user);  // Ask for the start date again
                }
            }
          });
        });
    }

   private function register($user)
    {
        $this->askForProperties($user);
    }

    private function askForProperties($user)
    {
        $positions = Property::all();
        $buttons = [];
        foreach ($positions as $position) {
            $buttons[] = Button::create($position->name)->value($position->id);
        }

        $question = Question::create('Properties')
            ->fallback('Lo siento, no puedo ayudarte con eso')
            ->callbackId('menu')
            ->addButtons($buttons);

        $this->ask($question, function (Answer $answer) use ($user) {
            $this->userData['property_id'] = $answer->getValue();
            $this->say('Perfecto!' . $this->userData['property_id']);
            $this->askForCurrency($user);
        });
    }

    private function askForCurrency($user)
    {
        $question = Question::create("The currency type is EURO, do you want to change it?")
            ->fallback('Unable to ask question')
            ->callbackId('ask_confirmation')
            ->addButtons([
                Button::create('Yes')->value('yes'),
                Button::create('No')->value('no'),
            ]);
    
        $this->ask($question, function (Answer $answer) use ($user) {
            if ($answer->isInteractiveMessageReply()) {
                if ($answer->getValue() === 'yes') {
                    $currency = TypeCurrency::all();
                    $buttons = [];
                    foreach ($currency as $curren) {
                        $buttons[] = Button::create($curren->name)->value($curren->id);
                    }
                   

                    $question = Question::create('Type Currency')
                        ->fallback('Lo siento, no puedo ayudarte con eso')
                        ->callbackId('menu')
                        ->addButtons($buttons);

                    $this->ask($question, function (Answer $answer) use ($user) {
                        $this->userData['type_currency_id'] = $answer->getValue();
                        $this->say('The default currency is ' . $answer->getValue());
                        $this->askForAmount($user);
                    });
                } else {
                    $this->userData['type_currency_id'] = 1;
                    $this->askForAmount($user);  // Ask for the start date again
                }
            }
        });

    }

    private function askForAmount($user)
    {
        $this->ask('Please enter the amount:', function (Answer $answer) use  ($user) {
            $amount = $answer->getText();
            if (!preg_match('/^[0-9]+$/', $amount)){
                return $this->repeat('Please enter the amount:');
            }
            $formatter = new NumeroALetras();
            $amount_writen = $formatter->toMoney($amount, 2);
            $this->userData['amount_writen'] = $amount_writen;
            $this->userData['amount'] = $amount;
            $this->askForName($user);
        });
    }

    private function askForName($user)
    {
        $this->ask('Please enter the full name of the Renter', function (Answer $answer) use ($user) {
            $start = $answer->getText();
            $this->userData['tenant_name'] = $start;
            
            $question = Question::create("You entered {$start}. Is this correct?")
            ->fallback('Unable to ask question')
            ->callbackId('ask_confirmation')
            ->addButtons([
                Button::create('Yes')->value('yes'),
                Button::create('No')->value('no'),
            ]);
    
        $this->ask($question, function (Answer $answer) use ($user) {
            if ($answer->isInteractiveMessageReply()) {
                if ($answer->getValue() === 'yes') {
                    $this->askForNationality($user);
                } else {
                    $this->askForName($user);  // Ask for the start date again
                }
            }
        });
        });
    }

    private function askForNationality($user)
    {
        $this->ask('Please enter the nationality of the Renter', function (Answer $answer) use ($user) {
            $start = $answer->getText();
            $this->userData['tenant_nationality'] = $start;
            //$this->askForIdentification($user);

            $question = Question::create("You entered {$start}. Is this correct?")
            ->fallback('Unable to ask question')
            ->callbackId('ask_confirmation')
            ->addButtons([
                Button::create('Yes')->value('yes'),
                Button::create('No')->value('no'),
            ]);
    
        $this->ask($question, function (Answer $answer) use ($user) {
            if ($answer->isInteractiveMessageReply()) {
                if ($answer->getValue() === 'yes') {
                    $this->askForIdentification($user);
                } else {
                    $this->askForNationality($user);  // Ask for the start date again
                }
            }
        });

        });
    }

    private function askForIdentification($user)
    {
        $this->ask('Please enter the identification of the Renter', function (Answer $answer) use ($user) {
            $start = $answer->getText();
            $this->userData['tenant_identification'] = $start;
            //$this->askForStartDate($user);

            $question = Question::create("You entered {$start}. Is this correct?")
            ->fallback('Unable to ask question')
            ->callbackId('ask_confirmation')
            ->addButtons([
                Button::create('Yes')->value('yes'),
                Button::create('No')->value('no'),
            ]);
    
        $this->ask($question, function (Answer $answer) use ($user) {
            if ($answer->isInteractiveMessageReply()) {
                if ($answer->getValue() === 'yes') {
                    $this->askForStartDate($user);
                } else {
                    $this->askForIdentification($user);  // Ask for the start date again
                }
            }
        });
        });
    }

    private function askForStartDate($user)
    {
        $this->ask('Please select the start date (Format: Y.m.d)', function (Answer $answer) use ($user) {
            $start = $answer->getText();
            
            $this->userData['start_date'] = $start;
            //$this->askForCurrency($user);

            $question = Question::create("You entered this date {$start}. Is this correct?")
            ->fallback('Unable to ask question')
            ->callbackId('ask_confirmation')
            ->addButtons([
                Button::create('Yes')->value('yes'),
                Button::create('No')->value('no'),
            ]);
    
        $this->ask($question, function (Answer $answer) use ($user) {
            if ($answer->isInteractiveMessageReply()) {
                if ($answer->getValue() === 'yes') {
                    $this->saveContract($user);
                } else {
                    $this->askForStartDate($user);  // Ask for the start date again
                }
            }
        });

        });
    }

    

    private function saveContract($user)
    {
        
        try {
            $contracto = new Contract();
            $contracto->tenant_name = $this->userData['tenant_name'];
            $contracto->tenant_nationality = $this->userData['tenant_nationality'];
            $contracto->tenant_identification = $this->userData['tenant_identification'];
            $contracto->property_id = $this->userData['property_id'];
            $contracto->type_currency_id = $this->userData['type_currency_id'];
            $contracto->amount = $this->userData['amount'];
            $contracto->amount_writen = $this->userData['amount_writen'];
            $contracto->start_date = $this->userData['start_date'];
            $contracto->save();

            $this->say('Perfecto! Tu contrado ha sido creado. 🎉');

            //$this->userData = [];
            $this->sendPDF();

        } catch (\Exception $e) {
            $this->say('Lo siento, ha ocurrido un error al intentar registrar tu contrato. Por favor, intenta de nuevo más tarde.');
            $this->run();
        }

    }

    private function sendPDF()
    {
            $registro = TypeCurrency::find($this->userData['type_currency_id']);
            $tipomoneda = $registro ? $registro->name : null;

            $propiedad = Property::find($this->userData['property_id']);
            $direccion = $propiedad ? $propiedad->address : null;

            $nueva_fecha = date('j \d\e F \d\e\l Y', strtotime($this->userData['start_date']));
            $seismeses = date('j \d\e F \d\e\l Y', strtotime('+6 months', strtotime($this->userData['start_date'])));
            $mpdf = new \Mpdf\Mpdf(['format' => 'Letter', 'margin_top' => 50]);
            $html = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
                <title></title>
                <meta name="generator" content="LibreOffice 7.6.3.2 (Linux)"/>
                <meta name="author" content="Automated Sender"/>
                <meta name="created" content="2023-12-11T20:08:00"/>
                <meta name="changedby" content="Automated Sender"/>
                <meta name="changed" content="2023-12-14T08:25:00"/>
                <meta name="AppVersion" content="16.0000"/>
                <style type="text/css">
                    @page { size: 8.5in 11in; margin-left: 1in; margin-right: 1in; margin-top: 1in; margin-bottom: 0.5in }
                    p { line-height: 115%; text-align: left; orphans: 2; widows: 2; margin-bottom: 0.1in; direction: ltr; background: transparent }
                </style>
            </head>
            <body lang="en-US" link="#000080" vlink="#800000" dir="ltr"><p align="center" style="line-height: 100%; margin-bottom: 0in">
            <font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES"><b>CONTRATO
            DE ARRENDAMIENTO DE VIVIENDA DE USO PROPIO DE VIVIENDA HABITUAL<br/>
            </b></span></font></font><br/>
            
            </p>
            <p lang="es-ES" style="line-height: 100%; margin-bottom: 0in"><br/>
                
            </p>
            <p align="right" style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">En
            Valencia, '. $nueva_fecha .'</span></font></font></p>
            <p lang="es-ES" style="line-height: 100%; margin-bottom: 0in"><br/>
            
            </p>
            <p lang="es-ES" style="line-height: 100%; margin-bottom: 0in"><br/>
            
            </p>
            <p lang="es-ES" align="justify" style="line-height: 100%; margin-bottom: 0in">
            <br/>
            
            </p>
            <p align="justify" style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES"><b>DE
            UNA PARTE</b></span></font></font><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">:
            Don Greg Scott Mudd, mayor de edad y de nacionalidad estadunidense
            con NIE No. X2062919A y con dirección en Calle Roteros 1, 1, 1,
            46003 de Valencia.  Actúa en calidad de propietario, en adelante, el
            ARRENDADOR.</span></font></font></p>
            <p lang="es-ES" align="justify" style="line-height: 100%; margin-bottom: 0in">
            <br/>
            
            </p>
            <p align="justify" style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES"><b>DE
            OTRA PARTE</b></span></font></font><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">:
            Don/Dña.  '. $this->userData['tenant_name']. ' mayor de edad y de nacionalidad '. $this->userData['tenant_nationality'].'
            con documento de identificación  '. $this->userData['tenant_identification']. ' quien actúa en calidad de
            inquilina/o, en adelante, el ARRENDATARIO.</span></font></font></p>
            <p lang="es-ES" align="justify" style="line-height: 100%; margin-bottom: 0in">
            <br/>
            
            </p>
            <p lang="es-ES" align="justify" style="line-height: 100%; margin-bottom: 0in">
            <br/>
            
            </p>
            <p style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES"><b>INTERVIENEN</b></span></font></font><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">:</span></font></font></p>
            <p lang="es-ES" style="line-height: 100%; margin-bottom: 0in"><br/>
            
            </p>
            <p align="justify" style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">Ambas
            partes en su propio nombre derecho y representación, y reconociendo
            la capacidad legalmente necesaria para el cumplimiento del presente
            contrato de arrendamiento.</span></font></font></p>
            <p lang="es-ES" align="justify" style="line-height: 100%; margin-bottom: 0in">
            <br/>
            
            </p>
            <p lang="es-ES" align="justify" style="line-height: 100%; margin-bottom: 0in">
            <br/>
            
            </p>
            <p style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES"><b>EXPONEN:</b></span></font></font></p>
            <p lang="es-ES" align="justify" style="line-height: 100%; margin-bottom: 0in">
            <br/>
            
            </p>
            <p align="justify" style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES"><b>PRIMERO</b></span></font></font><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">.
            Que el arrendador es propietario del inmueble situado en '. $direccion. ' de Valencia España. </span></font></font>
            </p>
            <p lang="es-ES" align="justify" style="line-height: 100%; margin-bottom: 0in">
            <br/>
            
            </p>
            <p align="justify" style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES"><b>SEGUNDO.
            </b></span></font></font><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">Que
            el ARRENDATARIO reconoce haber examinado la habitación y algunos
            inmuebles que amueblan la vivienda y que es de su conformidad, lo
            considera apto y adecuado para el uso al que lo destina y, se
            compromete a devolverlo en igual estado. </span></font></font>
            </p>
            <p lang="es-ES" align="justify" style="line-height: 100%; margin-bottom: 0in">
            <br/>
            
            </p>
            <p align="justify" style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">Ambas
            partes acuerdan la celebración del presente CONTRATO DE
            ARRENDAMIENTO Y DE USO PROPIO DE VIVIENDA HABITUAL, y ello conforme a
            las siguientes:</span></font></font></p>
            <p lang="es-ES" align="justify" style="line-height: 100%; margin-bottom: 0in">
            <br/>
            
            </p>
            <p align="justify" style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES"><b>CLÁUSULAS:</b></span></font></font></p>
            <p lang="es-ES" align="justify" style="line-height: 100%; margin-bottom: 0in">
            <br/>
            
            </p>
            <p align="justify" style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES"><b>PRIMERA</b></span></font></font><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">:
             El ARRENDADOR cede en arrendamiento al ARRENDATARIO la habitación,
            EL ARRENDATARIO reconoce haber inspeccionado y estar de total
            conformidad.  Recibiendo la posesión del inmueble el día'. $nueva_fecha.  ' mediante la entrega de llaves. El ARRENDATARIO toma
            la responsabilidad desde ese momento, de las condiciones establecidas
            en el presente contrato. </span></font></font>
            </p>
            <p lang="es-ES" align="justify" style="line-height: 100%; margin-bottom: 0in">
            <br/>
            
            </p>
            <p align="justify" style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES"><b>SEGUNDA:
            </b></span></font></font><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">La
            DURACION de este contrato será de 6 meses empezando el '. $nueva_fecha. ' y finalizando '. $seismeses. '</span></font></font><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES"><b>.
            </b></span></font></font>
            </p>
            <p lang="es-ES" align="justify" style="line-height: 100%; margin-bottom: 0in">
            <br/>
            
            </p>
            <p align="justify" style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">En
            la fecha de finalización del contrato, el ARRENDATARIO dejará la
            vivienda en el mismo estado que se entrega, libre de personas y
            objetos personales, haciéndole entrega de las llaves de esta al
            ARRENDADOR.</span></font></font></p>
            <p lang="es-ES" align="justify" style="line-height: 100%; margin-bottom: 0in">
            <br/>
            
            </p>
            <p align="justify" style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES"><b>TERCERA:
            RENTA</b></span></font></font><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">
            Como precio del arrendamiento, se fija una RENTA MENSUAL de
            ' .$this->userData['amount_writen'].  '  '. $tipomoneda. '('.$this->userData['amount'].' </span></font></font><font color="#4d5156"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES"><span style="background: #ffffff"></span></span></font></font></font><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">/Mes).
            Se realizará dentro de los CINCO PRIMEROS DIAS de cada mes
            calendario al ARRENDADOR el cual deberá efectuarle un recibo mes a
            mes como comprobante del pago. </span></font></font>
            </p>
            <p lang="es-ES" align="justify" style="line-height: 100%; margin-bottom: 0in">
            <br/>
            
            </p>
            <p align="justify" style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">Se
            estipula además que el tiempo MINIMO para la toma en arriendo del
            inmueble / Habitación será de </span></font></font><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES"><b>6</b></span></font></font><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">
            MESES CALENDARIO, teniendo esto en cuenta el ARRENDATARIO deberá
            informarle al ARRENDADOR si desea terminar o renovar el contrato con
            un plazo MINIMO de 30 DIAS anteriores a la finalización de este.</span></font></font></p>
            <p lang="es-ES" align="justify" style="line-height: 100%; margin-bottom: 0in">
            <br/>
            
            </p>
            <p align="justify" style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">De
            lo contrario el ARRENDATARIO Deberá pagar el equivalente a DOS MESES
            de alquiler, como indemnización por el incumplimiento del tiempo
            anteriormente pactado.</span></font></font></p>
            <p lang="es-ES" align="justify" style="line-height: 100%; margin-bottom: 0in">
            <br/>
            
            </p>
            <p align="justify" style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES"><b>CUARTA:
            </b></span></font></font><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">GASTOS
            GENERALES</span></font></font><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES"><b>.</b></span></font></font><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">
            Serán de común acuerdo divididos entre los ocupantes de la vivienda
            los gastos por consumo de los suministros de agua y luz, así como
            cualquier otro susceptible que deseen incluir como servicio común
            para la vivienda.</span></font></font></p>
            <p lang="es-ES" align="justify" style="line-height: 100%; margin-bottom: 0in">
            <br/>
            
            </p>
            <p align="justify" style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES"><b>QUINTA</b></span></font></font><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">,
            EL ARRENDATARIO, no podrá alojar animales domésticos sin el permiso
            escrito del ARRENDADOR.</span></font></font></p>
            <p lang="es-ES" align="justify" style="line-height: 100%; margin-bottom: 0in">
            <br/>
            
            </p>
            <p align="justify" style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES"><b>SEXTA,</b></span></font></font><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">
            EL ARRENDATARIO no podrá alojar personas que no se encuentren en el
            contrato, sin autorización por escrito del ARRENDADOR, una vez dada
            la autorización, y pasada una noche, el arrendatario se compromete a
            asumir los gastos del tercero, es decir. Incrementos en los servicios
            del inmueble. </span></font></font>
            </p>
            <p lang="es-ES" align="justify" style="line-height: 100%; margin-bottom: 0in">
            <br/>
            
            </p>
            <p align="justify" style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES"><b>SEPTIMA</b></span></font></font><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">.
            El ARRENDATARIO entrega al ARRENDADOR en concepto de reserva</span></font></font></p>
            <p align="justify" style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">El
            equivalente a UN MES de alquiler es decir  '. $this->userData['amount_writen']. '
            '. $tipomoneda. ' '. $this->userData['amount']. ', en concepto de FIANZA cómo garantía de las
            obligaciones del ARRENDATARIO.</span></font></font></p>
            <p lang="es-ES" align="justify" style="line-height: 100%; margin-bottom: 0in">
            <br/>
            
            </p>
            <p align="justify" style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">Esta
            cantidad queda sujeta a cubrir las posibles responsabilidades en que
            pueda incurrir el ARRENDATARIO con el ARRENDADOR por deterioros que
            se produzcan en el inmueble/habitación salvo los que hayan podido
            acaecer como consecuencia del uso normal, impago de rentas o
            cualquier otra causa derivada de la relación de arrendamiento que
            establece en el presente contrato. </span></font></font>
            </p>
            <p lang="es-ES" align="justify" style="line-height: 100%; margin-bottom: 0in">
            <br/>
            
            </p>
            <p align="justify" style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">La
            FIANZA será devuelta al ARRENDATARIO a la finalización del arriendo
            previa la constatación por parte del ARRENDADOR de que la vivienda y
            los bienes materiales que se entregaron junto con ella, se hayan en
            perfecto estado de conservación y siempre que no concurra la
            responsabilidad expresada en el párrafo anterior.</span></font></font></p>
            <p lang="es-ES" align="justify" style="line-height: 100%; margin-bottom: 0in">
            <br/>
            
            </p>
            <p align="justify" style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">El
            ARRENDADOR dispondrá de un plazo de 20 días aproximadamente desde
            la devolución de las llaves de la vivienda por el ARRENDATARIO para
            la devolución de la fianza previa comprobación del estado de la
            vivienda, sus instalaciones y el estado de liquidación de los
            suministros</span></font></font></p>
            <p lang="es-ES" align="justify" style="line-height: 100%; margin-bottom: 0in">
            <br/>
            
            </p>
            <p align="justify" style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">En
            el caso de incumplimiento de cualquiera de las cláusulas del
            contrato, la fianza no será devuelta.</span></font></font></p>
            <p lang="es-ES" align="justify" style="line-height: 100%; margin-bottom: 0in">
            <br/>
            
            </p>
            <p align="justify" style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES"><b>OCTAVA.</b></span></font></font><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">
            La vivienda y todas las instalaciones son recibidas por el
            arrendatario a su entera satisfacción, obligándose a usarlas
            correctamente de acuerdo con el destino pactado, así como a
            comunicar al arrendador con la mayor brevedad posible, cualquier
            avería o desperfectos que se pudiera ocasionar, tanto en el inmueble
            como en las referidas instalaciones.</span></font></font></p>
            <p align="justify" style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">El
            ARRENDATARIO dispone de 20 días para comunicar cualquier avería que
            encontrara en la vivienda. Haciéndose cargo el ARRENDADOR de su
            reparación.</span></font></font></p>
            <p lang="es-ES" align="justify" style="line-height: 100%; margin-bottom: 0in">
            <br/>
            
            </p>
            <p align="justify" style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES"><b>NOVENA,
            EL ARRENDATARIO</b></span></font></font><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">
            se compromete a permitir la entrada en la vivienda arrendada, previo
            aviso del ARRENDADOR, a éste o cualquier representante suyo,
            apoderado, administrador u operario tanto para todo cuanto guarde
            relación con reparaciones de la vivienda arrendada y generales del
            inmueble del cual forma parte, así como para otros fines
            justificados.</span></font></font></p>
            <p lang="es-ES" align="justify" style="line-height: 100%; margin-bottom: 0in">
            <br/>
            
            </p>
            <p align="justify" style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES"><b>DECIMA,
            CONVIVENCIA.</b></span></font></font><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">
            EL ARRENDATARIO se compromete a cumplir las normas establecidas por
            EL ARRENDADOR tales como:</span></font></font></p>
            <ul>
                <li><p align="justify" style="line-height: 100%; margin-bottom: 0in">
                 <font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">Seguir
                el calendario de limpieza establecido cada mes.</span></font></font></p></li>
                <li><p align="justify" style="line-height: 100%; margin-bottom: 0in">
                <font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">Las
                visitas deben avisarse con anticipación y las que deseen quedarse
                una noche debe solicitar aprobación con 15 días de anticipación
                por escrito, en caso de ser aprobado, el ARRENDATARIO deberá pagar
                un monto de CINCUENTA EUROS (50€) por persona, para ayudas de los
                servicios mensuales que están estipulados.</span></font></font></p></li>
            </ul>
            <p lang="es-ES" align="justify" style="line-height: 100%; margin-bottom: 0in">
            <br/>
            
            </p>
            <p align="justify" style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES"><b>UNDECIMA</b></span></font></font><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">.
            En caso de no devolver la habitación/vivienda en perfectas
            condiciones de uso, tal como es recibido en arrendamiento, el
            ARRENDATARIO se obliga a satisfacer al ARRENDADOR la indemnización
            de los desperfectos existentes, en la cuantía que convengan por el
            profesional, subsistiendo la obligación del ARRENDATARIO de
            satisfacer la renta de la vivienda hasta que tal indemnización se
            haga efectiva.</span></font></font></p>
            <p lang="es-ES" align="justify" style="line-height: 100%; margin-bottom: 0in">
            <br/>
            
            </p>
            <p style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES"><b>DECIMOSEGUNDA</b></span></font></font><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">.
            Basado en el artículo 23 de la Ley de Arrendamientos Urbanos, el
            ARRENDATIO no podrá realizar ningún tipo de obras o modificaciones
            de las instalaciones sin previa autorización por escrita del
            ARRENDADOR.  En caso de que se realizaran algunas modificaciones
            quedaran a beneficio de la vivienda y del ARRENDADOR sin que el
            ARRENDATARIO tenga derecho a indemnización.</span></font></font></p>
            <p lang="es-ES" style="line-height: 100%; margin-bottom: 0in"><br/>
            
            </p>
            <p style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">Se
            prohíbe la realización agujeros y revestimientos, así como, pintar
            interruptores y puertas.</span></font></font></p>
            <p lang="es-ES" style="line-height: 100%; margin-bottom: 0in"><br/>
            
            </p>
            <p style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">El
            ARRENDATARIO debe entregar la vivienda en las mismas condiciones en
            que se le entrega sin variaciones. </span></font></font>
            </p>
            <p lang="es-ES" style="line-height: 100%; margin-bottom: 0in"><br/>
            
            </p>
            <p style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES"><b>DECIMOTERCERA</b></span></font></font><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">.
             EL ARRENDATARIO deberá comunicar a el ARRENDADOR si tiene un cambio
            de número de teléfono. </span></font></font>
            </p>
            <p style="line-height: 100%; margin-bottom: 0in"> 
            </p>
            <p style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES"><b>DECIMOCUARTA</b></span></font></font><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">.
            Don/Dña.  '. $this->userData['tenant_name'] . ', definida/o como &quot;el ARRENDATARIO&quot;,
            es responsable solidariamente de las obligaciones del ARRENDATARIO
            bajo este contrato.<br/>
            </span></font></font><br/>
            
            </p>
            <p style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">Como
            constancia y prueba de conformidad con lo anteriormente estipulado,
            las partes firman:<br/>
            <br/>
            <br/>
            <br/>
            </span></font></font><br/>
            
            </p>
            <p lang="es-ES" style="line-height: 100%; margin-bottom: 0in"><br/>
            
            </p>
            <p lang="es-ES" style="line-height: 100%; margin-bottom: 0in"><br/>
            
            </p>
            <p lang="es-ES" style="line-height: 100%; margin-bottom: 0in"><br/>
            
            </p>
            <p style="line-height: 100%; margin-bottom: 0in"><font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">__________________________________
                          ___________________________________</span></font></font></p>
            <p style="line-height: 100%; margin-bottom: 0in">           <font face="Arial, serif"><font size="2" style="font-size: 11pt"><span lang="es-ES">EL
            ARRENDADOR								EL ARRENDATARIO</span></font></font></p>
            <p lang="es-ES" style="line-height: 100%; margin-bottom: 0in"><br/>
            
            </p>
            <p style="line-height: 100%; margin-bottom: 0in"><br/>
            
            </p>
            <p style="line-height: 100%; margin-bottom: 0in"><br/>
            
            </p>
            <div title="footer"><p style="line-height: 100%; margin-top: 0.46in; margin-bottom: 0in">
                <span style="background: #c0c0c0"><sdfield type=PAGE subtype=RANDOM format=PAGE>4</sdfield></span></p>
                <p style="line-height: 100%; margin-bottom: 0in"><br/>
            
                </p>
            </div>
            </body>
            </html>
            ';

            $mpdf->WriteHTML($html);

            $mpdf->output(public_path('pdf/contract_'. $this->userData['tenant_identification'] .'.pdf'), \Mpdf\Output\Destination::FILE);

            $pdfPath = public_path('pdf/contract_'. $this->userData['tenant_identification'] .'.pdf');
                
            $chatId = $this->bot->getUser()->getId();
            $tgbot = new Api('5693779682:AAFABArGstsmhvum75jmSIFxhy_okjyuVps');

            $tgbot->sendDocument([
                'chat_id' => $chatId,
                'document' => InputFile::create($pdfPath, 'contract.pdf'),
                'caption' => 'Your Contract',
            ]);
            
            $this->userData = [];
            $this->run();
    }

   /*  private function report($user)
    {
        $users = User::with('working')->get();
        //$working = working_time::all();
        $this->say('📊 REPORTE DE TRABAJO Y PAGO 📊');

        $currentDay = Carbon::now()->day;
        $twoDaysAgo = Carbon::now()->subDays(2)->day;
        $yesterday = Carbon::now()->subDays(1)->day;


        foreach ($users as $use) {
            $firstPaymentDay = $use->position->first_payment;
            $secondPaymentDay = $use->position->second_payment;

            foreach ($use->working as $word){
            if ($this->isPaymentDay($currentDay, $twoDaysAgo, $yesterday, $firstPaymentDay) || $this->isPaymentDay($currentDay, $twoDaysAgo, $yesterday, $secondPaymentDay)) {
                $this->sayUserDetails($word);
            }
           }
        }

        $this->say('No hay mas reportes de pago');
        $this->subMenu($user);
    }

    private function isPaymentDay($currentDay, $twoDaysAgo, $yesterday, $paymentDay)
    {
        return in_array($currentDay, [$paymentDay, $paymentDay + 1, $paymentDay + 2]);
    }
 */
    private function sayUserDetails($word)
    {

            $this->say('👤 ' . $word->user->name. ' 👤');
            $this->say('📅 ' . $word->entry_date . ' 📅');
            $this->say('🕐 ' . ($word->centry == 1 ? 'Entro a tiempo' : 'Entro tarde') . ' 🕐');
            $this->say('🕐 ' . 'Hora de almuerzo: ' . $word->lunch_time . ' 🕐');
            $this->say('🕐 ' . 'Hora de regreso del almuerzo: ' . $word->back_lunch . ' 🕐');

            if ($word->break == 1) {
                $this->say('🕐 ' . 'Primer de descanso: ' . $word->break_time . ' 🕐');
                $this->say('🕐 ' . 'Hora de regreso del descanso: ' . $word->back_break . ' 🕐');
            } else {
                $this->say('🕐 ' . 'Hora de descanso: ' . 'No tomó descanso' . ' 🕐');
            }

            if ($word->break_two == 1) {
                $this->say('🕐 ' . 'Segundo descanso: ' . $word->time_break_two . ' 🕐');
                $this->say('🕐 ' . 'Hora de regreso del descanso: ' . $word->back_break_two . ' 🕐');
            } else {
                $this->say('🕐 ' . 'Hora de descanso: ' . 'No tomó descanso' . ' 🕐');
            }

            if ($word->out == null) {
                $this->say('🕐 ' . 'Hora de salida: ' . 'No ha salido' . ' 🕐');
            } else {
                $this->say('🕐 ' . 'Hora de salida: ' . $word->out . ' 🕐');
            }

            $this->say($word->cout == 1 ? '🕐 Salio a tiempo 🕐' : '❌ Salio tarde ❌');

    }





   /*  public function login()
    {
        $this->say('🔒 INICIO DE SESIÓN 🔒');
        $this->bot->typesAndWaits(1);

        $this->ask('Por favor ingresa tu email:', function (Answer $answer) {
            $email = $answer->getText();

            // Validar el correo electrónico
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->say('El correo electrónico es inválido. ❌');
                $this->showMenu();
                return;
            }

            $this->ask('Por favor ingresa tu contraseña:', function (Answer $answer) use ($email) {
                $password = $answer->getText();

                // Validar la contraseña
                if (strlen($password) < 8) {
                    $this->say('La contraseña debe tener al menos 8 caracteres. ❌');
                    $this->showMenu();
                    return;
                }

                if (Auth::attempt(['email' => $email, 'password' => $password])) {
                    $nombre = auth()->user()->name;
                    $user = auth()->user();
                    $this->say('Bienvenido | Welcome ✅'. ' ' .  $nombre);
                    $this->subMenu($user);


                } else {
                    $this->say('Las credenciales son inválidas. ❌');
                    $this->showMenu();
                }

            });

         });
     } */

     /* private function subMenu($user)
     {
        if($user->admin == 1){
            $question = Question::create('¿Qué deseas hacer?')

            ->fallback('Lo siento, no puedo ayudarte con eso')
            ->callbackId('menu')
            ->addButtons([
                Button::create('Payment report | Reporte de pago')->value('report'),
                Button::create('Register user | Registrar usuario')->value('register'),
                Button::create('In | Entrada')->value('in'), //  Primera entrada del día.
            ]);

            $this->ask($question, function (Answer $answer) use ($user) {
                $res = $answer->getValue();
                switch ($res){
                    case 'report':
                        return $this->report($user);
                    break;

                    case 'register':
                        return $this->register($user);
                    break;

                    case 'in':
                         return $this->in($user);

                        break;
                    default:
                        return $this->repeat('No puedo entenderte, ¿puedes intentarlo de nuevo?');
                }
              });
            }else{
                $question = Question::create('¿Qué deseas hacer?')

                ->fallback('Lo siento, no puedo ayudarte con eso')
                ->callbackId('menu')
                ->addButtons([
                    Button::create('createNewContract')->value('in'), //  Primera entrada del día.
                ]);

                $this->ask($question, function (Answer $answer) use ($user) {
                    $res = $answer->getValue();
                    switch ($res){
                        case 'in':

                                $this->say('¡Bienvenido al trabajo, ' . $user->name . '!');

                                $working = new working_time();
                                $working->user_id = $user->id;
                                $working->entry_date = Carbon::now()->format('y/m/d H:i:s');
                                $working->save();
                                $id_working = $working->id;
                                $this->say('Entrada al trabajo registrada con éxito. ✅');

                                $this->showPostmenu($user, $id_working);

                            break;
                        default:
                            return $this->repeat('No puedo entenderte, ¿puedes intentarlo de nuevo?');
                    }
                  });
                }

      }

    private function showPostmenu($user, $id_working)
    {


        $question = Question::create('¿Qué deseas hacer?')
        ->fallback('Lo siento, no puedo ayudarte con eso')
        ->callbackId('menu')
        ->addButtons([
            Button::create('Break | Descanso')->value('break'),  //  Descanso de 15 minutos. Los empleados Ɵenen 2 descansos diarios de 15 minutos obligatorios.
            Button::create('Lunch | Comer')->value('lunch'), // Salida para comer.
            Button::create('Out   | Salida')->value('out')    // Salida de la oficina.
        ]);

    $this->ask($question, function (Answer $answer) use ($user, $id_working){
        $res = $answer->getValue();
        switch ($res){
            case 'break':
                return $this->break($user, $id_working);
                break;
            case 'lunch':
                return $this->lunch($user, $id_working);
                break;
            case 'out':
                return $this->out($user, $id_working);
                break;
            default:
                return $this->repeat('No puedo entenderte, ¿puedes intentarlo de nuevo?');
        }
      });



}


    private function in($user){

        $this->say('¡Bienvenido al trabajo, ' . $user->name . '!');
        $this->say('Recuerda que tu hora de entrada es ' . $user->position->start_time . ' ' .  'Bienvenido. 👋');

        $working = new working_time();
        $working->user_id = $user->id;
        $working->entry_date = Carbon::now()->format('y/m/d H:i:s');
        $working->save();

        $id_working = $working->id;
        $workings = working_time::find($id_working);

        if($workings->entry_date <= $user->position->start_time ){
            $workings->centry = 1;
            $workings->save();

            $this->say('Entraste a tiempo al trabajo. ✅');
         }else if($workings->entry_date > $user->position->start_time){
            $workings->centry = 0;
            $workings->save();

            $this->say('Entraste tarde al trabajo. ❌');
          }


        $this->say('Entrada al trabajo registrada con éxito. ✅');

        $this->showPostmenu($user, $id_working);

    }



    public function break($user, $id_working)
    {

    $working = working_time::find($id_working);

    if($working->break == 1 && $working->break_two == 1){
        $this->say('Ya tomaste tus dos breaks, si necesitas descansar toma tu lunch'. ' '. '👋');
        $this->showPostmenu($user, $id_working);

    }else{
        $this->say('¡El break solo dura 15 minutos, procura regresar antes de tiempo.! 👋');
        if($working->break == 1){
            $working->break_two = 1;
            $working->time_break_two = Carbon::now()->format('H:i:s');
            $working->save();
        }else{
            $working->break = 1;
            $working->break_time = Carbon::now()->format('H:i:s');
            $working->save();
        }

        $question = Question::create('¿Qué deseas hacer?')
        ->fallback('Lo siento, no puedo ayudarte con eso')
        ->callbackId('menu')
        ->addButtons([
            Button::create('Back | Regresar del descanso')->value('back_break'),  //  Descanso de 15 minutos. Los empleados Ɵenen 2 descansos diarios de 15 minutos obligatorios.
        ]);
        $this->ask($question, function (Answer $answer) use ($user, $id_working){
            $res = $answer->getValue();
            switch ($res){
                case 'back_break':
                    return $this->back_break($user, $id_working);
                    break;
                default:
                    return $this->repeat('No puedo entenderte, ¿puedes intentarlo de nuevo?');
            }
          });

        }

        $this->bot->typesAndWaits(1);
    }

    public function back_break($user, $id_working)
    {
        $this->say('¡Hola ' . $user->name .  ' '. 'regresas del break! 👋');

        $working = working_time::find($id_working);
        if($working->break == 1 && $working->back_break == null){
            $working->back_break = Carbon::now()->format('H:i:s');
            $working->save();
        }
        else if($working->break_two == 1 && $working->back_break_two == null){
            $working->back_break_two = Carbon::now()->format('H:i:s');
            $working->save();
        }

        $this->showPostmenu($user, $id_working);


        $this->bot->typesAndWaits(1);

    }

    public function lunch($user, $id_working)
    {

        $working = working_time::find($id_working);
        if($working->lunch_time == null){

            $this->say('Buen provecho'. ' ' . $user->name . ' '. '👋');
            $working->lunch_time = Carbon::now()->format('H:i:s');
            $working->save();

            $question = Question::create('¿Qué deseas hacer?')
            ->fallback('Lo siento, no puedo ayudarte con eso')
            ->callbackId('menu')
            ->addButtons([
                Button::create('Back | Regresar del lunch')->value('back_lunch'),  //  Descanso de 15 minutos. Los empleados Ɵenen 2 descansos diarios de 15 minutos obligatorios.
            ]);
            $this->ask($question, function (Answer $answer) use ($user, $id_working){
                $res = $answer->getValue();
                switch ($res){
                    case 'back_lunch':
                        return $this->back_lunch($user, $id_working);
                        break;
                    default:
                        return $this->repeat('No puedo entenderte, ¿puedes intentarlo de nuevo?');
                }
              });


            $this->bot->typesAndWaits(1);

        }else{
            $this->say($user->name .  ' ' . 'Ya almorzaste, si necesitas descansar toma un break'. ' '. '👋');
            $this->showPostmenu($user, $id_working);
        }


    }


    public function back_lunch($user, $id_working)
    {
        $this->say('¡Hola ' . ' '. $user->name .  ' '. 'regresas del lunch! 👋');

            $working = working_time::find($id_working);

            $working->back_lunch = Carbon::now()->format('H:i:s');
            $working->save();


        $this->showPostmenu($user, $id_working);


        $this->bot->typesAndWaits(1);

    }


     public function out($user, $id_working)
    {

        $working = working_time::find($id_working);

        if ($working->entry_date == $working->out) {
            $this->say('Las horas de entrada y salida son iguales.');
            $this->showPostmenu($user, $id_working);


        }else{
            $this->say('¡Hasta luego!  ' . $user->name . ' '. 'Que descanses. 👋');
            $this->say('Recuerda que tu hora de salida es ' . $user->position->end_time . ' ' . 'Que descanses. 👋');

            if($working->out == null && $working->out >= $user->position->end_time ){
                $this->say('¡Saliste a tu hora!, Que descanses. 👋');
                $working->out = Carbon::now()->format('y/m/d H:i:s');
                $working->cout = 1;
                $working->save();

                $this->showMenu();
            }else if($working->out == null && $working->out < $user->position->end_time){
                $this->say('Saliste antes de tu hora de salida, por favor notifica a tu supervisor.');
                $working->out = Carbon::now()->format('y/m/d H:i:s');
                $working->cout = 0;
                $working->save();

                $this->showMenu();
            }

      } 
    }*/

}
