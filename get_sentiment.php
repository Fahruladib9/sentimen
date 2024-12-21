<?php

function getSentiment($comment)
{
    $jsonFiles = ['data/data_sentimen.json', 'data/data_sentimen2.json', 'data/data_sentimen3.json', 'data/data_sentimen4.json'];
    $sentiments = [];

    foreach ($jsonFiles as $jsonFile) {
        if (file_exists($jsonFile)) {
            $jsonData = file_get_contents($jsonFile);
            $data = json_decode($jsonData, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                foreach ($data as $item) {
                    if (isset($item['comment']) && isset($item['sentiment'])) {
                        $sentiments[] = [
                            'comment' => $item['comment'],
                            'sentiment' => $item['sentiment']
                        ];
                    }
                }
            } else {
                return json_encode(['sentiment' => 'Error: Format JSON tidak valid.']);
            }
        } else {
            return json_encode(['sentiment' => 'Error: Tidak bisa menemukan file JSON ' . $jsonFile]);
        }
    }

    $commentWords = preg_split('/\s+/', strtolower($comment));
    $bestSentiment = 'No Data';
    $highestMatchCount = 0;

    for ($percent = 100; $percent > 0; $percent--) {
        $sentimentCount = [];
        $foundMatch = false;

        foreach ($sentiments as $item) {
            $itemWords = preg_split('/\s+/', strtolower($item['comment']));
            $matchCount = count(array_intersect($commentWords, $itemWords));
            $matchPercentage = ($matchCount / count($itemWords)) * 100;

            if ($matchPercentage >= $percent) {
                if (!isset($sentimentCount[$item['sentiment']])) {
                    $sentimentCount[$item['sentiment']] = 0;
                }
                $sentimentCount[$item['sentiment']] += $matchCount;

                if ($matchCount > $highestMatchCount) {
                    $highestMatchCount = $matchCount;
                }
                $foundMatch = true;
            }
        }

        if ($foundMatch) {
            arsort($sentimentCount);
            $bestSentiment = key($sentimentCount);
            break;
        }
    }

    return json_encode(['sentiment' => $bestSentiment]);
}

// Ambil data JSON dari request POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $comment = $data['comment'] ?? '';

    if ($comment) {
        echo getSentiment($comment);
    } else {
        echo json_encode(['sentiment' => 'Error: Comment tidak ditemukan.']);
    }
}
