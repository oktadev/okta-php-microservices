<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Aws\Sqs\SqsClient;

class TranscriptionController extends Controller
{
    public function create(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'audio-file-url' => 'required|url'
        ]);

        $message = [
            'user-email'          => $request->input('email'),
            'user-audio-file-url' => $request->input('audio-file-url')
        ];

        // of course, this should be extracted to a service
        // instead of using a private method on the controller:
        $this->putMessageOnQueue($message);

        return response()->json($message, 201);
    }

    private function putMessageOnQueue($message)
    {
        $key = getenv('AWS_ACCESS_KEY_ID');
        $secret = getenv('AWS_SECRET_ACCESS_KEY');

        $client = SqsClient::factory([
            'key' => $key,
            'secret' => $secret,
            'version' => '2012-11-05',
            // modify the region if necessary:
            'region'  => 'us-east-1',
        ]);

        $result = $client->sendMessage(array(
            'QueueUrl'    => getenv('AWS_QUEUE_URL_TRANSCRIBE'),
            'MessageBody' => json_encode($message)
        ));

        return $result;
    }
}
