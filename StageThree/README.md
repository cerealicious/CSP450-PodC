# Stage Three: Enterprise Database Integration & Application Tier

## 🎯 Project Overview
This repository contains the deployment phase for Stage 3 of our Enterprise Infrastructure Lab. Having established an OSPF-routed network architecture with secure VLAN segmentation in Stage Two, Stage Three scales an online relational database management system (**MariaDB**) coupled to a dynamic web application frontend (**Apache/PHP**).

## 🗺️ System Parameters (Profile Identity: catalan)
* **Your Student UID / VLAN ID:** `231`
* **Linux Client HostName:** `catalan-Client` (Sitting behind VMnet5 LAN link)
* **Linux Server HostName:** `catalan-Server` (Statically mapped via Switch binding)
* **Server Production SVI IP Address:** `172.16.57.254`
* **Internal Private Subnet Domain:** `172.16.57.192/26`
* **Default Network Gateway Router:** `172.16.57.193` (Aruba 6300 Core Switch Interface)
* **Database Staging Directory:** `/tmp/` (Optimized to completely bypass Linux AppArmor file sandboxing blocks)
* **Web Server Host Root Directory:** `/var/www/html/`

---

## 🛡️ Enterprise Security & Multi-Tenant Access Model
* **Administrative Operations Control:** Database structural changes are explicitly locked down to a hard-privilege admin account bound to your unique identifier string.
* **Classroom Cross-Tenant Policy:** To enable global inter-branch connectivity checks without exposing root root administrative passwords, all pod networks deploy a universal read-only profile account (`csp450ro`) strictly limited to `SELECT` (read-only) database properties. This keeps our core inventory data shielded against intentional or accidental deletions or injection drop vectors.

---

## 📁 Repository Directory Structure Mapping
* `/var/www/html/index.php` -> Custom PHP Instrument Lookup Utility web engine frontend script.
* `/tmp/populatedTable.csv` -> Sanitized corporate flat-file spreadsheet dataset staging anchor.

---
#  Phase 1: DB Infrastructure & Environment Provisioning
1. Login to your Server VM, open terminal and run: `sudo apt update`.
2. Install Software Binaries by running the command: `sudo apt install apache2 php php-mysql mariadb-server mariadb-client -y`
3. Enable Remote Network Access:
MariaDB locks down its ears so it only listens to itself (localhost). We need to explicitly tell it to listen to your production IP address (`172.16.57.254`) so that my classmates can access my store. Edit the configuration file by running:
```bash
sudo nano /etc/mysql/mariadb.conf.d/50-server.cnf
```
4. Look for `bind-address = 127.0.0.1` and change it to `bind-address = 0.0.0.0` then save the configuration file. *(This tells MariaDB to listen on all active network interfaces on your server, including your production IP).*

5. Restart mariadb and make sure it is enabled
```bash
sudo systemctl restart mariadb
sudo systemctl enable mariadb
```

6. In terminal, run `sudo ss -tulpn | grep 3306`. If you see a line showing `0.0.0.0:3306` or `*:3306`. This confirms your database server is awake and listening for network connections.

- Install core `mariadb-server` engines on the Ubuntu Server VM.
- Configure listener bindings to the local production address (`172.16.57.254`).
---

#  Phase 2: Phase 2: Multi-Tenant Database Access & Security Policies
### Database Initialization
1. Login to your Server VM, open terminal and run: `sudo mariadb -u root`.
*Terminal prompt will change from your regular command line to a database prompt that looks like `MariaDB [(none)]>`. This means you are now successfully inside the database engine.*

2. Create your privileged Admin Account. Make sure to create your own strong password of choice:
```bash
CREATE USER 'catalan_admin'@'%' IDENTIFIED BY 'P@ssw0rd';
```
3. Give this user full administrative power over everything by typing this and hitting Enter:
```bash
GRANT ALL PRIVILEGES ON *.* TO 'catalan_admin'@'%' WITH GRANT OPTION;
```
4. Create the share account that our classmates will use to view your database:
```bash
CREATE USER 'csp450ro'@'%' IDENTIFIED BY 'csp450ro';
```
Limit this user so they can **only** read data using the `SELECT` command, preventing them from deleting things:

```bash
GRANT SELECT ON *.* TO 'csp450ro'@'%';
```
5. Save Changes and Exit
```bash
FLUSH PRIVILEGES;
EXIT;
```
---

#  Phase 3: Relational Schema Deployment & Data Population.
### Task 3.1: Initialize the Production Database & Tables**
1. Log in to local MariaDB shell prompt using the Admin root credentials:
```bash
sudo mariadb -u root
```
2. Now, create the schema and name it `inventory` exactly as specified:

```SQL
CREATE DATABASE inventory;
```
3. Switch focus over into your new database area so your next commands know where to place the data tables:

```SQL
USE inventory;
```

4. Now, copy and paste this entire code block into your terminal and press Enter to create the empty inventory template:

```SQL
CREATE TABLE instruments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  instrument_type VARCHAR(100),
  instcondition VARCHAR(50),
  price DECIMAL(10,2)
);
```
```SQL
EXIT;
```

### Task 3.2: Prepare and Move the CSV Data File
*Before continuing, make sure to edit your `populatedTable.csv` file using an application like Notepad or Excel to append 10 unique, custom corporate branch instruments at the bottom of the document.*

Example:
```bash
,Custom Electric Guitar,New,1500
,Premium Maple Cello,New,1850
,Vintage Brass Trombone,Used,950
,Handcrafted Mahogany Ukulele,New,349
,Professional Carbon Fiber Violin,New,2800
,Studio Electric Bass,Refurbished,720
,Concert Grand Marimba,New,4500
,Classic Bamboo Flute,New,125
,Pro Double-Bass Pedal Kit,Used,399
,Custom 88-Key Stage Keyboard,New,1650
```
*Look at that very first row: `,Custom Electric Guitar,New,1500.00`.
Later in Phase 5 during your practical verification check, the lab sheet states you must perform a search specifically for a **New Guitar at a price of $1500**. This line gives us the perfect data match to successfully pass that visual inspection.*


1. Push the File From Your Host Workstation

Open command prompt and navigate to the location of your CSV file. We will use **SCP command** to copy the CSV file and paste it on a `/tmp/` folder inside our Server-VM.
```bash
cd Downloads
scp populatedTable.csv catalan@172.16.57.254:/tmp/
```
*(When prompted, type `yes` to accept the security key, then enter your Server-VM  account password.)*

2. Return to your **Ubuntu Server VM** and change the permissions by running:
```bash
sudo chown mysql:mysql /tmp/populatedTable.csv
sudo chmod 644 /tmp/populatedTable.csv
```

3. Log back in to MariaDB, select Inventory and use LOAD DATA INFILE:
```bash
sudo mariadb -u root --local-infile=1
```

```SQL
USE inventory;
```
```SQL
USE inventory;

LOAD DATA LOCAL INFILE '/tmp/populatedTable.csv' 
INTO TABLE instruments
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 LINES
(instrument_type, instcondition, price);
```

4. Run a query to confirm if everything was imported:
```SQL
SELECT * FROM instruments LIMIT 15;
```

5. Once you see the contents of the CSV, you can now leave the relational prompt.
```SQL
EXIT;
```
---

# Phase 4: Application Integration (Apache & PHP Frontend)
### Task 4.1: Modify the Database Frontend Script
1. Open your local copy of the `mariaDBfrontEnd.php` script on your **Client VM**, find the top configuration block, and edit lines 8–14 to look like this:
```php
<?php
/**
 * CSP450 Stage 3 - Instrument Lookup
 * Student: catalan (UID 231)
 * Server:  catalan-Server (172.16.57.254)
 */
session_start();
$db_name = 'inventory'; // Points to our new database 
$db_user = 'csp450ro';    // Global read-only login account
$db_pass = 'csp450ro';    // Matching password
$db_port = 3306;
$my_uid = 231;          // Your Unique ID
```
2. Save the updated web file exactly as **`index.php`**. Do not keep the name `mariaDBfrontEnd.php`.

### Task 4.2: Move the Files Over to Your Production Server
1. Open a terminal Client VM and securely copy (scp) the modified web app script straight to your server:
```bash
scp index.php catalan@172.16.57.254:/tmp/
```
2. On **Server VM** terminal run the command to pull it out of the temporary folder and place it directly into Apache's primary web directory:

```bash
sudo mv /tmp/index.php /var/www/html/index.php
```
3. Delete the old index.html file
```bash
sudo rm /var/www/html/index.html
```

### Task 4.3: Secure File System Permissions
1. Change the permissions of Apache web service:
```bash
sudo chown www-data:www-data /var/www/html/index.php
sudo chmod 644 /var/www/html/index.php
```
2. Restart Apache
```bash
sudo systemctl restart apache2
```

---

# Phase 5: Enterprise Cross-Tenant Verification & Final Inspection
### Task 5.1: Access Your Local Web Server Front-End
1. In your **Client VM**, open browser and type the Server's IP Address:
```bash
172.16.57.254
```
2. Press Enter and you should see a webpage titled **"Instrument Lookup — Musical Instrument Sales Co."**

### Step 5.2: Test Your Custom Instrument Search
- Verify that your database can fetch the custom entries you appended
    - In the **Database Server** IP box, type your own server IP: `172.16.57.254`.
    - In the **Instrument Type** box, type: `Guitar`.
    - In the **Condition dropdown**, select: `New`.
    - In the **Maximum Price** box, type: `1500`.
    - Click **Search Inventory**.
    
🔍 *Expected Result: Your webpage will display a clean result row showing your custom entry: `Custom Electric Guitar` priced perfectly at `$1500.00`.*

### Task 5.3: Run a Unique Inventory Search
 - Try searching for another one of your unique creations to prove the database filters are dynamically responsive.
    - In the **Instrument Type** box, type: `Cello`
    - Clear out the condition and price limits.
    - Click **Search Inventory**.

🔍 *Expected Result: The webpage will filter out everything else and display your `Premium Maple Cello` priced at `$1850`.*


### Step 5.4: Connect to a Classmate’s Database (Remote Host)
 - Ask one of your classmates for their Server VMs IP Address.
    - Erase your IP from the **Database Server IP** box, and type in your **classmate's Server IP** instead.
    - Clear the other boxes.
    - Click **Search Inventory**.
    
🔍 *Expected Result: Your webpage will intercept the trigger command, route data packets across your physical OSPF switch trunk trunks, log securely into their database using the matching read-only `csp450ro` token variables, and display their custom branch store assets right on your local screen!*

---
### 📋 Phase 6: Final Submission Checklist
- [ ] `index.php`: The edited frontend script containing your custom user details at the top. 
- [ ] `populatedTable.csv`: Your modified flat-file spreadsheet featuring your 10 unique corporate branch instruments appended cleanly to the bottom. 


---
*Last Updated: July 13, 2026*


