<?php
session_start();

$servername = "localhost";
$username_db = "root";
$password_db = "";
$database = "surveypolinema";

// Buat koneksi
$conn = new mysqli($servername, $username_db, $password_db, $database);

// Periksa koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil username dari sesi
$username = isset($_SESSION['username']) ? $_SESSION['username'] : ''; 

// Debug: Cek nilai username dari sesi
echo "Username dari sesi: " . $username . "<br>";

// Ambil username dari sesi
if(empty($username)) {
    die("Error: Username tidak tersedia");
}

// Query SQL untuk mendapatkan user_id berdasarkan username
$sql_user_id = "SELECT user_id FROM m_user WHERE username = '$username'";
$result = $conn->query($sql_user_id);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $user_id = $row['user_id'];

    // Perbarui data survei yang belum memiliki user_id dengan user_id dari pengguna yang login
    $sql_update_survey = "UPDATE m_survey SET user_id = '$user_id', survey_tanggal = NOW() WHERE user_id IS NOT NULL AND user_id = '$user_id'";
    if ($conn->query($sql_update_survey) === TRUE) {
        // Ambil ID survei yang baru saja dimasukkan
        $sql_get_survey_id = "SELECT survey_id FROM m_survey WHERE user_id = '$user_id' ORDER BY survey_id DESC LIMIT 1";
        $result_survey_id = $conn->query($sql_get_survey_id);
        if ($result_survey_id->num_rows > 0) {
            $row_survey_id = $result_survey_id->fetch_assoc();
            $survey_id = $row_survey_id['survey_id'];

            // Perbarui data responden yang belum memiliki survey_id dengan survey_id baru
            $sql_update_responden = "UPDATE t_responden_alumni SET survey_id = '$survey_id', responden_tanggal = NOW() WHERE survey_id IS NULL AND responden_alumni_id = '$user_id'";
            if ($conn->query($sql_update_responden) === TRUE) {
                // Ambil ID responden yang baru saja dimasukkan
                $sql_get_responden_id = "SELECT responden_alumni_id FROM t_responden_alumni WHERE survey_id = '$survey_id' AND responden_alumni_id = '$user_id'";
                $result_responden_id = $conn->query($sql_get_responden_id);
                if ($result_responden_id->num_rows > 0) {
                    $row_responden_id = $result_responden_id->fetch_assoc();
                    $responden_id = $row_responden_id['responden_alumni_id'];

                    // Insert jawaban ke tabel t_jawaban_alumni
                    foreach ($_POST as $key => $value) {
                        if (substr($key, 0, 6) === 'answer') { // Jika input adalah jawaban
                            $soal_id = substr($key, 6); // Ambil ID soal dari nama input
                            $jawaban = mysqli_real_escape_string($conn, $value);

                            // Query SQL untuk menyimpan jawaban
                            $sql_insert_jawaban = "INSERT INTO t_jawaban_alumni (responden_alumni_id, soal_id, jawaban) VALUES ('$responden_id', '$soal_id', '$jawaban')";
                            if (!$conn->query($sql_insert_jawaban)) { // Eksekusi query
                                echo "Error: " . $sql_insert_jawaban . "<br>" . $conn->error;
                            }
                        }
                    }
                    echo '<link rel="stylesheet" href="../style.css">';
                    echo '<div class="success-messageK">';
                    echo '<h2 class="h2K">Data Berhasil Disimpan</h2>';
                    echo '<p>Silakan menekan tombol di bawah ini untuk kembali ke Tampilan Awal.</p>';
                    echo '<div class="spasiK"></div>';
                    echo '<a href="../index.html" class="buttonK">Kembali</a>'; // Perubahan pada tombol
                    echo '</div>';
                } else {
                    echo "Error: Gagal mendapatkan ID responden";
                }
            } else {
                echo '<link rel="stylesheet" href="../style.css">';
                echo '<div class="error-messageK">';
                echo '<h2 class="h2K">Error</h2>';
                echo '<p>Penyimpanan data responden tidak berhasil. Silakan coba lagi.</p>';
                echo '<p>Error: ' . $conn->error . '</p>';
                echo '</div>';
            }
        } else {
            echo "Error: Gagal mendapatkan ID survei";
        }
    } else {
        echo '<link rel="stylesheet" href="../style.css">';
        echo '<div class="error-messageK">';
        echo '<h2 class="h2K">Error</h2>';
        echo '<p>Penyimpanan data survei tidak berhasil. Silakan coba lagi.</p>';
        echo '<p>Error: ' . $conn->error . '</p>';
        echo '</div>';
    }
} else {
    echo "Error: User ID tidak ditemukan";
}

$conn->close();
?>
