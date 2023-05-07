<?php declare(strict_types=1);

namespace IC\Html;

use Stringable;

/**
 * ClassList is a helper class to operate on class names.
 */
final class ClassList implements Stringable
{

    private array $names = [];

    /**
     * @param string ...$names
     */
    public function __construct(string ...$names)
    {
        $this->add(...$names);
    }

    /**
     * Checks if the names exists in the list.
     *
     * @param string ...$names
     *
     * @return bool
     */
    public function contains(string ...$names): bool
    {
        foreach ($names as $name) {
            if (!in_array($name, $this->names, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Adds the given class names to the list.
     *
     * @param string ...$names
     *
     * @return $this
     */
    public function add(string ...$names): self
    {
        $add = $this->parse($names);

        if (!empty($add)) {
            $result = $this->isEmpty() ? $add : array_merge($this->names, $add);

            $this->names = $this->unique($result);
        }

        return $this;
    }

    /**
     * Removes the given class names from the list.
     *
     * @param string ...$names
     *
     * @return $this
     */
    public function remove(string ...$names): self
    {
        if (!$this->isEmpty()) {
            $remove = $this->parse($names);

            if (!empty($remove)) {
                $result = array_diff($this->names, $remove);

                $this->names = array_values($result);
            }
        }

        return $this;
    }

    /**
     * Replaces the occurrences of {$search} with the {$replace} class name.
     *
     * If {$loose} is false (by default) it does a strict search and replace, replacing one class name with the given
     * substitution.
     *
     * If {$loose} is true, it can match patterns, and every occurrence of {$search} will be replaced by {$replace} in
     * all the names.
     *
     * @param string $search  The class name or pattern to search.
     * @param string $replace The class name or pattern to replace.
     * @param bool   $loose   Whether to perform a strict or loose search.
     *
     * @return $this
     */
    public function replace(string $search, string $replace, bool $loose = false): self
    {
        if ($this->isEmpty()) {
            return $this;
        }

        $names = null;

        if ($loose) {
            $names = str_replace($search, $replace, $this->toString());
        } elseif (($index = array_search($search, $this->names, true)) !== false) {
            $names         = $this->names;
            $names[$index] = $replace;
        }

        if ($names) {
            $names = $this->parse($names);

            $this->names = $this->unique($names);
        }

        return $this;
    }

    /**
     * Removes the given existing class name from the list and returns false.
     * If it doesn't exist it's added and returns true.
     *
     * @param string    $name  The class name to toggle.
     * @param bool|null $force Null by default, and the behaviour is as explained above.
     *                         If set to false, then the class name will only be removed, but not added.
     *                         If set to true, then the class name will only be added, but not removed.
     *
     * @return bool A boolean value indicating whether the class name is in the list after the call or not.
     */
    public function toggle(string $name, bool $force = null): bool
    {
        $result = false;

        if ($name === '') {
            return false;
        }

        if ($this->contains($name)) {
            $result = true;

            if ($force === null || $force === false) {
                $this->remove($name);

                $result = false;
            }
        } elseif ($force === null || $force === true) {
            $count = $this->count();
            $this->add($name);

            if ($this->count() > $count) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Runs a filter over each class name. If the {$callback} returns false the class name is removed.
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function filter(callable $callback): self
    {
        if (!$this->isEmpty()) {
            $names = array_filter($this->names, $callback);

            $this->names = array_values($names);
        }

        return $this;
    }

    /**
     * Runs a map over each class name.
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function map(callable $callback): self
    {
        if (!$this->isEmpty()) {
            $names = array_map($callback, $this->names);
            $names = $this->parse($names);

            $this->names = $this->unique($names);
        }

        return $this;
    }

    /**
     * Returns the number of class names in the list.
     *
     * @return int The number of class names.
     */
    public function count(): int
    {
        return count($this->names);
    }

    /**
     * Whether the list is empty or not.
     *
     * @return bool True if empty, false otherwise.
     */
    public function isEmpty(): bool
    {
        return empty($this->names);
    }

    /**
     * Returns the class names as an array.
     *
     * @return array The class names as an array.
     */
    public function toArray(): array
    {
        return $this->names;
    }

    /**
     * Returns the class names as a string. Each class name is separated by a single whitespace.
     *
     * @return string The class names as a string.
     */
    public function toString(): string
    {
        return implode(' ', $this->names);
    }

    /**
     * Magic method to convert the list to a string.
     *
     * @return string The class names as a string.
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Converts the input into an array of valid class names.
     *
     * @param array|string $names
     *
     * @return array
     */
    private function parse(array|string $names): array
    {
        $names = is_string($names) ? [$names] : $names;

        if (empty($names)) {
            return [];
        }

        $result = [];

        foreach ($names as $name) {
            $values   = $this->split($name);
            $result[] = array_map([$this, 'sanitize'], $values);
        }

        $result = array_merge(...$result);

        return array_filter($result);
    }

    /**
     * Splits a class name by spaces.
     *
     * @param string $name
     *
     * @return array
     */
    private function split(string $name): array
    {
        return (array) preg_split('#\s+#', trim($name));
    }

    /**
     * Removes illegal characters from a class name.
     *
     * @param string $name
     *
     * @return string
     */
    private function sanitize(string $name): string
    {
        // Strip out any % encoded octets
        $sanitized = preg_replace('/%[a-f0-9]{2}/i', '', $name);
        // Limit to A-Z,a-z,0-9,_,-
        $sanitized = preg_replace('/[^a-z0-9_-]/i', '', $sanitized);
        // Remove multiple hyphens at the start
        $sanitized = preg_replace('/^-+/', '-', $sanitized);
        // Remove numbers at the start
        $sanitized = ltrim($sanitized, '0..9');

        return $this->isValid($sanitized) ? $sanitized : '';
    }

    /**
     * Determines whether the class name is valid.
     *
     * CSS 2.1 compliant.
     *
     * @param string $name
     *
     * @return bool
     */
    private function isValid(string $name): bool
    {
        return !($name === '' || preg_match('/^-?[_a-z]+[_a-z0-9-]*/im', $name) !== 1);
    }

    /**
     * Removes duplicate class names.
     *
     * @param array $names
     *
     * @return array
     */
    private function unique(array $names): array
    {
        return array_keys(array_flip($names));
    }

}
