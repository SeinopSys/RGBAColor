<?php

namespace SeinopSys;

/**
 * A flexible class for parsing/storing color values
 */
class RGBAColor
{
    /** @var int */
    public $red, $green, $blue;
    /** @var float */
    public $alpha;

    const COMPONENTS = ['red', 'green', 'blue'];

    /**
     * Maps patterns to a boolean indicating whether the results can be used directly (without hex->dec conversion)
     */
    const PATTERNS = [
        '/#([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})?/i' => false,
        '/#([a-f\d])([a-f\d])([a-f\d])/i' => false,
        '/rgba?\(\s*(\d+),\s*(\d+),\s*(\d+)(?:,\s*([10]|0?\.\d+))?\s*\)/i' => true,
    ];

    /**
     * @param int $r Red color component value (0 - 255)
     * @param int $g Green color component value (0 - 255)
     * @param int $b Blue color component value (0 - 255)
     * @param float $a Alpha color component value (0.0 - 1.0)
     *
     * @return $this
     */
    public function __construct(int $r, int $g, int $b, float $a = 1)
    {
        $this->red = $r;
        $this->green = $g;
        $this->blue = $b;
        $this->alpha = $a;
    }

    /**
     * @param int $r Red color component value (0 - 255)
     *
     * @return $this
     */
    public function setRed(int $r)
    {
        $this->red = $r;

        return $this;
    }

    /**
     * @param int $g Green color component value (0 - 255)
     *
     * @return $this
     */
    public function setGreen(int $g)
    {
        $this->green = $g;

        return $this;
    }

    /**
     * @param int $b Blue color component value (0 - 255)
     *
     * @return $this
     */
    public function setBlue(int $b)
    {
        $this->blue = $b;

        return $this;
    }

    /**
     * @param float $a Alpha color component value (0.0 - 1.0)
     *
     * @return $this
     */
    public function setAlpha(float $a)
    {
        $this->alpha = $a;

        return $this;
    }

    public function isTransparent(): bool
    {
        return $this->alpha !== 1.0;
    }

    /**
     * Returns the brightness of the color using the YIQ weighing
     *
     * @return int Brightness (0 - 255)
     */
    public function yiq(): int
    {
        return (($this->red * 299) + ($this->green * 587) + ($this->blue * 114)) / 1000;
    }

    /**
     * Returns the current color as a HEX string (without the alpha component)
     *
     * @return string
     */
    public function toHex(): string
    {
        return '#' . strtoupper(self::_pad(base_convert($this->red, 10, 16)) . self::_pad(base_convert($this->green, 10, 16)) . self::_pad(base_convert($this->blue, 10, 16)));
    }

    /**
     * Returns the current color as a HEX string (including the alpha component)
     *
     * @return string
     */
    public function toHexa(): string
    {
        return $this->toHex() . strtoupper(self::_pad(base_convert(round($this->alpha * 255), 10, 16)));
    }

    /**
     * Returns the current color as an RGB string (without the alpha component)
     *
     * @return string
     */
    public function toRGB(): string
    {
        return "rgb({$this->red},{$this->green},{$this->blue})";
    }

    /**
     * Returns the current color as an RGBA string (always includes the alpha component)
     *
     * @return string
     */
    public function toRGBA(): string
    {
        return "rgba({$this->red},{$this->green},{$this->blue},{$this->alpha})";
    }

    /**
     * Returns a valid CSS representation of the color
     * If the color contains transparency the RGBA string is returned, and HEX otherwise.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->isTransparent() ? $this->toRGBA() : $this->toHex();
    }


    /**
     * Pads values to length 2 with zeroes from the left (for internal use)
     *
     * @param mixed $input
     *
     * @return string
     */
    private static function _pad($input): string
    {
        return str_pad((string)$input, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Invert all 3 color values, and optionally the alpha
     *
     * @param bool $alpha If truw, inverts the alpha channel too
     *
     * @return self
     */
    public function invert($alpha = false): self
    {
        $this->red = 255 - $this->red;
        $this->green = 255 - $this->green;
        $this->blue = 255 - $this->blue;
        if ($alpha) {
            $this->alpha = 1 - $this->alpha;
        }

        return $this;
    }

    /**
     * Given an input string, this method calls preg_replace_callback for each color found in the string
     * $callback receives an instance of this class, and is expected to return a replacement
     *
     * @param string $input Input string
     * @param callable $callback Callback function
     */
    public static function forEachColorIn(string &$input, callable $callback)
    {
        foreach (self::PATTERNS as $pattern => $_) {
            $input = preg_replace_callback($pattern, function ($match) use ($callback, $pattern) {
                return $callback(self::_parseWith($match[0], $pattern));
            }, $input);
        }
    }

    /**
     * Internal function used to create an instance of the class based on a pattern
     *
     * @param string $color Color as a string
     * @param string $pattern Pattern to use
     *
     * @return self|null
     */
    private static function _parseWith(string $color, string $pattern):?self
    {
        if (!preg_match($pattern, $color, $matches)) {
            return null;
        }

        $values = array_slice($matches, 1, 4);

        if (!self::PATTERNS[$pattern]) {
            if (strlen($values[0]) === 1) {
                $values = array_map(function ($el) {
                    return $el . $el;
                }, $values);
            }
            $values[0] = intval($values[0], 16);
            $values[1] = intval($values[1], 16);
            $values[2] = intval($values[2], 16);
            if (!empty($values[3])) {
                $values[3] = intval($values[3], 16) / 255;
            }
        }

        return new self(...$values);
    }

    /**
     * Returns an instace of this class based on an exsiting color string
     * If parsing fails null is returned instead
     *
     * @param string $color Color as a string
     *
     * @return self|null
     */
    public static function parse(string $color):?self
    {
        foreach (self::PATTERNS as $pattern => $_) {
            $result = self::_parseWith($color, $pattern);
            if ($result === null) {
                continue;
            }

            return $result;
        }

        return null;
    }
}
