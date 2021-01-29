<?php
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/gmail', 'GmailController@connect')->name('gmail.connect');
Route::get('/callback', 'GmailController@callback')->name('gmail.callback');

// Route::get('/gmail', function(){
//     $googleClient = Google::getClient();
    
//     if ($googleClient->isAccessTokenExpired()) {

//         if ($googleClient->getRefreshToken()) {
//             $googleClient->fetchAccessTokenWithRefreshToken($googleClient->getRefreshToken());
//         } else {
//             $authUrl = $googleClient->createAuthUrl();
//             return redirect($authUrl);
//         }   
//     } else {
      
//     }
// })->name('gmail.setup');



// function decodeBody($body) {
//     $rawData = $body;
//     $sanitizedData = strtr($rawData,'-_', '+/');
//     $decodedMessage = base64_decode($sanitizedData);
//     if(!$decodedMessage){
//         $decodedMessage = FALSE;
//     }
//     return $decodedMessage;
// }


// Route::get('/callback', function(){
//     $code = request('code');
//     $googleClient = Google::getClient();
//     // Exchange authorization code for an access token.
//     $accessToken = $googleClient->fetchAccessTokenWithAuthCode($code);
//     $googleClient->setAccessToken($accessToken);

//     // Check to see if there was an error.
//     if (array_key_exists('error', $accessToken)) {
//         throw new Exception(join(', ', $accessToken));
//     }

//     $service = new Google_Service_Gmail($googleClient);
//     $user = 'me';
//     // $results = $service->users_messages->listUsersMessages($user);
//     // dd($results);

    
//     // sin adjuntos boletin
//     $results = $service->users_messages->get($user, '17749cea540a7b17');

//     // sin adjuntos
//     // $results = $service->users_messages->get($user, '17749d61b0551190');

//     // con adjuntos
//     // $results = $service->users_messages->get($user, '17749ca97b8c1147');

//     // $results = $service->users_messages->get($user, '17749ca97b8c1147');
//     // $attachmentData = $service->users_messages_attachments->get('me', '17749ca97b8c1147', 'ANGjdJ-OhsPRiUkc3OJlY0a3mhzxSeN8hKz5HusURl05sjokSn28AHKSCkxllUhzXLod0cAP1djTMiT5C5z6trke5IcpbfPikr54api0L3D29qmZTf240fOlBO2EC7PMLyoj4m42RZVmWfgXhR75dTiSV-nJzwVMBbapQp1VZXW_2dbDIaNfBOEvVpGvlWr_mLbjG0B9jv7Qeb0QRiiYBPSKmB-qhzE-HEZdp10mTA');

//     $payload = $results->getPayload();
//     $body = $payload->getBody();

//     $FOUND_BODY = decodeBody($body['data']);

//     // If we didn't find a body, let's look for the parts
//     if(!$FOUND_BODY) {
//         $parts = $payload->getParts();
        
//         foreach ($parts as $part) {
            
//             if($part['body']->data) {
//                 $FOUND_BODY = decodeBody($part['body']->data);
//                 break;
//             } else if($part['parts'] && !$FOUND_BODY) {
//                 foreach ($part['parts'] as $p) {
//                     // replace 'text/html' by 'text/plain' if you prefer
                    
//                     if($p['mimeType'] === 'text/plain' && $p['body']) {
//                         $FOUND_BODY = decodeBody($p['body']->data);
//                         break;
//                     } 
//                 }
//             }
            
//             if($FOUND_BODY) {
//                 break;
//             }
//         }
//     }
//     // Finally, print the message ID and the body
//     echo $FOUND_BODY;

//     dd($FOUND_BODY);
// });


Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
