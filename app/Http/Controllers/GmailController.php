<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use PulkitJalan\Google\Facades\Google;
use Google_Service_Gmail;
use Illuminate\Support\Collection;

class GmailController extends Controller
{

    public $user = 'me';

    public function connect(){
        $client = Google::getClient();

        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                $authUrl = $client->createAuthUrl();
                return redirect($authUrl);
            }
        }
    }

    public function callback(){
        $code = request('code');
        $googleClient = Google::getClient();
        $listMessagesCollection = new Collection();
        // Exchange authorization code for an access token.
        $accessToken = $googleClient->fetchAccessTokenWithAuthCode($code);
        $googleClient->setAccessToken($accessToken);

        dd($googleClient);

        // Check to see if there was an error.
        if (array_key_exists('error', $accessToken)) {
            throw new Exception(join(', ', $accessToken));
        }


        #####################################################

        $gmail = Google::make('gmail');
        $messagesArray = $gmail->users_messages->listUsersMessages($this->user, ['maxResults' => 10])->getMessages();
        if ($messagesArray) {
            $messagesCollection = $this->getMessagesCollection($messagesArray, $gmail);
            if ($messagesCollection) {
                $listMessagesCollection = $this->listMessagesCollection($messagesCollection);
            } else {

            }
        } else {
            echo "no hay mensajes";
        }

        return view('mails', compact('listMessagesCollection'));
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
                // $body = $payload->getBody();
                // $data = $this->decodeBody($body['data']);
                // if (!$data) {
                //     $data = $this->validateWhereIsData($data, $payload);
                // }

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
        $parts = $payload->getParts();
        foreach ($parts as $part) {
            if($part['body']->data) {
                $data = $this->decodeBody($part['body']->data);
            } else if($part['parts'] && $data) {
                foreach ($part['parts'] as $dataInPart) {
                    if($dataInPart['mimeType'] === 'text/plain' && $dataInPart['body']) {
                        $data = $this->decodeBody($dataInPart['body']->data);
                    }
                }
            }
        }

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
