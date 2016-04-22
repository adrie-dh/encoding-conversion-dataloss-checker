Encodingconversion dataloss checker

[![Build Status](https://secure.travis-ci.org/adrie-dh/encoding-conversion-dataloss-checker.png)](http://travis-ci.org/adrie-dh/encoding-conversion-dataloss-checker)

Provides the tools to check for dataloss during encoding.
For instance UTF-8 to ISO-8859-1 will ensure that some characters might not make the conversion.

Also comes with a simple substring wrapper which allows you to provide exact feedback in your frontend regarding the problematic characters. 

Examples:

```php
$testString = 'Ma võin klaasi süüa, 是紅, see ei tee mulle midagi. 是紅. And then some.';
$tester = new EncodingConversionDataLossChecker('UTF-8', 'ISO-8859-1');
$diffArray = $tester->diagnose($testString);

/* $diffArray:
array(2) {
  [0]=>
  array(3) {
    ["startPos"]=>
    int(21)
    ["characters"]=>
    string(6) "是紅"
    ["length"]=>
    int(2)
  }
  [1]=>
  array(3) {
    ["startPos"]=>
    int(50)
    ["characters"]=>
    string(6) "是紅"
    ["length"]=>
    int(2)
  }
}
*/

$testString = 'In most of europe the standard currency is the euro (€), it has € as a symbol.';
$tester = new EncodingConversionDataLossChecker('UTF-8', 'ISO-8859-1');
$diffArray = $tester->diagnose($testString);

/* $diffArray:
array(2) {
  [0]=>
  array(3) {
    ["startPos"]=>
    int(53)
    ["characters"]=>
    string(3) "€"
    ["length"]=>
    int(1)
  }
  [1]=>
  array(3) {
    ["startPos"]=>
    int(64)
    ["characters"]=>
    string(3) "€"
    ["length"]=>
    int(1)
  }
}
*/

$testString = 'Ma võin klaasi süüa, 是紅, see ei tee mulle midagi. 是紅. And then some.';
$tester = new EncodingConversionDataLossChecker('UTF-8', 'ISO-8859-1');
$diffArray = $tester->diagnose($testString);
$wrappedHtml = $tester->subStringWrap($testString, $diffArray, '<p>', '</p>');

/* $wrappedHtml:
string(93) "Ma võin klaasi süüa, <p>是紅</p>, see ei tee mulle midagi. <p>是紅</p>. And then some."
*/

```
