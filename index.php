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
                <label for="chk" aria-hidden="true" style="font-size: small; margin-bottom: 40px; text-align: center;">
                    PENERAPAN ALGORITMA RANDOM FOREST PADA ANALISIS SENTIMEN ULASAN PENGGUNA TERHADAP APLIKASI VIDEO EDITING DI PLAYSTORE
                </label>
                <input type="text" name="txt" id="comment" placeholder="Komentar" required="" />
                <button type="submit" id="submitButton">Proses</button>
                <button type="submit" id="spinnertButton" style="display: none;">
                    <div class="spinner-border" id="spinner" role="status" aria-hidden="true"></div>
                </button>
            </form>
        </div>

        <div class="login">
            <form method="post" id="resultForm">
                <label for="chk" aria-hidden="false" id="sentimentLabel">Sentimen</label>
                <div id="result">
                    <!-- Hasil prediksi sentimen akan muncul di sini -->
                </div>
            </form>
        </div>
    </div>

    <script>
        // Fungsi untuk memprediksi sentimen menggunakan kedua metode (Random Forest dan Pencocokan Kata)
        function predictSentiment(comment, trainingData, numTrees = 10) {
            // Tampilkan pesan "Memproses..." sebelum prediksi
            document.getElementById('sentimentLabel').innerText = "Memproses...";

            // Tampilkan spinner saat menunggu hasil
            document.getElementById('submitButton').disabled = true;
            document.getElementById('submitButton').style.display = 'none';
            document.getElementById('spinnertButton').style.display = 'block';
            document.getElementById('spinnertButton').disabled = true;
            document.getElementById('spinner').style.display = 'inline-block';

            return new Promise((resolve, reject) => {
                // Prediksi dengan Random Forest
                const rfSentiment = randomForestPrediction(comment, trainingData, numTrees);

                // Prediksi dengan Pencocokan Kata
                fetch("get_sentiment.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify({
                            comment: comment
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        const matchingSentiment = data.sentiment; // Mendapatkan sentimen berdasarkan pencocokan kata

                        // Menggabungkan hasil prediksi dari kedua metode
                        const finalSentiment = combineSentiments(rfSentiment, matchingSentiment);

                        // Kembalikan hasil gabungan
                        resolve(finalSentiment);
                    })
                    .catch(error => {
                        reject(error);
                    });
            });
        }

        // Fungsi untuk menggabungkan hasil dari Random Forest dan Pencocokan Kata
        function combineSentiments(rfSentiment, keywordSentiment) {
            if (rfSentiment === keywordSentiment) {
                return rfSentiment; // Jika hasil dari kedua metode sama, pilih hasil tersebut
            } else {
                // Jika berbeda, kita bisa memilih hasil yang lebih kuat berdasarkan beberapa aturan
                return rfSentiment === 'Neutral' ? keywordSentiment : rfSentiment; // Contoh aturan untuk memilih hasil yang lebih kuat
            }
        }

        // Fungsi untuk menghitung TF-IDF
        function calculateTFIDF(data) {
            const tfidf = {};
            const documentCount = data.length;
            const wordDocCounts = {};

            // Hitung jumlah dokumen yang mengandung setiap kata
            data.forEach(item => {
                const uniqueWords = new Set(item.comment.toLowerCase().split(/\s+/));
                uniqueWords.forEach(word => {
                    wordDocCounts[word] = (wordDocCounts[word] || 0) + 1;
                });
            });

            // Hitung TF-IDF untuk setiap dokumen
            data.forEach(item => {
                const words = item.comment.toLowerCase().split(/\s+/);
                const wordCounts = {};

                words.forEach(word => {
                    wordCounts[word] = (wordCounts[word] || 0) + 1;
                });

                tfidf[item.comment] = {};
                Object.keys(wordCounts).forEach(word => {
                    const tf = wordCounts[word] / words.length;
                    const idf = Math.log(documentCount / (1 + wordDocCounts[word]));
                    tfidf[item.comment][word] = tf * idf;
                });
            });

            console.log("TF-IDF Data:", tfidf); // Debugging output
            return tfidf;
        }

        // Fungsi untuk memprediksi dengan Random Forest
        function randomForestPrediction(comment, trainingData, numTrees = 10) {
            const tfidf = calculateTFIDF(trainingData);
            const trees = buildRandomForest(trainingData, numTrees);
            const vector = tfidf[comment] || {};

            let positiveVotes = 0,
                negativeVotes = 0,
                neutralVotes = 0;

            trees.forEach(tree => {
                const votes = tree.map(node => {
                    const match = Object.keys(node.tfidf).every(word => vector[word] && vector[word] === node.tfidf[word]);
                    return match ? node.sentiment : null;
                }).filter(v => v);

                votes.forEach(vote => {
                    if (vote === "Positive") positiveVotes++;
                    else if (vote === "negatif") negativeVotes++;
                    else if (vote !== "Positive" && vote !== "negatif") neutralVotes++;
                });
            });

            const result = {
                Positive: positiveVotes,
                Negative: negativeVotes,
                Neutral: neutralVotes
            };

            console.log("Prediction Votes:", result); // Debugging output

            return Object.keys(result).reduce((a, b) => result[a] > result[b] ? a : b);
        }

        // Fungsi untuk membuat pohon keputusan sederhana
        function createDecisionTree(data) {
            const tree = data.map(d => ({
                tfidf: d.tfidf,
                sentiment: d.sentiment
            }));
            console.log("Decision Tree:", tree); // Debugging output
            return tree;
        }

        // Fungsi untuk membangun beberapa pohon keputusan
        function buildRandomForest(data, numTrees) {
            const tfidf = calculateTFIDF(data);
            const trees = [];

            for (let i = 0; i < numTrees; i++) {
                const sampledData = data.sort(() => 0.5 - Math.random()).slice(0, Math.floor(data.length * 0.8));
                trees.push(createDecisionTree(sampledData.map(d => ({
                    ...d,
                    tfidf: tfidf[d.comment]
                }))));
            }

            console.log("Random Forest Trees:", trees); // Debugging output
            return trees;
        }

        // Mengambil data JSON
        function fetchTrainingData() {
            return new Promise((resolve, reject) => {
                const jsonFiles = ['data/data_sentimen.json', 'data/data_sentimen2.json', 'data/data_sentimen3.json', 'data/data_sentimen4.json'];
                let trainingData = [];

                jsonFiles.forEach((file, index) => {
                    fetch(file)
                        .then(response => response.json())
                        .then(data => {
                            trainingData = [...trainingData, ...data];
                            console.log(`Data from ${file}:`, data); // Debugging output
                            if (index === jsonFiles.length - 1) {
                                resolve(trainingData);
                            }
                        })
                        .catch(error => {
                            console.error(`Error loading file ${file}:`, error);
                            reject(error);
                        });
                });
            });
        }

        // Memulai prediksi
        fetchTrainingData().then(trainingData => {
            console.log("Training Data Loaded:", trainingData); // Debugging output
            document.getElementById('commentForm').addEventListener('submit', function(event) {
                event.preventDefault();
                const comment = document.getElementById('comment').value;

                console.log("User Comment:", comment); // Debugging output
                // Tampilkan pesan "Memproses..." sebelum prediksi
                document.getElementById('sentimentLabel').innerText = "Memproses...";

                // Ambil data pelatihan dan lakukan prediksi
                predictSentiment(comment, trainingData)
                    .then(sentiment => {
                        document.getElementById('sentimentLabel').innerText = sentiment;
                        console.log("Predicted Sentiment:", sentiment); // Debugging output

                        // Menyembunyikan spinner setelah hasil keluar
                        document.getElementById('submitButton').disabled = false;
                        document.getElementById('submitButton').style.display = 'block';
                        document.getElementById('spinnertButton').style.display = 'none';
                        document.getElementById('spinner').style.display = 'none';
                    })
                    .catch(error => {
                        console.error("Error:", error);
                        document.getElementById('sentimentLabel').innerText = "Terjadi kesalahan.";
                    });
            });
        }).catch(error => {
            console.error('Error loading training data:', error);
        });
    </script>
</body>

</html>