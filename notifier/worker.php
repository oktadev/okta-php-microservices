<?php
require 'vendor/autoload.php';

use Aws\Sqs\SqsClient;
use Dotenv\Dotenv;

$dotenv = Dotenv::create(__DIR__);
$dotenv->load();

$key = getenv('AWS_ACCESS_KEY_ID');
$secret = getenv('AWS_SECRET_ACCESS_KEY');
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
        'QueueUrl'        => $notificationQueueUrl,
        'WaitTimeSeconds' => 10,
    ]);

    // if we have a message, get the receipt handle and message body and process it
    if ($result->getPath('Messages')) {
        $receiptHandle = $result->getPath('Messages/*/ReceiptHandle')[0];
        $messageBody = $result->getPath('Messages/*/Body')[0];
        $decodedMessage = json_decode($messageBody, true);

        // Create the Transport
        $transport = (new Swift_SmtpTransport(getenv('SMTP_HOST'), 587, 'tls'))
          ->setUsername(getenv('SMTP_USERNAME'))
          ->setPassword(getenv('SMTP_PASSWORD'))
          ->setAuthMode('PLAIN');

        // Create the Mailer using your created Transport
        $mailer = new Swift_Mailer($transport);

        // Create a message
        $message = (new Swift_Message('Your file has been transcribed!'))
          ->setFrom(['notifier@app.com' => 'Audio Transcription Service'])
          ->setTo([$decodedMessage['user-email']])
          ->setBody($decodedMessage['user-audio-file-url']);

        // Send the message
        $result = $mailer->send($message);

        // delete the notification message:
        $client->deleteMessage([
            'QueueUrl' => $notificationQueueUrl,
            'ReceiptHandle' => $receiptHandle,
        ]);
    }
}
