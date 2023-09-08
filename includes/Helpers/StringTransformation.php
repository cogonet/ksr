<?php

declare(strict_types=1);

namespace KSR\Helpers;

class StringTransformation
{    
    /**
     * case_sensitive_replace
     *
     * @param  mixed $source
     * @param  mixed $replacement
     * @param  mixed $string
     * @param  mixed $whole_word_only
     * @param  mixed $formatted
     * @return string
     */
    public static function case_sensitive_replace(string $source, string $replacement, string $string, $whole_word_only = false, $formatted = false): string
    {

        $pos = 0;
        while ($pos <= strlen($string) && ($pos = strpos(strtolower($string), strtolower($source), $pos)) !== false) {
            if ($whole_word_only) {
                preg_match("/\b" . $string[$pos] . "/", $string[$pos === 0 ? $pos : ($pos - 1)] . $string[$pos], $start);

                if ($pos + strlen($source) <= strlen($string) - 1) {
                    preg_match("/" . $string[($pos + strlen($source) - 1)] . "\b/", $source . $string[($pos + strlen($source))], $end);
                }
            }

            if (($whole_word_only && $start && $end) || !$whole_word_only) {
                $substr = substr($string, $pos, strlen($source));

                if (ctype_upper($substr)) {
                    $string = substr_replace($string, strtoupper($replacement), $pos, strlen($source));
                    continue;
                }

                $substr_parts = preg_split('//u', $substr, -1, PREG_SPLIT_NO_EMPTY);
                $replace_parts = preg_split('//u', $replacement, -1, PREG_SPLIT_NO_EMPTY);
                $new_word = '';

                foreach ($replace_parts as $k => $rp) {
                    if (array_key_exists($k, $substr_parts)) {
                        $new_word .= ctype_upper($substr_parts[$k]) ? mb_strtoupper($rp) : mb_strtolower($rp);
                    } else {
                        $new_word .= $rp;
                    }
                }

                $string = substr_replace($string, $formatted ? '<b>' . $new_word . '</b>' : $new_word, $pos, strlen($source));
            }

            $pos = $pos + strlen($source);
        }

        return $string;
    }
}
