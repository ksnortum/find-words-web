<?php

declare(strict_types=1);

require "custom_dictionary.php";
require "custom_word.php";

if (!function_exists('str_endsWith')) {
    function str_endsWith(string $haystack, string $needle): bool {
        $needle_len = strlen($needle);
        return ($needle_len === 0 || 0 === substr_compare($haystack, $needle, - $needle_len));
    }
}

class WordSearcher {
    private const ALL_LETTERS = "abcdefghijklmnopqrstuvwxyz";

    private stdClass $data;

    public function __construct(stdClass $data) {
        $this->data = $data;
    }

    public function get_words(): array {
        $dictionary = new CustomDictionary($this->data->dict);
        $valid_words = $dictionary->get_valid_words();
        $contains_letters = $this->get_letters_from_contains();
        $data_letters = $this->get_valid_data_letters($contains_letters);
        $search_letters = $data_letters . $contains_letters . $this->data->startsWith . $this->data->endsWith;
        $search_letters = strtolower($search_letters);

        $wildcards = "";
        foreach (str_split($this->data->letters) as $letter) {
            if ($letter === ".") {
                $wildcards .= ".";
            }
        }
 
        $pattern = $this->build_pattern();
        $words = [];

        foreach ($valid_words as $element) {
            $word = $element->get_word();

            if (!$this->data->typeOfGame === "crossword" && strlen($word) > strlen($search_letters) + strlen($wildcard)) {
                continue;
            }

            if (trim($pattern) !== "" && !preg_match($pattern, $word)) {
                continue;
            }

            if (($this->data->typeOfGame === "crossword" || $this->data->typeOfGame == "wordle")
                    && trim($this->data->numberOfLetters) !== ""
                    && strlen($word) != intval($this->data->numberOfLetters)) {
                continue;
            }

            $word_copy = $word;
            $value_letters = "";
            foreach (str_split($search_letters) as $letter) {
                if (preg_match("/$letter/", $word_copy) === 1) {
                    $word_copy = preg_replace("/$letter/", "", $word_copy, 1);
                    $value_letters .= $letter;
                }

                if (strlen($word_copy) === 0) {
                    break;
                }
            }

            $i = 0;
            while (strlen($word_copy) != 0 && $i < strlen($wildcards)) {
                $word_copy = substr($word_copy, 1);
                $i++;
            }

            if (strlen($word_copy) == 0) {
                $is_bingo = strlen($word) - strlen($contains_letters) - strlen($this->data->startsWith)
                        - strlen($this->data->endsWith) >= 7;
                array_push($words, new CustomWord($word, $value_letters, $is_bingo, $element->get_description()));
            }
        }

        if ($this->data->typeOfGame === "scrabble") {
            // Sort by value in CustomWord
            usort($words, fn($a, $b) => $b->get_value() <=> $a->get_value());
        } else {
            // Sort by word
            usort($words, fn($a, $b) => strcmp($a->get_word(), $b->get_word()));
        }

        return $words;
    }

    private function get_valid_data_letters(string $contains_letters): string {
        $data_letters = "";

        if ($this->data->typeOfGame === "wordle") {
            foreach (str_split(self::ALL_LETTERS) as $letter) {
                if (strpos($this->data->letters, $letter) === false) {
                    $data_letters .= $letter;
                }
            }
            $data_letters .= $data_letters . $data_letters;
        } else {
            $data_letters = $this->data->letters;
        }

        $data_letters = $this->remove_capitals($this->data->contains, $data_letters);
        $data_letters = $this->remove_capitals($this->data->startsWith, $data_letters);
        $data_letters = $this->remove_capitals($this->data->endsWith, $data_letters);
        str_replace(".", "", $data_letters);

        return $data_letters;
    }

    private function remove_capitals(string $word, string $data_letters): string {
        foreach (str_split($word) as $letter) {
            if (ctype_upper($letter)) {
                $pattern = "/" . strtolower($letter) . "/";
                $data_letters = preg_replace($pattern, "", $data_letters);
            }
        }

        return $data_letters;
    }

    private function get_letters_from_contains() {
        $is_escaped_character = false;
        $result = "";

        foreach (str_split($this->data->contains) as $letter) {
            if (!$is_escaped_character) {
                if (preg_match("/[a-zA-Z]/", $letter) && !$is_escaped_character) {
                    $result .= $letter;
                }
            }

            $is_escaped_character = ($letter === "\\");
        }

        return $result;
    }

    private function build_pattern(): string {
        $pattern = $this->lower_case_non_escaped_letters($this->data->contains);

        if (trim($this->data->startsWith) != "") {
            $pattern = "^" . strtolower($this->data->startsWith) . ".*" . $pattern;
        }

        if (trim($this->data->endsWith) != "") {
            if (!str_endsWith($pattern, ".*")) {
                $pattern .= ".*";
            }
            $pattern .= strtolower($this->data->endsWith) . "$";
        }

        if (trim($pattern) !== "") {
            $pattern = "/" . $pattern . "/";
        }

        return $pattern;
    }

    private function lower_case_non_escaped_letters(string $letters): string {
        $is_escaped_character = false;
        $result = "";

        foreach (str_split($letters) as $letter) {
            if (!$is_escaped_character) {
                if (preg_match("/[a-zA-Z]/", $letter)) {
                    $result .= strtolower($letter);
                } elseif ($letter !== "\\") {
                    $result .= $letter;
                }
            }

            $is_escaped_character = ($letter === "\\");
        }

        return $result;
    }
}

// $data = new StdClass();
// $data->typeOfGame = "crossword";
// $data->dict = DictionaryName::COLLINS_DEFINE;
// $data->letters = "man";
// $data->contains = "";
// $data->startsWith = ""; ;
// $data->endsWith = "";
// $data->numberOfLetters = "3";
// $ws = new WordSearcher($data);
// print_r($ws->get_words());

?>