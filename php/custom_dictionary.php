<?php

declare(strict_types=1);

require "dictionary_element.php";
require "dictionary_name.php";

/**
 * Read and cache the letters from a dictionary file.
 */
class CustomDictionary {
    private string $dictionary_name;
    private array $words;

    public function __construct(string $dictionary_name) {
        $this->dictionary_name = $dictionary_name;
        $this->words = [];
    }

    /**
     * Read dictionary from file or use cache if the dictionary has already been read.
     * @return array of DictionaryElements (word and possible definition)
     */
    public function get_valid_words(): array {
        if (array_key_exists($this->dictionary_name, $this->words)) {
            return $this->words[$this->dictionary_name];
        }

        $valid_words = [];
        $path = "../resources/dicts/" . $this->dictionary_name . ".txt";
    	$input = fopen($path, "r") or die("Could not open $path");

        while(!feof($input)) {
            $line = fgets($input);
            // Why is this necessary?
            if (gettype($line) !== 'string') {
                break;
            }
            $line = rtrim($line, "\n");
            $parts = explode("\t", $line);
            $word = strtolower($parts[0]);

            if (strlen($word) > 1) {
                $definition = (sizeof($parts) > 1 ? $parts[1] : "");
                array_push($valid_words, new DictionaryElement($word, $definition));
            }
        }

        fclose($input);
        $this->words[$this->dictionary_name] = $valid_words;

        return $valid_words;
    }
}

?>