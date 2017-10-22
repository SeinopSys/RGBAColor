<h1>RGBAColor Class <a href="https://travis-ci.org/ponydevs/MLPVC-RR"><img src="https://travis-ci.org/SeinopSys/RGBAColor.svg?branch=master" alt="Build Status"></a></h1>

A flexible class for parsing/storing color values

## Installation

```
composer require seinopsys/rgbacolor:1.*
```

## Usage

```php
use SeinopSys\RGBAColor;

$red = RGBAColor::parse('#f00');
$green = new RGBAColor(0, 255, 0);
$transparentBlue = new RGBAColor(0, 0, 255, .5);
$purple = (string)$green->invert();
$black = $red->setRed(0)->toHex();
```
