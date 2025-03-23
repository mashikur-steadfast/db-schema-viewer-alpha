<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;


Route::get('/', [App\Http\Controllers\DBSchemaController::class, 'show']);

// Route::get('/sse/{orgId}', function (Request $request, $orgId) {
//     // Set headers for SSE
//     header('Content-Type: text/event-stream');
//     header('Cache-Control: no-cache');
//     header('Connection: keep-alive');

//     // Use a channel pattern based on the orgId
//     $pattern = "org:{$orgId}:*";

//     // Subscribe to the pattern in Redis
//     Redis::psubscribe([$pattern], function ($message, $channel) {
//         // Prepare data to send as SSE message
//         $data = [
//             'org_id' => explode(':', $channel)[1], // Extract orgId from channel
//             'channel' => $channel,
//             'message' => json_decode($message, true),
//         ];

//         echo "data: " . json_encode($data) . "\n\n";
//         ob_flush();
//         flush();
//     });

//     return response('', 200, [
//         'Content-Type' => 'text/event-stream',
//         'Cache-Control' => 'no-cache',
//         'Connection' => 'keep-alive',
//     ]);
// });

// // Default view route
// Route::view('/', 'index');

// Route::view('/send-location', 'send-location');

// Route::get('/send-location-api', function (Request $request) {

//     $lat = $request->query('lat');
//     $lon = $request->query('lon');
//     $orgId = 1;

//     $message = json_encode(['lat' => $lat, 'lon' => $lon]);
//     Redis::publish("org:{$orgId}:location", $message);

//     return response()->json(['status' => 'Location sent']);
// });
