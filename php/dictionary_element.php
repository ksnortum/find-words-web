<?php

declare(strict_types=1);

/**
 * Holds a dictionary word and possible definition.
 */
class DictionaryElement {
    private string $word;
    private string $definition;

    public function __construct(string $word, string $definition) {
        $this->word = $word;
        $this->definition = $definition;
    }

    public function get_word(): string {
        return $this->word;
    }

    public function get_definition(): string {
        return $this->definition;
    }
}

?>