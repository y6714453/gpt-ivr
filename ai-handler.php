<?php
if (!isset($_GET['file_link'])) {
    echo "error";
    exit;
}

$fileUrl = $_GET['file_link'];

// הורדת קובץ ההקלטה
$audioPath = "/tmp/recording.mp3";
file_put_contents($audioPath, file_get_contents($fileUrl));

// שלב 1: תמלול עם Whisper של OpenAI
$openaiKey = 'הכנס_כאן_את_המפתח_שלך';

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.openai.com/v1/audio/transcriptions",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $openaiKey"
    ],
    CURLOPT_POSTFIELDS => [
        'file' => new CURLFile($audioPath),
        'model' => 'whisper-1'
    ]
]);
$transcription = json_decode(curl_exec($curl), true);
curl_close($curl);

$question = $transcription['text'] ?? '';

if (!$question) {
    echo "tts=לא הצלחתי להבין את השאלה.";
    exit;
}

// שלב 2: שליחת השאלה ל־ChatGPT
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.openai.com/v1/chat/completions",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $openaiKey",
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'user', 'content' => $question]
        ]
    ])
]);
$response = json_decode(curl_exec($curl), true);
curl_close($curl);

$answer = $response['choices'][0]['message']['content'] ?? 'לא הצלחתי לענות על השאלה.';

// שלב 3: החזרת תשובה לימות
echo "tts=" . $answer;
