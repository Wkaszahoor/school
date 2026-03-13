<?php
$zipFile = "library.zip"; // Zip file ka naam
$extractTo = "./"; // Jahan extract karna hai

if (!file_exists($zipFile)) {
    die("❌ ZIP file '$zipFile' not found.");
}

$zip = new ZipArchive;
if ($zip->open($zipFile) === TRUE) {
    $zip->extractTo($extractTo);
    $zip->close();
    echo "✅ Extracted successfully!";
} else {
    echo "❌ Failed to extract ZIP file.";
}
?>