# WEB ERP MES
##  Ressource and Manufacturing execution system  Web

Version 1 in progress - See the projects page to see the progress.
https://github.com/billyboy35/WEB_MES/projects/2

I am unprofessional, any improvement discussion would be helpful (Help search for gantt planning creation).

The goal is to have version 1 with a functional workflow up to invoicing.

* Currently, the quotation, orders, confirmation, Delivery notes, Invoice order are finished.

* Next step : Purchase request, Purchase Order,AR, PO receipts, Supplier Invoice

* Parallel work : planning, Stock | Inventory

-----------------
![alt text](https://github.com/billyboy35/WEB_MES/blob/master/public/images/Doc/Menu.PNG)

### Installation
1. git clone git@gitlab.com:billy_boy/ERP.git
2. SQL setting connection
* Use __erp.sql__ to import ERP database  
* Connection to the SQL database in __SQL.class.php__ file
* SQL login to define in __include_recup_config.php__

3. http://localhost/erp/public/

4. Log acces to user pages
* Login : Admin
* Password : password

-----------------
Few pages : quote - component - planning

![alt text](https://github.com/billyboy35/WEB_MES/blob/master/public/images/Doc/Quote/Quote_home.PNG)
-----------------
Item description
![alt text](https://github.com/billyboy35/WEB_MES/blob/master/public/images/Doc/Item/Item_exemple.PNG)
-----------------
Task List
![alt text](https://github.com/billyboy35/WEB_MES/blob/master/public/images/Doc/Planning/Task_list.PNG)
-----------------
Order delivery schedule
![alt text](https://github.com/billyboy35/WEB_MES/blob/master/public/images/Doc/Planning/Calendar.PNG)

-----------------
WorkFlow 
![alt text](https://github.com/billyboy35/WEB_MES/blob/master/public/images/Doc/Workflow.png)
