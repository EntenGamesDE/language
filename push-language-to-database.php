<?php

$dir = new DirectoryIterator(__DIR__);
foreach ($dir as $fileinfo) {
    if ($fileinfo->isDot() || $fileinfo->getFilename() === "push-language-to-database.php") {
        continue;
    }
    unlink($fileinfo->getRealPath());
}

exec('git clone https://github.com/EntenGamesDE/language.git language-repo');
$dir = new DirectoryIterator(__DIR__ . DIRECTORY_SEPARATOR . "language-repo");
foreach ($dir as $fileinfo) {
    if ($fileinfo->isDot() || $fileinfo->getExtension() !== "json") {
        continue;
    }
    rename($fileinfo->getRealPath(), dirname($fileinfo->getRealPath(), 2) . DIRECTORY_SEPARATOR . $fileinfo->getFilename());
}
exec("rm -r " . __DIR__ . DIRECTORY_SEPARATOR . "language-repo");

$mysqliSettings = yaml_parse_file("/home/Datenbank/MySQL.yml");
$mysqli = new mysqli(
    $mysqliSettings["address"],
    $mysqliSettings["username"],
    $mysqliSettings["password"],
    $mysqliSettings["database"]
);

$dir = new DirectoryIterator(__DIR__);
foreach ($dir as $fileinfo) {
    if ($fileinfo->isDot() || $fileinfo->getExtension() !== "json") {
        continue;
    }
    $languageKey = $fileinfo->getBasename(".json");
    /** @var array<string, string> $translations */
    $translations = json_decode(file_get_contents($fileinfo->getRealPath()), true);

    $stmt = $mysqli->prepare("DELETE FROM translations WHERE language = ?;");
    $stmt->bind_param("s", $languageKey);
    $stmt->execute();

    $stmt = $mysqli->prepare("INSERT INTO translations (language, translation_key, translation) VALUES ('" . $languageKey . "', ?, ?)");
    foreach ($translations as $translationKey => $translation) {
        $stmt->bind_param("ss", $translationKey, $translation);
        $stmt->execute();
        $stmt->reset();
    }
    unlink($fileinfo->getRealPath());
}

$mysqli->close();