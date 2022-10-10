<?php declare(strict_types=1);

/**
 * Holds a custom word, with a value and possible definition.
 */
class CustomWord implements JsonSerializable {
    private const LETTER_VALUE = array(
        'a' => 1,
        'b' => 3,
        'c' => 3, 
        'd' => 2,
        'e' => 1,
        'f' => 4,
        'g' => 2,
        'h' => 4,
        'i' => 1,
        'j' => 8,
        'k' => 5,
        'l' => 1,
        'm' => 3,
        'n' => 1,
        'o' => 1,
        'p' => 3,
        'q' => 10,
        'r' => 1,
        's' => 1,
        't' => 1,
        'u' => 1,
        'v' => 4,
        'w' => 4,
        'x' => 8,
        'y' => 4,
        'z' => 10
    );

    private string $word;
    private string $value_word;
    private int $value;
    private string $definition;

    public function __construct(string $word, string $value_word, bool $is_bingo, string $definition) {
        $this->word =  strtolower($word);
        $this->value_word = strtolower($value_word);
        $this->value = $this->calculate_value() + ($is_bingo ? 50 : 0);
        $this->definition = strtolower($definition);
    }

    private function calculate_value(): int {
        $total = 0;
        if ($this->value_word !== "") {
            foreach (str_split($this->value_word) as $letter) {
                $total += self::LETTER_VALUE[$letter];
            }
        }

        return $total;
    }

    public function get_word(): string {
        return $this->word;
    }

    public function get_value_word(): string {
        return $this->value_word;
    }

    public function get_value(): int {
        return $this->value;
    }

    public function get_definition(): string {
        return $this->definition;
    }

    public function jsonSerialize() {
        return get_object_vars($this);
    }
}

// // testing, remove when finished
// $word = new CustomWord("asdf", "asdf", true, "Just letters");
// print $word->get_value();
// print "\n";

// $arr = array(
//     new CustomWord("a", "a", false, ""),
//     new CustomWord("z", "z", false, ""),
//     new CustomWord("c", "c", false, "")
// );
// print_r($arr);
// sort($arr);
// print_r($arr);

?>
