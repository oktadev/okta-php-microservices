<?php
require 'vendor/autoload.php';

use Aws\Sqs\SqsClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::create(__DIR__);
$dotenv->load();

$key = getenv('AWS_ACCESS_KEY_ID');
$secret = getenv('AWS_SECRET_ACCESS_KEY');
$queueUrl = getenv('AWS_QUEUE_URL_TRANSCRIBE');
$notificationQueueUrl = getenv('AWS_QUEUE_URL_NOTIFY');

$client = SqsClient::factory([
    'key' => $key,
    'secret' => $secret,
    'version' => '2012-11-05',
    // modify the region if necessary:
    'region'  => 'us-east-1',
]);

while (true) {

    // wait for messages with 10 second long-polling
    $result = $client->receiveMessage([
        'QueueUrl'        => $queueUrl,
        'WaitTimeSeconds' => 10,
    ]);

    // if we have a message, get the receipt handle and message body and process it
    if ($result->getPath('Messages')) {
        $receiptHandle = $result->getPath('Messages/*/ReceiptHandle')[0];
        $messageBody = $result->getPath('Messages/*/Body')[0];
        $decodedMessage = json_decode($messageBody, true);

        // simulate processing the message here:
        // wait 2 seconds
        sleep(2);

        // put a mesage on the notification queue:
        $result = $client->sendMessage(array(
            'QueueUrl'    => $notificationQueueUrl,
            'MessageBody' => $messageBody
        ));

        // delete the transcription message:
        $client->deleteMessage([
            'QueueUrl' => $queueUrl,
            'ReceiptHandle' => $receiptHandle,
        ]);
    }
}
