Here Redis-server is used to store all the children of logged in master account. 
All the child records of logged in user are fetched from the MySQL database only when the user is logged  in. 
The complete set of associated records is saved in Redis key structure. Eventually, whenever the child records are required on any page, 
they are then fetched from Redis key database instead of the MySQL server. 
The purpose is to save time and improve the performance of page load.

Technologies used:

redis server 3.2
mysql 5
cakephp 1.3

