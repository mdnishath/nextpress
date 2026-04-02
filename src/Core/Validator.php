<?php

declare(strict_types=1);

namespace NextPressBuilder\Core;

/**
 * Input validation with rule-based validation.
 *
 * Validates data against a set of rules and collects errors.
 * Rules: 'required', 'string', 'int', 'bool', 'email', 'url', 'color',
 *        'slug', 'json', 'min:N', 'max:N', 'in:a,b,c', 'regex:pattern'
 */
class Validator
{
    /** @var array<string, string[]> Field errors keyed by field name. */
    private array $errors = [];

    /**
     * Validate data against rules.
     *
     * @param array<string, mixed> $data
     * @param array<string, string> $rules  e.g., ['title' => 'required|string|max:255']
     * @return array<string, string[]> Errors keyed by field name.
     */
    public function validate(array $data, array $rules): array
    {
        $this->errors = [];

        foreach ( $rules as $field => $ruleString ) {
            $fieldRules = explode( '|', $ruleString );
            $value = $data[ $field ] ?? null;

            foreach ( $fieldRules as $rule ) {
                $this->applyRule( $field, $value, $rule );
            }
        }

        return $this->errors;
    }

    /**
     * Check if validation passed (no errors).
     */
    public function passes(): bool
    {
        return empty( $this->errors );
    }

    /**
     * Check if validation failed.
     */
    public function fails(): bool
    {
        return ! empty( $this->errors );
    }

    /**
     * Get all errors.
     *
     * @return array<string, string[]>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get errors for a specific field.
     *
     * @return string[]
     */
    public function fieldErrors(string $field): array
    {
        return $this->errors[ $field ] ?? [];
    }

    /**
     * Get a flat list of all error messages.
     *
     * @return string[]
     */
    public function messages(): array
    {
        $messages = [];
        foreach ( $this->errors as $fieldErrors ) {
            foreach ( $fieldErrors as $error ) {
                $messages[] = $error;
            }
        }
        return $messages;
    }

    /**
     * Apply a single validation rule to a field.
     */
    private function applyRule(string $field, mixed $value, string $rule): void
    {
        // Parse rule parameters (e.g., 'max:255' => ['max', '255']).
        $parts = explode( ':', $rule, 2 );
        $ruleName = $parts[0];
        $param = $parts[1] ?? null;

        match ( $ruleName ) {
            'required' => $this->validateRequired( $field, $value ),
            'string'   => $this->validateString( $field, $value ),
            'int'      => $this->validateInt( $field, $value ),
            'float'    => $this->validateFloat( $field, $value ),
            'bool'     => $this->validateBool( $field, $value ),
            'array'    => $this->validateArray( $field, $value ),
            'email'    => $this->validateEmail( $field, $value ),
            'url'      => $this->validateUrl( $field, $value ),
            'color'    => $this->validateColor( $field, $value ),
            'slug'     => $this->validateSlug( $field, $value ),
            'json'     => $this->validateJson( $field, $value ),
            'min'      => $this->validateMin( $field, $value, $param ),
            'max'      => $this->validateMax( $field, $value, $param ),
            'in'       => $this->validateIn( $field, $value, $param ),
            'regex'    => $this->validateRegex( $field, $value, $param ),
            default    => null, // Unknown rules are silently ignored.
        };
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[ $field ][] = $message;
    }

    private function validateRequired(string $field, mixed $value): void
    {
        if ( $value === null || $value === '' || $value === [] ) {
            $this->addError( $field, "{$field} is required." );
        }
    }

    private function validateString(string $field, mixed $value): void
    {
        if ( $value !== null && ! is_string( $value ) ) {
            $this->addError( $field, "{$field} must be a string." );
        }
    }

    private function validateInt(string $field, mixed $value): void
    {
        if ( $value !== null && ! is_int( $value ) && ! ctype_digit( (string) $value ) ) {
            $this->addError( $field, "{$field} must be an integer." );
        }
    }

    private function validateFloat(string $field, mixed $value): void
    {
        if ( $value !== null && ! is_numeric( $value ) ) {
            $this->addError( $field, "{$field} must be a number." );
        }
    }

    private function validateBool(string $field, mixed $value): void
    {
        if ( $value !== null && ! is_bool( $value ) && ! in_array( $value, [ 0, 1, '0', '1', 'true', 'false' ], true ) ) {
            $this->addError( $field, "{$field} must be a boolean." );
        }
    }

    private function validateArray(string $field, mixed $value): void
    {
        if ( $value !== null && ! is_array( $value ) ) {
            $this->addError( $field, "{$field} must be an array." );
        }
    }

    private function validateEmail(string $field, mixed $value): void
    {
        if ( $value !== null && $value !== '' && ! is_email( (string) $value ) ) {
            $this->addError( $field, "{$field} must be a valid email address." );
        }
    }

    private function validateUrl(string $field, mixed $value): void
    {
        if ( $value !== null && $value !== '' && ! filter_var( $value, FILTER_VALIDATE_URL ) ) {
            $this->addError( $field, "{$field} must be a valid URL." );
        }
    }

    private function validateColor(string $field, mixed $value): void
    {
        if ( $value !== null && $value !== '' ) {
            $sanitizer = new Sanitizer();
            if ( $sanitizer->color( (string) $value ) === '' ) {
                $this->addError( $field, "{$field} must be a valid color value." );
            }
        }
    }

    private function validateSlug(string $field, mixed $value): void
    {
        if ( $value !== null && $value !== '' && ! preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) $value ) ) {
            $this->addError( $field, "{$field} must be a valid slug (lowercase letters, numbers, and hyphens)." );
        }
    }

    private function validateJson(string $field, mixed $value): void
    {
        if ( $value !== null && is_string( $value ) ) {
            json_decode( $value );
            if ( json_last_error() !== JSON_ERROR_NONE ) {
                $this->addError( $field, "{$field} must be valid JSON." );
            }
        }
    }

    private function validateMin(string $field, mixed $value, ?string $param): void
    {
        if ( $value === null || $param === null ) {
            return;
        }

        $min = (int) $param;

        $strlen = function_exists( 'mb_strlen' ) ? 'mb_strlen' : 'strlen';
        if ( is_string( $value ) && $strlen( $value ) < $min ) {
            $this->addError( $field, "{$field} must be at least {$min} characters." );
        } elseif ( is_numeric( $value ) && (float) $value < $min ) {
            $this->addError( $field, "{$field} must be at least {$min}." );
        } elseif ( is_array( $value ) && count( $value ) < $min ) {
            $this->addError( $field, "{$field} must have at least {$min} items." );
        }
    }

    private function validateMax(string $field, mixed $value, ?string $param): void
    {
        if ( $value === null || $param === null ) {
            return;
        }

        $max = (int) $param;

        $strlen = function_exists( 'mb_strlen' ) ? 'mb_strlen' : 'strlen';
        if ( is_string( $value ) && $strlen( $value ) > $max ) {
            $this->addError( $field, "{$field} must not exceed {$max} characters." );
        } elseif ( is_numeric( $value ) && (float) $value > $max ) {
            $this->addError( $field, "{$field} must not exceed {$max}." );
        } elseif ( is_array( $value ) && count( $value ) > $max ) {
            $this->addError( $field, "{$field} must not have more than {$max} items." );
        }
    }

    private function validateIn(string $field, mixed $value, ?string $param): void
    {
        if ( $value === null || $param === null ) {
            return;
        }

        $allowed = explode( ',', $param );

        if ( ! in_array( (string) $value, $allowed, true ) ) {
            $this->addError( $field, "{$field} must be one of: {$param}." );
        }
    }

    private function validateRegex(string $field, mixed $value, ?string $param): void
    {
        if ( $value === null || $param === null || $value === '' ) {
            return;
        }

        if ( ! preg_match( $param, (string) $value ) ) {
            $this->addError( $field, "{$field} format is invalid." );
        }
    }
}
