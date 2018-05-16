## Here you will find all the unit tests for this project. ##
Each tested php file gets its own directory, with separate Test.php files within to test various functions. You will have to modify the testconfig file to fit your needs. You will also need to have an instance of MySQL running to match the testconfig file, including username, password, and the test database name. The test database should follow the schema of the real one; use the .sql file found in the root of this project to import it. **It is recommended not to run these tests on the production server while currently in-production, use a separate machine if possible; The tests may drop important data from the database if using the same schema!**

Steps (I took) to get PHP unit testing working on Windows:
1. Install PHP v7.2.5 under C: PHP7 (follow steps from: http://kizu514.com/blog/install-php7-and-composer-on-windows-10/)

2. Install Composer (follow steps from: https://getcomposer.org/doc/00-intro.md)

3. Install PHPUnit using composer (follow steps from: https://phpunit.de/getting-started/phpunit-7.html)

4. Install DbUnit by typing: composer require --dev phpunit/dbunit ^4 (same thing as installing PHPUnit)

5. To run a single test from the command line, type: vendor\bin\phpunit tests\verification\areCyclesFarEnoughApartTest.php

6. To run ALL tests (which should probably be done before committing any code), type: vendor\bin\phpunit tests
