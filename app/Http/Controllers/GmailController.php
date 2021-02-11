<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use PulkitJalan\Google\Facades\Google;

class GmailController extends Controller
{
    public $user = 'me';

    public function connect(){
        $client = Google::getClient();
        $client->setAccessType('offline');
        $client->setApprovalPrompt('force');

        $authUrl = $client->createAuthUrl();
        return redirect($authUrl);
    }

    public function callback(){
        $userAuth = Auth::user();
        $code = request('code');
        $user = User::find($userAuth->id);
        $client = Google::getClient();

        // Exchange authorization code for an access token.
        $accessToken = $client->fetchAccessTokenWithAuthCode($code);
        $user->token = json_encode($accessToken);

        if ($user->update()) {
            return redirect()->route('gmail.mailbox');
        }
    }

    public function bandejaEntrada(){
        $userAuth = Auth::user();
        $client = Google::getClient();
        $listMessagesCollection = new Collection();

        if ($userAuth->token) {
            // dd($userAuth->token);
            try {
                $client->setAccessToken($userAuth->token);
                if ($client->isAccessTokenExpired()) {
                    if ($client->getRefreshToken()) {
                        $newToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                        $user = User::find($userAuth->id);
                        $user->token = json_encode($newToken);
                        $user->update();
                    }
                }
            } catch (\Throwable $th) {
                echo('Error en el token');
                die();
            }
        } else {
            return redirect()->route('gmail.connect');
        }

        $gmail = Google::make('gmail');

        $filter = [
            'from' => 'andyrat1996@gmail.com'
        ];

        // $results = $gmail->users_labels->listUsersLabels($this->user);
        // dd($results);
        $contacts = new Collection();

        $correo = 'andyrat1996@gmail.com';
        $correoDos = 'amayajuan95@gmail.com';
        $contacts->push('from:'.$correo);
        $contacts->push('from:'.$correoDos);
        $separado_por_comas = implode(",", $contacts->toArray());
        // dd($separado_por_comas);

        $messagesArray = $gmail->users_messages->listUsersMessages($this->user, ['maxResults' => 20])->getMessages();
        // $messagesArray = $gmail->users_messages->listUsersMessages($this->user, ['maxResults' => 20, 'q' => '{'.$separado_por_comas.'}'])->getMessages();

        // dd($messagesArray);

        if ($messagesArray) {
            $messagesCollection = $this->getMessagesCollection($messagesArray, $gmail);
            // dd($messagesCollection);
            if ($messagesCollection) {
                $listMessagesCollection = $this->listMessagesCollection($messagesCollection);
            }
        }

        // dd($listMessagesCollection);

        return view('mails', compact('listMessagesCollection'));
    }

    /**
    * Obtiene una id de un mensaje
    * @return message regresa el mensaje que se quiere visualizar
    */
    public function show($id, $from){
        $userAuth = Auth::user();
        $client = Google::getClient();
        $client->setAccessToken($userAuth->token);
        $gmail = Google::make('gmail');

        $message = $gmail->users_messages->get($this->user, $id);

        // dd($gmail);
        $payload = $message->getPayload();
        $body = $payload->getBody();
        $parts = $payload->getParts();

        $data = $this->decodeBody($body['data']);
        $attachments = [];

        // dd($payload->parts);
        if (!$data) {
            $attachments = $this->getAttachments($id, $parts, $gmail);
            // dd($attachments);
            $data = $this->validateWhereIsData($data, $payload, $id);
        }

        return view('message-detail', compact(['data', 'attachments', 'from']));
    }

    public function getAttachments($message_id, $parts, $client) {
        $attachments = [];

        foreach ($parts as $part) {
            if (!empty($part->body->attachmentId)) {
                $attachment = $client->users_messages_attachments->get($this->user, $message_id, $part->body->attachmentId);
                // dd($attachment);

                $attachments[] = [
                    'filename' => $part->filename,
                    'mimeType' => $part->mimeType,
                    'data'     => strtr($attachment->data, '-_', '+/')
                ];

                // if($attachment){
                //     $filename = $part->filename;

                //     if(empty($filename)) $filename = $attachment['filename'];

                //     if(empty($filename)) $filename = time() . ".dat";

                //     $fp = fopen("./" . time() . "-" . $filename, "w+");
                //     dd(fwrite($fp, $attachment['data']));
                //     fclose($fp);
                // }
            } else if (!empty($part->parts)) {
                $attachments = array_merge($attachments, $this->getAttachments($message_id, $part->parts, $client));
            }
        }

        return $attachments;
    }

    public function downloadAttachment($attachmentId){
        dd($attachmentId);
    }

    /**
    * Obtiene una colección de los ultimos 10 mensajes
    * Recorre cada mensaje y solicita a la API el cuerpo del mensaje
    * @return messages regresa el cuerpo de los mensajes
    */
    public function getMessagesCollection($messagesCollection, $gmail){
        $messages = new Collection();
        if ($messagesCollection) {
            foreach ($messagesCollection as $messageCollection) {
                $message = $gmail->users_messages->get($this->user, $messageCollection->id);
                $messages->push($message);
            }
        }
        return $messages;
    }

    /**
    * Obtiene una colección de mensajes
    * Recorre cada mensaje y arma la lista de la bandeja de entrada
    * @return listMessageCollection regresa la lista de mensajes
    */
    public function listMessagesCollection($messages){
        $listMessagesCollection = new Collection();

        if ($messages) {
            foreach ($messages as $message) {
                $payload = $message->getPayload();
                $headers = $payload->getHeaders();

                // $email = $this->getHeader($headers, 'From');
                // list($partOne, $partTwo) = explode('<', $email);
                // dd($partTwo);
                // list($sanitized, $emailSanitized) = explode('>', $partTwo);
                // $emailSanitized = $sanitized;
                // dd($emailSanitized['0']);
                // $emailSanitized = filter_var($$emailExplode[0], FILTER_SANITIZE_EMAIL);
                // $sanitized = filter_var($a, FILTER_SANITIZE_EMAIL);

                $listMessagesCollection->push((object)[
					'id'	        => $message->id,
					'from' 		    => $this->getHeader($headers, 'From'),
					'subject'	    => $this->getHeader($headers, 'Subject'),
                    'snippet'		=> $message->snippet,
                    'attachment'    => false
                ]);
            }
        }

        return $listMessagesCollection;
    }

    /**
    * Obtiene la data en false
    * Busca dentro del payload la información y le asigna un valor a la data
    * @return data regresa el contenido del mensaje
    */
    public function validateWhereIsData($data, $payload, $messageId){
        // dd($payload);
        // $datas = new Collection();
        // $docs = new Collection();
        // $userAuth = Auth::user();
        // $client = Google::getClient();
        // $client->setAccessToken($userAuth->token);
        // $gmail = Google::make('gmail');

        $parts = $payload->getParts();
        foreach ($parts as $part) {
            // dd($part['parts']);

            if($part['body']->data) {
                $data = $this->decodeBody($part['body']->data);
            } else if($part['parts'] && !$data) {
                foreach ($part['parts'] as $dataInPart) {
                    // dd($dataInPart);
                    if($dataInPart['mimeType'] === 'text/plain' && $dataInPart['body']) {
                        $data = $this->decodeBody($dataInPart['body']->data);
                    }
                }
            }

            // if($part['mimeType'] === 'application/pdf' && $part['body']) {
            //     $data = $part['body']->attachmentId;
            //     $name = $part->filename;
            //     $tmp = [
            //         'attachmentId' => $data,
            //         'filename' => $name
            //     ];
            //     $datas->push($tmp);

            //     // dd($datas);
            // }

            // if($part['mimeType'] === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' && $part['body']) {
            //     $data = $part['body']->attachmentId;
            //     $name = $part->filename;
            //     $tmp = [
            //         'attachmentId' => $data,
            //         'filename' => $name
            //     ];
            //     $datas->push($tmp);

            //     // dd($datas);
            // }

            // if($part['mimeType'] === 'application/vnd.openxmlformats-officedocument.presentationml.presentation' && $part['body']) {
            //     $data = $part['body']->attachmentId;
            //     $name = $part->filename;
            //     $tmp = [
            //         'attachmentId' => $data,
            //         'filename' => $name
            //     ];
            //     $datas->push($tmp);

            //     // dd($datas);
            // }
        }


        // foreach ($datas as $data) {
        //     // dd($data['filename']);
        //     $attachment = $gmail->users_messages_attachments->get($this->user, $messageId, $data['attachmentId']);
        //     $datas = strtr($attachment->data, array('-' => '+', '_' => '/'));
        //     $decodedMessage = base64_decode($datas);
        //     $tmp = [
        //         'attachment' => $decodedMessage,
        //         'filename' => $data['filename']
        //     ];
        //     $docs->push($tmp);

        //     // foreach ($docs as $a) {
        //     //     dd($a['filename']);
        //     // }
        //     // dd($docs->filename);
        //     // return view('message-detail', compact('data'));
        // }


        // dd($datas);

        return $data;

    }


    /**
    * Obtiene el cuerpo codificado/encriptado
    * Decodifica/desencripta el cuerpo del mensaje
    * @return messages regresa el cuerpo del mensaje
    */
    public function decodeBody($body) {
        $rawData = $body;
        $sanitizedData = strtr($rawData,'-_', '+/');
        $decodedMessage = base64_decode($sanitizedData);
        if(!$decodedMessage){
            $decodedMessage = FALSE;
        }
        return $decodedMessage;
    }

    /**
    * Obtiene los headers y el nombre del header que se busca
    * Regresa el header que se le indico
    * @return header
    */
    public function getHeader($headers, $name) {
        foreach($headers as $header) {
          if($header['name'] == $name) {
            return $header['value'];
          }
        }
    }

    public function reply($to){
        return view('reply', compact(['to']));
    }

    public function emailForm(Request $request){
        // dd($request->input('to'));
        $user = Auth::user();
        $client = Google::getClient();
        $client->setAccessToken($user->token);

        $from = $user->email;
        $to = $request->input('to');
        $subject = $request->input('asunto');
        $message_email = $request->input('mensaje');

        // dd(serialize($to));

        $message = $this->gmailCreateMessage($from, $to, $subject, $message_email);

        $this->gmailSendMessage($message);

        return redirect()->route('gmail.mailbox');

        // $to_name = 'Crealab';
        // $to_email = 'crealabplaya@gmail.com';
        // $data = ['name'=>"Sam Jose", "body" => "Test mail"];

        // Mail::send('emails', $data, function($message) use ($to_name, $to_email) {
        //     $message->to($to_email, $to_name)->subject('pek');
        //     $message->from('crealabplaya@gmail.com','Artisans Web');
        // });
    }

    public function gmailCreateMessage($from, $to, $subject, $messageText){
        $message = Google::make('gmail_message');

        $rawMessage = "From: <{$from}>\r\n";
        $rawMessage .= "To: <{$to}>\r\n";
        $rawMessage .= 'Subject: =?utf-8?B?' . base64_encode($subject) . "?=\r\n";
        $rawMessage .= "MIME-Version: 1.0\r\n";
        $rawMessage .= "Content-Type: text/html; charset=utf-8\r\n";
        $rawMessage .= 'Content-Transer-Enconding: quoted-printable' . "\r\n\r\n";
        $rawMessage .= "{$messageText}\r\n";

        $rawM = strtr(base64_encode($rawMessage), array('+' => '-', '/' => '_'));
        $message->setRaw($rawM);

        return $message;
    }

    public function gmailSendMessage($message){
        $service = Google::make('gmail');

        try {
            $message = $service->users_messages->send($this->user, $message);
        } catch (\Throwable $th) {
            print 'Algo paso'. $th->getMessage();
        }

        return NULL;
    }
}
