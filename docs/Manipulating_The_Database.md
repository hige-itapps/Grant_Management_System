# Manipulating The Database
Despite this project containing many important web files, **the core of the system is the database itself**. If the information in the database is wrong, then everything else will be wrong too; and very often, the database is the root cause of other issues. Therefore, it is extremely important that you should be able to modify the database directly without relying solely on the front-end interfaces, just in case.

This documentation specifically describes the MySQL structure of the database, and how to create, remove, update, and delete records (known as *CRUD* operations) for every table with specific examples. This documentation assumes that you will be using the MySQL Workbench application to connect to the database, and that you have all CRUD permissions enabled for your account.

## Getting Started
In order to do anything at all, you first need to open up your connection to the database. Open the MySQL Workbench application, then look to your “MySQL Connections” tab. If you have not done so already, create a connection to the database using the hostname, port number, and other configuration options specified by the administrator. Double click on the connection to open it.

Now that you have a connection into the database, direct your attention to the leftmost panel of the screen. You should see a ‘Schemas’ tab, with a schema titled ‘hige’. This is where you will do all of your work. Expand this schema, and you will see more options. Expand the ‘Tables’ option- this is where all the tables in the database are stored. The easiest way to access any table is to right click on it and select the “Select Rows” option (which may have an optional ‘Limit’ attached).

Doing this will result in a list of all the records within the table you chose (up to the specified limit, if any).

## Table Definitions
At the time of writing, there are 8 tables in the database. These are listed below, each one with a bullet-point list of every field it has. Some tables have fields which are *foreign keys* on other table’s fields, meaning they depend on them; if you wish to remove the record the foreign key refers to, you must either change the foreign key or delete the associated record first.

1. administrators – Holds information about the system’s administrators, mainly Jon and/or Dr. Metro-Roland. These users not only have the ability to modify the permissions of all other staff members, they also inherit all of their permissions within the program. For example, if the application approver can approve or deny applications, then so can the admin. NOTE- for this reason, to eliminate redundancy and possible future errors, please do not assign a user to both this category and another.	
 - BroncoNetID [VARCHAR(20)] – The user’s BroncoNetID.
 - Name [VARCHAR(100)] – The user’s name.
