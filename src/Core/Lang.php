<?php
/**
 * Lang – loads a JSON language file and provides dot-notation key lookup.
 * All user-visible strings must go through Lang::t() so the UI can be
 * translated by swapping the JSON file.
 */
class Lang
{
    private static array $strings = [];

    /**
     * Load a language JSON file into memory.
     * Must be called once during bootstrap before any translation is needed.
     */
    public static function load(string $filePath): void
    {
        $json = file_get_contents($filePath);
        self::$strings = json_decode($json, true) ?? [];
    }

    /**
     * Look up a translation by dot-notation key, e.g. 'auth.errors.email_required'.
     * Returns the key itself if no translation is found (safe fallback).
     *
     * @param array<string,string> $replace  Placeholder substitutions: ['name' => 'Alice']
     *                                        replaces ':name' in the translated string.
     */
    public static function t(string $key, array $replace = []): string
    {
        $parts = explode('.', $key);
        $node  = self::$strings;

        foreach ($parts as $part) {
            if (!is_array($node) || !array_key_exists($part, $node)) {
                return $key; // key not found → return key as fallback
            }
            $node = $node[$part];
        }

        if (!is_string($node)) {
            return $key;
        }

        foreach ($replace as $placeholder => $replacement) {
            $node = str_replace(':' . $placeholder, (string)$replacement, $node);
        }

        return $node;
    }
}
