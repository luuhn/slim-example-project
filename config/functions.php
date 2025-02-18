<?php

/**
 * Convert all applicable characters to HTML entities.
 *
 * @param string|null $text The string
 *
 * @return string The html encoded string
 */
function html(?string $text = null): string
{
    return htmlspecialchars($text ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Get validation error message of given field.
 *
 * @param array $validation Entire validation array (containing message and errors)
 * @param string $field Name given in validation class where error messages are made (may differ from HTML)
 *
 * @return string|null
 */
function get_field_error(array $validation, string $field): ?string
{
    if ($validation !== []) {
        $key = array_search($field, array_column($validation['errors'], 'field'), false);
        if ($key !== false) {
            return $validation['errors'][$key]['message'];
        }
    }

    return null;
}

/**
 * Text translation.
 *
 * @param string $message The message
 * @param string|int|float|bool ...$context The context
 *
 * @return string The translated string
 */
function __(string $message, ...$context): string
{
    $locale = setlocale(LC_ALL, 0);
    $translated = gettext($message);
    if (!empty($context)) {
        $translated = vsprintf($translated, $context);
    }

    return $translated;
}
