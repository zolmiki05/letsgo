<?php
/**
 * Lang – minimal localisation engine backed by a JSON file.
 *
 * All user-visible strings are stored in lang/hu.json (currently Hungarian).
 * Translations are accessed by dot-notation keys, e.g.:
 *
 *   Lang::t('auth.errors.email_required')
 *   // → "Az e-mail cím megadása kötelező."
 *
 * If a key is not found, the key itself is returned as a safe fallback,
 * making missing translations visible without crashing.
 *
 * Placeholder substitution:
 *   Lang::t('group.welcome', ['name' => 'Péter'])
 *   // Replaces ':name' with 'Péter' in the translated string.
 *
 * Adding a new language:
 *   1. Copy lang/hu.json → lang/en.json (or any other locale).
 *   2. Change the Lang::load() call in bootstrap.php to point to the new file.
 *   3. No code changes required elsewhere.
 */
class Lang
{
    /** Loaded translation strings, nested array matching the JSON structure. */
    private static array $strings = [];

    /**
     * Load a language JSON file into memory.
     *
     * Must be called once during bootstrap, before any controller or view runs.
     * Subsequent calls would overwrite the loaded strings.
     *
     * @param string $filePath  Absolute path to the JSON language file.
     */
    public static function load(string $filePath): void
    {
        $json = file_get_contents($filePath);
        self::$strings = json_decode($json, true) ?? [];
    }

    /**
     * Translate a dot-notation key and optionally substitute placeholders.
     *
     * Traverses the nested $strings array one segment at a time.
     * Returns the key unchanged if the path does not resolve to a string,
     * so missing keys are always visible and never cause errors.
     *
     * @param string               $key      Dot-notation key, e.g. 'event.status.IDEA'.
     * @param array<string,string> $replace  Map of placeholder → replacement.
     *                                        ':name' in the string is replaced by $replace['name'].
     * @return string  The translated string, or $key if not found.
     */
    public static function t(string $key, array $replace = []): string
    {
        $parts = explode('.', $key);
        $node  = self::$strings;

        // Walk the nested array one key segment at a time
        foreach ($parts as $part) {
            if (!is_array($node) || !array_key_exists($part, $node)) {
                return $key; // key not found → return key as safe fallback
            }
            $node = $node[$part];
        }

        // The resolved node must be a string (not a nested array/object)
        if (!is_string($node)) {
            return $key;
        }

        // Apply placeholder substitutions: ':name' → value
        foreach ($replace as $placeholder => $replacement) {
            $node = str_replace(':' . $placeholder, (string)$replacement, $node);
        }

        return $node;
    }
}
