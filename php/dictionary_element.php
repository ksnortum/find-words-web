<?php

declare(strict_types=1);

class DictionaryElement {
    private string $word;
    private string $description;

    public function __construct(string $word, string $description) {
        $this->word = $word;
        $this->description = $description;
    }

    public function get_word(): string {
        return $this->word;
    }

    public function get_description(): string {
        return $this->description;
    }
}

?>