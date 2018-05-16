## Here you will find all the unit tests for this project. ##
Each tested php file gets its own directory, with separate Test.php files within to test various functions.

Steps (I took) to get PHP unit testing working on Windows:
1. Install PHP v7.2.5 under C: PHP7 (follow steps from: http://kizu514.com/blog/install-php7-and-composer-on-windows-10/)

2. Install Composer (follow steps from: https://getcomposer.org/doc/00-intro.md)

3. Install PHPUnit using composer (follow steps from: https://phpunit.de/getting-started/phpunit-7.html)

4. To run a single test from the command line, type: vendor\bin\phpunit tests\verification\areCyclesFarEnoughApartTest.php

5. To run ALL tests (which should probably be done after finishing editing/adding any code at all), type: vendor\bin\phpunit tests
