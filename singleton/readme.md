This is a small coding challenge for NoughtsCrosses game where the game results are entered via STDIN and result is calculated and saved in the database with STDIN input. The game aggregate result is also shown if "calculate" argument is passed in the console. To start the input "calculate" is passed as argv[1].

$ php tictactoe.php calculate

$ php tictactoe.php results

This code snippet is a good example of Singleton pattern class instance. As per code, if the DBConnect :: getInstance method is called for more than one time, it will always return the reference that is created at first attempt. The code does not create a new reference by getInstance method. Also, the Object is class cannot be created because the constructor is declared as private.


OOP PHP 5 (singleton pattern db instance),
PHP STDIN console based input and output argv,
MySQL 5
