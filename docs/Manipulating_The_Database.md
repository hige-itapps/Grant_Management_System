# Manipulating The Database
Despite this project containing many important web files, **the core of the system is the database itself**. If the information in the database is wrong, then everything else will be wrong too; and very often, the database is the root cause of other issues. Therefore, it is extremely important that you should be able to modify the database directly without relying solely on the front-end interfaces, just in case.

This documentation specifically describes the MySQL structure of the database, and how to create, remove, update, and delete records (known as *CRUD* operations) for every table with specific examples. This documentation assumes that you will be using the MySQL Workbench application to connect to the database, and that you have all CRUD permissions enabled for your account.

## Getting Started
In order to do anything at all, you first need to open up your connection to the database. Open the MySQL Workbench application, then look to your “MySQL Connections” tab. If you have not done so already, create a connection to the database using the hostname, port number, and other configuration options specified by the administrator. Double click on the connection to open it.

Now that you have a connection into the database, direct your attention to the leftmost panel of the screen. You should see a ‘Schemas’ tab, with a schema titled ‘hige’. This is where you will do all of your work. Expand this schema, and you will see more options. Expand the ‘Tables’ option- this is where all the tables in the database are stored. The easiest way to access any table is to right click on it and select the “Select Rows” option (which may have an optional ‘Limit’ attached).

Doing this will result in a list of all the records within the table you chose (up to the specified limit, if any).

## Table Definitions
At the time of writing, there are 10 tables in the database. These are listed below, each one with a bullet-point list of every field it has. Some tables have fields which are *foreign keys* on other table’s fields, meaning they depend on them; if you wish to remove the record the foreign key refers to, you must either change the foreign key or delete the associated record first. Fields followed by 'PK' are the primary keys for their tables.

**1. administrators** – Holds information about the system’s administrators, mainly Jon and/or Dr. Metro-Roland. These users not only have the ability to modify the permissions of all other staff members, they also inherit all of their permissions within the program. For example, if the application approver can approve or deny applications, then so can the admin. NOTE- for this reason, to eliminate redundancy and possible future errors, please do not assign a user to both this category and another.	
 - **BroncoNetID [VARCHAR(20)] PK** – The user’s BroncoNetID.
 - **Name [VARCHAR(100)]** – The user’s name.
 
**2.	applicants** – Simply used to hold the BroncoNetIDs of all applicants who have applied at any time.
 -	**BroncoNetID [VARCHAR(20)] PK** – The user’s BroncoNetID.

**3.	application_approval** – Holds the HIGE staff members who have permission to approve/deny applications. 
 -	**BroncoNetID [VARCHAR(20)] PK** – The user’s BroncoNetID.
 -	**Name [VARCHAR(100)]** – The user’s name.

**4.	applications** – The most complex of all the tables. Holds most of each application’s information. Each application is assigned a unique ID to separate it from the rest. The only application info it doesn’t store is budget information, which is stored in the next table.
 -	**ID [INT(11)] PK** – This application’s unique ID.
 -	**Applicant [VARCHAR(20)]** – *Foreign key* on the applicant’s BroncoNetID from the applicants table.
 -	**Date [DATE]** – The submission date of the application.
 -	**NextCycle [TINYINT(4)]** – Boolean that is true if the applicant chose to apply for the NEXT cycle from the submission date, or false if the applicant chose to apply for the CURRENT cycle from the submission date.
 -	**Name [VARCHAR(100)]** – The user’s name.
 -	**Email [VARCHAR(254)]** – The user’s email address.
 -	**Department [VARCHAR(80)]** – The user’s department.
 -	**DepartmentChairEmail [VARCHAR(254)]** – The department chair’s email address.
 -	**TravelStart [DATE]** – The expected date when the user’s travelling begins.
 -	**TravelEnd [DATE]** – The expected date when the user’s travelling ends. It is expected for this to be >= TravelStart.
 -	**EventStart [DATE]** – The expected date when the user’s activities begin. It is expected for this to be >= TravelStart and <= TravelEnd.
 -	**EventEnd [DATE]** – The expected date when the user’s activities end. It is expected for this to be >= TravelStart, <= TravelEnd, and >= EventStart.
 -	**Title [VARCHAR(300)]** – The application’s title.
 -	**Destination [VARCHAR(100)]** – The location that the user is travelling to.
 -	**AmountRequested [DECIMAL(10,2)]** – The requested amount of money to receive from the fund to cover the trip.
 -	**IsResearch [TINYINT(1)]** – Boolean that is true when the user’s purpose of travel is for research, or false otherwise.
 -	**IsConference [TINYINT(1)]** – Boolean that is true when the user’s purpose of travel is for a conference, or false otherwise.
 -	**IsCreativeActivity [TINYINT(1)]** – Boolean that is true when the user’s purpose of travel is for a creative activity, or false otherwise.
 -	**IsOtherEventText [VARCHAR(400)]** – Used when the user’s purpose of travel is for another activity not listed above. If so, it will have text in it; otherwise it will be blank. Essentially used as a boolean alongside the rest.
 -	**OtherFunding [VARCHAR(400)]** – Used when the user has another source of funding besides the IEFDF grant.
 -	**ProposalSummary [VARCHAR(1400)]** – The user’s proposal summary.
 -	**FulfillsGoal1 [TINYINT(1)]** – Boolean that is true when the user’s trip fulfills the first goal of the grant, or false otherwise.
 -	**FulfillsGoal2 [TINYINT(1)]** – Boolean that is true when the user’s trip fulfills the second goal of the grant, or false otherwise.
 -	**FulfillsGoal3 [TINYINT(1)]** – Boolean that is true when the user’s trip fulfills the third goal of the grant, or false otherwise.
 -	**FulfillsGoal4 [TINYINT(1)]** – Boolean that is true when the user’s trip fulfills the fourth goal of the grant, or false otherwise.
 -	**DepartmentChairSignature [VARCHAR(100)]** – The approval of the department chair, specified by their full name.
 -	**AmountAwarded [DECIMAL(10,2)]** – The amount of money awarded to this applicant, if any.
 -	**Status [VARCHAR(20)]** – The status of the application; "Approved", "Denied", "Hold", or "Pending".

**5.	applications_budgets** – Used in tandem with applications; specifically, to hold the various budgets items for each application. There should always be at least one of these (with no maximum amount) for each application.
 -	**BudgetItemID [INT(11)] PK** – This budget item’s unique ID.
 -	**ApplicationID [INT(11)]** – *Foreign key* on the associated application’s ID from the applications table.
 -	**Name [VARCHAR(25)]** – Name of this budget’s expense (“Air Travel”, “Ground Travel”, “Hotel”, “Registration Fee”, “Per Diem”, or “Other”).
 -	**Cost [DECIMAL(10,2)]** – The specific cost of this budget item.
 -	**Details [VARCHAR(100)]** – Additional details about this budget item, which are required.

**6.	committee** – Used to hold the IEFDF committee members, who are only allowed to view applications. 
 -	**BroncoNetID [VARCHAR(20)] PK** – The user’s BroncoNetID.
 -	**Name [VARCHAR(100)]** – The user’s name.
 
**7. emails** – Saves emails associated with a specific application/report
 - **ID [INT(11)] PK** – The unique ID for this email
 - **ApplicationID** – *Foreign key* on the associated application’s ID from the applications table.
 - **Subject [VARCHAR(100)]** – Subject of the email.
 - **Message [TEXT]** – The email's full message.
 - **Time [TIMESTAMP]** – Time of saving the email.

**8.	final_report_approval** – Holds the HIGE staff members who have permission to approve/deny follow-up reports.
 -	**BroncoNetID [VARCHAR(20)] PK** – The user’s BroncoNetID.
 -	**Name [VARCHAR(100)]** – The user’s name.

**9.	final_reports** – Holds information about the follow-up reports that applicants can submit for their approved applications.
 -	**ApplicationID [INT(11)] PK** – *Foreign key* on the associated application’s ID from the applications table.
 -	**Date [DATE]** – The submission date of the report.
 -	**TravelStart [DATE]** – The actual date when the user’s travelling began.
 -	**TravelEnd [DATE]** – The actual date when the user’s travelling ended. It is expected for this to be >= TravelStart.
 -	**EventStart [DATE]** – The actual date when the user’s activities began. It is expected for this to be >= TravelStart and <= TravelEnd.
 -	**EventEnd [DATE]** – The actual date when the user’s activities ended. It is expected for this to be >= TravelStart, <= TravelEnd, and >= EventStart.
 -	**TotalAwardSpent [DECIMAL(10,2)]** – The amount of money the user spent on their trip, of the amount awarded to them.
 -	**ProjectSummary [VARCHAR(3200)]** – The user’s full project summary.
 -	**Status [VARCHAR(20)]** – The status of the report; "Approved", "Denied", "Hold", or "Pending".

**10. notes** – Saves notes associated with a specific application/report which are only visible to HIGE staff
 -	**ApplicationID [INT(11)] PK** – *Foreign key* on the associated application’s ID from the applications table.
 -	**Note [TEXT]** – The full note's text.
 
## Creating Records
The MySQL Workbench app’s GUI makes it easy to perform any basic CRUD actions. To create a record, start by loading up the table of your choice. Scroll to the bottom of the table where you will find a blank line. Simply type in your data to be added into each corresponding box. Finally, click off the input box, find the ‘Apply’ button down and to the right, and confirm your additions. 

If your query fails, it is likely that the data is of an incorrect format, or that there is a foreign key that doesn’t exist yet. Check the table definitions above for additional information.

If you find that you would like to run a more complex insertion query, you can always type one in the query box near the top of the page (in the picture above, it is shown under a tab called ‘administrators’). Once you’ve completed your query, simply click the lightning bolt button to execute it. By default, this query will be a SELECT * query which you can use to refresh the table whenever a change is made.

The standard syntax for an insert statement is:
```INSERT INTO table(field1, field2, …) VALUES('value1', 'value2', …)```

So for example, to insert a new administrator, you can run:
```INSERT INTO administrators(BroncoNetID, Name) VALUES('bpx4132', 'Barry Johnson')```
 
