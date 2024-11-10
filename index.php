<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Form Sentimen</title>
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@500&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="main">
        <input type="checkbox" id="chk" aria-hidden="true" />

        <div class="signup">
            <form method="post" id="commentForm">
                <label for="chk" aria-hidden="true" style="font-size: small; margin-bottom: 40px; text-align: center;">PENERAPAN ALGORITMA RANDOM FOREST PADA ANALISIS SENTIMEN ULASAN PENGGUNA TERHADAP APLIKASI VIDEO EDITING DI PLAYSTORE</label>
                <input type="text" name="txt" id="comment" placeholder="Komentar" required="" />
                <button type="submit" id="submitButton">
                    Proses
                    <!-- <div class="spinner-border" id="spinner" role="status" aria-hidden="true"></div> -->
                </button>
                <button type="submit" id="spinnertButton" style="display: none;">
                    <div class="spinner-border" id="spinner" role="status" aria-hidden="true"></div>
                </button>
            </form>
        </div>

        <div class="login">
            <form method="post" id="resultForm">
                <?php
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $comment = $_POST['txt'];
                    $sentiment = getSentiment($comment);
                }
                ?>
                <?php if (!empty($sentiment)) : ?>
                    <label for="chk" aria-hidden="false"><?= htmlspecialchars($sentiment); ?></label>
                <?php else : ?>
                    <label for="chk" aria-hidden="false">Sentimen</label>
                <?php endif; ?>
                <div id="result">
                    <!-- <?php if ($sentiment == 'Positive') : ?>
            <h2 style="margin-left:10px;">Sentimen : Positive</h2>
          <?php endif; ?> -->
                    <?php
                    // if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    //   $comment = $_POST['txt'];
                    //   $sentiment = getSentiment($comment);
                    //   echo "<h2 style=\"margin-left:10px;\">Sentimen:" . htmlspecialchars($sentiment) . "</h2>";
                    //   // echo "<p style=\"margin-left:10px;\">Sentimen komentar: " . htmlspecialchars($sentiment) . "</p>";
                    // }


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
                                    return 'Error: Format JSON tidak valid.';
                                }
                            } else {
                                return 'Error: Tidak bisa menemukan file JSON ' . $jsonFile;
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

                        return $bestSentiment;
                    }
                    ?>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('commentForm').addEventListener('submit', function() {
            document.getElementById('submitButton').disabled = true;
            document.getElementById('submitButton').style.display = 'none';
            document.getElementById('spinnertButton').style.display = 'block';
            document.getElementById('spinnertButton').disabled = true;
            document.getElementById('spinner').style.display = 'inline-block';
        });
    </script>
</body>

</html>