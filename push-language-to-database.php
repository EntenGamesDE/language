<?php

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
}

$mysqli->close();