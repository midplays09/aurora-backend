<?php

namespace App\Security;

/**
 * Sanitizes all user input to prevent XSS, injection attacks, and malicious payloads.
 * Used in every controller before data touches the database.
 */
class InputSanitizer
{
    /**
     * Sanitize a single string value.
     * Strips HTML/PHP tags, trims whitespace, removes null bytes.
     */
    public function sanitize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Remove null bytes (can bypass other filters)
        $value = str_replace("\0", '', $value);

        // Strip all HTML and PHP tags
        $value = strip_tags($value);

        // Convert special HTML characters to entities
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // Remove potential MongoDB injection operators
        $value = $this->stripMongoOperators($value);

        // Trim whitespace
        $value = trim($value);

        return $value;
    }

    /**
     * Sanitize an email address.
     */
    public function sanitizeEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $email = strtolower(trim($email));
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);

        return $email ?: null;
    }

    /**
     * Sanitize an entire array of string values (e.g., request body).
     * @return array<string, string|null>
     */
    public function sanitizeArray(array $data, array $allowedKeys): array
    {
        $sanitized = [];

        foreach ($allowedKeys as $key) {
            if (isset($data[$key]) && is_string($data[$key])) {
                $sanitized[$key] = $this->sanitize($data[$key]);
            } else {
                $sanitized[$key] = $data[$key] ?? null;
            }
        }

        return $sanitized;
    }

    /**
     * Validate that a password meets minimum security requirements.
     * @return string|null Error message, or null if valid.
     */
    public function validatePassword(string $password): ?string
    {
        if (strlen($password) < 8) {
            return 'Password must be at least 8 characters long.';
        }

        if (strlen($password) > 4096) {
            return 'Password is too long.';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            return 'Password must contain at least one uppercase letter.';
        }

        if (!preg_match('/[a-z]/', $password)) {
            return 'Password must contain at least one lowercase letter.';
        }

        if (!preg_match('/[0-9]/', $password)) {
            return 'Password must contain at least one number.';
        }

        return null;
    }

    /**
     * Strip MongoDB-specific operators that could be used for NoSQL injection.
     * e.g., $gt, $ne, $where, $regex
     */
    private function stripMongoOperators(string $value): string
    {
        // Remove any string starting with $ that looks like a Mongo operator
        return preg_replace('/\$[a-zA-Z]+/', '', $value);
    }

    /**
     * Validate that a string doesn't exceed a maximum length.
     */
    public function enforceMaxLength(?string $value, int $maxLength): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_substr($value, 0, $maxLength, 'UTF-8');
    }
}
