<?php

namespace App\Http\Controllers;

use App\User;
use Exception;
use Illuminate\Http\Request;
use PulkitJalan\Google\Facades\Google;
use Google_Service_Gmail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

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
        $code = request('code');
        $userAuth = Auth::user();
        $user = User::find($userAuth->id);
        $googleClient = Google::getClient();
        $listMessagesCollection = new Collection();
        // Exchange authorization code for an access token.
        $accessToken = $googleClient->fetchAccessTokenWithAuthCode($code);
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
            $client->setAccessToken($userAuth->token);
            if ($client->isAccessTokenExpired()) {
                if ($client->getRefreshToken()) {
                    $newToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                    $user = User::find($userAuth->id);
                    $user->token = json_encode($newToken);
                    $user->update();
                }
            }
        } else {
            return redirect()->route('gmail.connect');
        }

        $gmail = Google::make('gmail');
        $messagesArray = $gmail->users_messages->listUsersMessages($this->user, ['maxResults' => 10])->getMessages();

        if ($messagesArray) {
            $messagesCollection = $this->getMessagesCollection($messagesArray, $gmail);
            if ($messagesCollection) {
                $listMessagesCollection = $this->listMessagesCollection($messagesCollection);
            }
        }

        return view('mails', compact('listMessagesCollection'));
    }

    /**
    * Obtiene una id de un mensaje
    * @return message regresa el mensaje que se quiere visualizar
    */
    public function show($id){
        $userAuth = Auth::user();
        $client = Google::getClient();
        $client->setAccessToken($userAuth->token);
        $gmail = Google::make('gmail');

        $message = $gmail->users_messages->get($this->user, $id);

        // dd($gmail);
        $payload = $message->getPayload();
        $body = $payload->getBody();
        $data = $this->decodeBody($body['data']);

        if (!$data) {
            $data = $this->validateWhereIsData($data, $payload);
        }

        // dd($data);

        return view('message-detail', compact('data'));
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
    public function validateWhereIsData($data, $payload){
        // dd($payload);

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
        }

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
}
