<?php
namespace EncodingConversionDataLossChecker;

/*
 * EncodingConversionDataLossChecker MIT License
 *
 * Copyright (c) 2013 Adrie den Hartog
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/**
 * Standalone library to test the whether a string's content would survive charset encoding to another encoding standard
 */
class EncodingConversionDataLossChecker
{
    private $fromEncoding;
    private $toEncoding;

    /**
     * @param string $fromEncoding The encoding the string is originally encoded in
     * @param string $toEncoding The encoding the string is to be tested at
     */
    public function __construct ($fromEncoding, $toEncoding)
    {
        $this->testParameters($fromEncoding, $toEncoding);
        $this->fromEncoding = $fromEncoding;
        $this->toEncoding = $toEncoding;
    }

    /**
     * @param string $string The content to be tested
     */
    public function diagnose ($string)
    {
        $this->testInputStringAgainstEncoding($string, $this->fromEncoding);

        $testDouble = $this->generateReEncodedTestDouble(
            $string,
            $this->fromEncoding,
            $this->toEncoding
        );

        $diffArray = $this->strictStringCompare($string, $testDouble);
        return $diffArray;
    }

    /**
     * Takes the array of substring elements produced by EncodingConversionDataLossChecker::diagnose and wraps it
     * @param string $text String to perform subStringWraps on
     * @param array $diffArray Array of substring elements produced by EncodingConversionDataLossChecker::diagnose
     * @param string $preString String to add before each substring element
     * @param string $postString String to add after each substring element
     */
    public function subStringWrap($text, array $diffArray, $preString, $postString)
    {
        $subStrings = array();
        foreach ($diffArray as $diffElement) {
            if (!empty($diffElement['characters'])) {
                // Note: Since the translation array takes the original string as the array key, and array
                // keys are inherently unique, all translations are unique by default.
                $subStrings[$diffElement['characters']] = $preString.$diffElement['characters'].$postString;
            }
        }
        $output = strtr($text, $subStrings);
        return $output;
    }

    private function testParameters ($fromEncoding, $toEncoding)
    {
        if (!$this->isSupportedEncoding($fromEncoding)) {
            throw new EncodingConversionDataLossCheckerException('Provided fromEncoding is not supported.');
        }
        if (!$this->isSupportedEncoding($toEncoding)) {
            throw new EncodingConversionDataLossCheckerException('Provided toEncoding is not supported.');
        }
    }

    private function testInputStringAgainstEncoding ($string, $fromEncoding)
    {
        if (!mb_check_encoding($string, $fromEncoding)) {
            throw new EncodingConversionDataLossCheckerException('Provided fromEncoding is not correct.');
        }
    }

    private function generateReEncodedTestDouble ($string, $fromEncoding, $toEncoding)
    {
        /**
         * Convert it twice. First from-to, then to-from.
         * The conversion tries to preserve character by shifting, which produces the same
         * readable character, however it is not 1:1 comparable to the original because of this.
         * By re-encoding back to the original encoding, we can differentiate 1:1 and focus
         * on the characters which did not survive the encoding steps.
         */
        $testDouble = mb_convert_encoding($string, $toEncoding, $fromEncoding);
        $testDouble = mb_convert_encoding($testDouble, $fromEncoding, $toEncoding);
        return $testDouble;
    }

    private function strictStringCompare ($string1, $string2)
    {
        $diffArray = array();

        // This only works reliable when working within UTF-8 internal encoding. This forces it.
        $currentInternalEncoding = mb_internal_encoding();
        mb_internal_encoding('UTF-8');

        $chunk = -1;
        $lastCharPos = 0;
        for ($charPos = 0; $charPos < mb_strlen($string1); $charPos++) {
            $string1char = mb_substr($string1,$charPos,1);
            $string2char = mb_substr($string2,$charPos,1);
            if ($string1char !== $string2char) {
                if ($charPos > $lastCharPos+1) {
                    $chunk++;
                }
                if (!isset($diffArray[$chunk])) {
                    $diffArray[$chunk] = array(
                        'startPos' => $charPos,
                        'characters' =>  $diffArray[$chunk]['characters'] = $string1char,
                        'length' => 1
                    );
                } else {
                    $diffArray[$chunk]['characters'] .= $string1char;
                    $diffArray[$chunk]['length']++;
                }
                $lastCharPos = $charPos;
            }
        }

        // ..and return to the original encoding, to respect the environment we're working in.
        mb_internal_encoding($currentInternalEncoding);

        return $diffArray;
    }

    private function isSupportedEncoding ($encoding)
    {
        if (in_array($encoding, mb_list_encodings())) {
            return true;
        }
        return false;
    }
}