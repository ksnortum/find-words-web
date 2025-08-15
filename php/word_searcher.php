<?php

declare(strict_types=1);

require "custom_dictionary.php";
require "custom_word.php";
require "type_of_game.php";

// Not needed?  Creates a function "str_ends_with" if this version of PHP doesn't have it
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool {
        $needle_len = strlen($needle);
        return ($needle_len === 0 || 0 === substr_compare($haystack, $needle, - $needle_len));
    }
}

/**
 * Does all the work for searching words based on input data.
 */
class WordSearcher {
    private const ALL_LETTERS = "abcdefghijklmnopqrstuvwxyz";

    private stdClass $data;

    public function __construct(stdClass $data) {
        $this->data = $data;
    }

    /**
     * The main function.  It loops through the words in a dictionary, comparing them to the parameters in the input data object.
     * @return array of CustomWords
     */
    public function get_words(): array {

        // open a dictionary and read all its words
        $dictionary = new CustomDictionary($this->data->dict);
        $valid_words = $dictionary->get_valid_words();

        // search_letters is what you'll actual use to search the words
        $contains_letters = $this->get_letters_from_contains();
        $data_letters = $this->get_valid_data_letters($contains_letters);
        $search_letters = $data_letters . $contains_letters . $this->data->startsWith . $this->data->endsWith;
        $search_letters = strtolower($search_letters);

        // wildcards contains only the dots in the available letters
        $wildcards = "";
        foreach (str_split($this->data->letters) as $letter) {
            if ($letter === ".") {
                $wildcards .= ".";
            }
        }
 
        // build a pattern that may quickly exclude words that don't match
        $pattern = $this->build_pattern();
        $words = [];

        // Loop through each dictionary word (element) and test it against the input data
        foreach ($valid_words as $element) {
            $word = $element->get_word();

            // Skip, the word is longer that the characters to search for
            if ($this->data->typeOfGame !== TypeOfGame::CROSSWORD && strlen($word) > strlen($search_letters) + strlen($wildcards)) {
                continue;
            }

            // Skip, the word doesn't match the pattern we built.
            // try/catch in case something goes wrong with the pattern.
            try {
                if (trim($pattern) !== "" && !preg_match($pattern, $word)) {
                    continue;
                }
            } catch ( \Exception $e ) {
                error_log("The pattern '" . $pattern . "' is not a valid regex."); 
                break;
            }

            // Skip, some of the games can use number_of_letters (word length) as a criterion 
            if (($this->data->typeOfGame === TypeOfGame::CROSSWORD || $this->data->typeOfGame == TypeOfGame::WORDLE)
                    && trim($this->data->numberOfLetters) !== ""
                    && strlen($word) != intval($this->data->numberOfLetters)) {
                continue;
            }

            // If "contains" is a list of letters and not all the letters are in the word, skip
            if (str_contains($this->data->contains, ',') && !$this->all_letters_in_word($word)) {
                continue;
            }

            // If the game is crossword, we're done, add this word to the array
            if ($this->data->typeOfGame === TypeOfGame::CROSSWORD) {
                array_push($words, new CustomWord($word, "", false, $element->get_definition()));
                continue;
            }

            // Loop through the search letters, removing any letters it finds in the copy_word
            $word_copy = $word;
            $value_letters = "";
            foreach (str_split($search_letters) as $letter) {
                if (preg_match("/$letter/", $word_copy) === 1) {
                    $word_copy = preg_replace("/$letter/", "", $word_copy, 1);
                    $value_letters .= $letter;
                }

                // word_copy is empty (we found a word), we can exit
                if (strlen($word_copy) === 0) {
                    break;
                }
            }

            // Wildcards remove *any* letter from the word_copy
            $i = 0;
            while (strlen($word_copy) != 0 && $i < strlen($wildcards)) {
                $word_copy = substr($word_copy, 1);
                $i++;
            }

            // This word_copy is empty, so the word matches.  Check for Bingo and add to the array
            if (strlen($word_copy) == 0) {
                $is_bingo = strlen($word) - strlen($contains_letters) - strlen($this->data->startsWith)
                        - strlen($this->data->endsWith) >= 7;
                array_push($words, new CustomWord($word, $value_letters, $is_bingo, $element->get_definition()));
            }
        }

        // Scrabble sorts words by descending value, all others by ascending alphabetic order.
        if ($this->data->typeOfGame === TypeOfGame::SCABBLE) {
            // Sort by value in CustomWord
            usort($words, fn($a, $b) => $b->get_value() <=> $a->get_value());
        } else {
            // Sort by word
            usort($words, fn($a, $b) => strcmp($a->get_word(), $b->get_word()));
        }

        return $words;
    }

    /**
     * Finds letters to search for from Available Letters input. Capital letters are 
     * removed since they have a special meaning. In the game Wordle, Available 
     * Letters are Can't Have letters.  Use the entire alphabet and remove letters
     * found in Can't Have.  Then the letters are tripled since you can have up to
     * three letters that are the same.
     * 
     * @param string $contains_letters letters from Contains input, cleaned
     * @return string letters to search for from Available Letters input
     */
    private function get_valid_data_letters(string $contains_letters): string {
        $data_letters = "";

        if ($this->data->typeOfGame === TypeOfGame::WORDLE) {
            foreach (str_split(self::ALL_LETTERS) as $letter) {
                if (strpos($this->data->letters, $letter) === false) {
                    $data_letters .= $letter;
                }
            }
            $data_letters .= $data_letters . $data_letters;
        } else {
            $data_letters = $this->data->letters;
        }

        $data_letters = $this->remove_capitals($contains_letters, $data_letters);
        $data_letters = $this->remove_capitals($this->data->startsWith, $data_letters);
        $data_letters = $this->remove_capitals($this->data->endsWith, $data_letters);
        str_replace(".", "", $data_letters);

        return $data_letters;
    }

    /**
     * Remove letters from data_letters that are found in word.
     */
    private function remove_capitals(string $word, string $data_letters): string {
        foreach (str_split($word) as $letter) {
            if (ctype_upper($letter)) {
                $pattern = "/" . strtolower($letter) . "/";
                $data_letters = preg_replace($pattern, "", $data_letters);
            }
        }

        return $data_letters;
    }

    /**
     * Get non-escaped letters from Contains letters input, since it can contain
     * regexps or commas or whitespace.
     */
    private function get_letters_from_contains() {
        $is_escaped_character = false;
        $result = "";

        foreach (str_split($this->data->contains) as $letter) {
            if ($letter === ',' || preg_match("/\s/", $letter)) {
                continue;
            }

            if (!$is_escaped_character) {
                if (preg_match("/[a-zA-Z]/", $letter) && !$is_escaped_character) {
                    $result .= $letter;
                }
            }

            $is_escaped_character = ($letter === "\\");
        }

        return $result;
    }

    /**
     * Build a regexp from Contains, StartsWith and EndsWith letters.  This regexp
     * is not intended to catch only the correct words, but instead to filter out
     * words that are obviously wrong.
     */
    private function build_pattern(): string {
        $pattern = "";

        // If string contains commas, it's not a pattern, it's a list of letters
        if (!str_contains($this->data->contains, ',')) {
            $pattern = $this->lower_case_non_escaped_letters($this->data->contains);
        }

        if (trim($this->data->startsWith) != "") {
            $pattern = "^" . strtolower($this->data->startsWith) . ".*" . $pattern;
        }

        if (trim($this->data->endsWith) != "") {
            if (!str_ends_with($pattern, ".*")) {
                $pattern .= ".*";
            }
            $pattern .= strtolower($this->data->endsWith) . "$";
        }

        if (trim($pattern) !== "") {
            $pattern = "/" . $pattern . "/";
        }

        return $pattern;
    }

    /**
     * Lowercase all letters except when escaped, since the letters can be a regexp.
     */
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

    /**
     * Return true only if all letters in the "contains" list are in word
     */
    private function all_letters_in_word(string $word): bool {
        $all_letters_in_word = true;

        foreach(preg_split("/,\s*/", $this->data->contains) as $element) {
            if (!str_contains($word, $element)) {
                $all_letters_in_word = false;
                break;
            }
        }

        return $all_letters_in_word;
    }
}

?>