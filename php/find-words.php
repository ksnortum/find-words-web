<?php 

declare(strict_types=1);

// Log error to a file "error.log" in php directory
ini_set("log_errors", true);
ini_set('error_log', "error.log");
// Below is how you would enter a debugging message
// error_log("Error message"); // debug

require "word_searcher.php";

// Takes raw data from the request
$json = file_get_contents('php://input');

// Converts it into a PHP object
$data = json_decode($json);

// Return a JSON string of word suggestions
$word_searcher = new WordSearcher($data);
$words = $word_searcher->get_words();
echo json_encode($words);

?>