***
# 🎓 Stage Three: Enterprise Database & Web Application Integration

## 🎯 Project Overview
This repository contains the deployment phase for Stage 3 of our Enterprise Infrastructure Lab. Having established an OSPF-routed network architecture with secure VLAN segmentation in Stage Two, Stage Three scales an online relational database management system (**MariaDB**) coupled to a dynamic web application frontend (**Apache/PHP**).


### 🗺️ Network Identity Profile
* **My Assigned UID / VLAN ID:** `231`
* **Linux Client HostName:** `catalan-Client` (Sitting behind VMnet5 LAN link)
* **Linux Server HostName:** `catalan-Server` (Statically mapped via Switch binding)
* **Server Production SVI IP Address:** `172.16.57.254`
* **Internal Private Subnet Domain:** `172.16.57.192/26`
* **Default Network Gateway Router:** `172.16.57.193` (Aruba 6300 Core Switch Interface)
---

## 🛡️ Security Concept: Multi-Tenant Access
1.  **My Admin Account (`catalan_admin`):** Only I use this. It has full power to create tables and delete data.
2.  **The Shared Account (`csp450ro`):** This is the account for everyone. It can **only view** data (`SELECT`). It cannot delete or change anything. This way, if you accidentally try to delete my database, the system will stop you!

---

## 📁 Where I Put My Files
*   **Web Page:** `/var/www/html/index.php` (This is what users see in their browser).
*   **Data File:** `/tmp/populatedTable.csv` (A temporary holding area for your data before it goes into the database).

---

# Phase 1: Installing & Configuring the Database Server

### Step 1.1: Update System Packages
First, I made sure my **Server-VM** had the latest software lists.
```bash
sudo apt update
```

### Step 1.2: Install Required Software
I re-run the install command (even though I did it already on Stage One).
*   `apache2`: The web server software.
*   `php`: The scripting language that connects the web page to the database.
*   `mariadb-server`: The database engine.
*   `mariadb-client`: Tools to talk to the database.

Run this command:
```bash
sudo apt install apache2 php php-mysql mariadb-server mariadb-client -y
```

### Step 1.3: Allow Remote Connections
By default, MariaDB only listens to itself ("localhost"). Since I wanted you to access my database over the network, I had to change this.

1.  I opened the configuration file:
    ```bash
    sudo nano /etc/mysql/mariadb.conf.d/50-server.cnf
    ```
2.  I found the line:
    `bind-address = 127.0.0.1`
3.  I changed it to:
    `bind-address = 0.0.0.0`
    *(Note: `0.0.0.0` tells the database to listen on all network interfaces).*
4.  I saved and exited: Press `Ctrl+O`, then `Enter`, then `Ctrl+X`.


### Step 1.4: Restart Services
Apply the changes by restarting the database service and ensuring it starts automatically on boot.
```bash
sudo systemctl restart mariadb
sudo systemctl enable mariadb
```

### Step 1.5: Verify Connectivity
Check if the database is listening for external connections.
```bash
sudo ss -tulpn | grep 3306
```
**✅ Success Check:** You should see output containing `0.0.0.0:3306` or `*:3306`. If you only see `127.0.0.1`, repeat Step 1.3.



### Step 1.6: Install MariaDB on Client-VM
During visual inspection, we will be running MariaDB using the** Client-VM** to verify that we can log into our MariaDB database using the read-only user that we will create later.

So on my Client-VM, I opened terminal and run these commands.
```bash
sudo apt update
```
```bash
sudo apt install mariadb-client -y
```
---

# Phase 2: Creating Database Users & Permissions

### Step 2.1: Enter the Database Shell
Log in as the root administrator.
```bash
sudo mariadb -u root
```
*My prompt changed to `MariaDB [(none)]>`. I am now inside the database engine.*

### Step 2.2: Create Your Admin User
I created a user for myself. **Make sure to choose a strong password!**
```sql
CREATE USER 'catalan_admin'@'%' IDENTIFIED BY 'P@ssw0rd';
GRANT ALL PRIVILEGES ON *.* TO 'catalan_admin'@'%' WITH GRANT OPTION;
```

### Step 2.3: Create the Shared Read-Only User
This is the user we are creating for everyone. It allows you to view my data without risking deletion.
```sql
CREATE USER 'csp450ro'@'%' IDENTIFIED BY 'csp450ro';
GRANT SELECT ON *.* TO 'csp450ro'@'%';
```

### Step 2.4: Save and Exit
```sql
FLUSH PRIVILEGES;
EXIT;
```

---

# Phase 3: Building the Database & Importing Data

### Step 3.1: Create the Database Structure
1.  I logged back into MariaDB as root:
    ```bash
    sudo mariadb -u root
    ```
2.  I created the database named `inventory`:
    ```sql
    CREATE DATABASE inventory;
    USE inventory;
    ```
3.  I created the table structure for instruments:
    ```sql
    CREATE TABLE instruments (
      id INT AUTO_INCREMENT PRIMARY KEY,
      instrument_type VARCHAR(100),
      instcondition VARCHAR(50),
      price DECIMAL(10,2)
    );
    EXIT;
    ```

### Step 3.2: Prepare Your Data File
1.  On my **Host Machine**, I opened `populatedTable.csv` in Excel or Notepad.
2.  **Crucial Step:** I added **10 unique rows** of custom instrument data at the bottom of the file.

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
Later in Phase 5, we will search our inventory specifically for a **New Guitar at a price of $1500**.*

3. Save the file.

### Step 3.3: Transfer the CSV to the Server
From my **Host Machine** terminal (or PowerShell), I used **SCP command** to copy the CSV file inside my Downloads Folder and pasted it on a `/tmp/` folder inside my Server-VM.
*(Replace `catalan` with your username)*
```bash
cd Downloads
scp populatedTable.csv catalan@172.16.57.254:/tmp/
```
*Type `yes` if asked about the fingerprint, then enter your VM password.*

### Step 3.4: Import Data into MariaDB
1.  On my **Server VM**, I fixed file permissions so the database could read it:
    ```bash
    sudo chown mysql:mysql /tmp/populatedTable.csv
    sudo chmod 644 /tmp/populatedTable.csv
    ```
2.  I logged into MariaDB with local file access enabled:
    ```bash
    sudo mariadb -u root --local-infile=1
    ```
3.  I selected the database and loaded the data:
    ```sql
    USE inventory;
    
    LOAD DATA LOCAL INFILE '/tmp/populatedTable.csv' 
    INTO TABLE instruments
    FIELDS TERMINATED BY ','
    ENCLOSED BY '"'
    LINES TERMINATED BY '\n'
    IGNORE 1 LINES
    (id, instrument_type, instcondition, price);
    ```
4.  I verified the import:
    ```sql
    SELECT * FROM instruments LIMIT 15;
    ```
    *I saw my custom instruments listed.*
    ```sql
    EXIT;
    ```
---
# Phase 4: Deploying the Web Application

### Step 4.1: Configure the PHP Frontend
1.  On my **Client VM**, I opened the provided `mariaDBfrontEnd.php` file in a text editor.
2.  I edited lines **8–14** to match my specific details:
    ```php
    <?php
    session_start();
    $db_name = 'inventory';       // Database name
    $db_user = 'csp450ro';        // Read-only user
    $db_pass = 'csp450ro';        // Read-only password
    $db_port = 3306;
    $my_uid = 231;                // Your UID
    ?>
    ```
3.  **Save As:** `index.php`.

### Step 4.2: Upload to Server
From my **Client VM** terminal, I copied the `index.php` file to the `/tmp/` folder on my Server-VM:
```bash
scp index.php catalan@172.16.57.254:/tmp/
```

### Step 4.3: Install on Web Server
On my **Server VM**:
1.  I moved the file to the web directory:
    ```bash
    sudo mv /tmp/index.php /var/www/html/index.php
    ```
2.  I removed the default Apache page:
    ```bash
    sudo rm /var/www/html/index.html
    ```
3.  I set the correct ownership for Apache:
    ```bash
    sudo chown www-data:www-data /var/www/html/index.php
    sudo chmod 644 /var/www/html/index.php
    ```
4.  I restarted Apache to apply changes:
    ```bash
    sudo systemctl restart apache2
    ```

---

# Phase 5: Testing & Verification

### Test 1: Local Web Access
1.  On your**Client VM**, open a web browser (Firefox).
2.  Navigate to: `http://172.16.57.254`
3.  You should see the **"Instrument Lookup"** page.

### Test 2: Search Functionality
1.  **Searching for the Custom Guitar:**
    *   Instrument Type: `Guitar`
    *   Condition: `New`
    *   Max Price: `1500`
    *   Click **Search**.
    *  🔍*Result:* Should show "Custom Electric Guitar" at $1500.
2.  **Search for another custom item:**
    *   Try searching for `Cello` or another item you added.
    *   🔍*Result:* Should display your unique entry.

### Test 3: Cross-Tenant Connectivity (Classmate Check)
1.  Ask a classmate/partner for their **Server-VM's IP Address**.
2.  On your browser, replace the IP with their IP Address.
3.  Click **Search Inventory**.
    *   🔍*Result:* You should see their instruments. This proved that OSPF routing and the `csp450ro` user were working correctly across the network.

---

# Phase 6: Visual Inspection Guide

### 1. From your Client, demonstrate that you can log into your MariaDB database using the read-only user that you created as per the specification.
1. Open a terminal window on your **Linux Client VM (`catalan-Client`)**.
2. Establish a remote database mapping context target pointing directly to your Server VM's static IP address (`172.16.57.254`):
```bash
mariadb -u csp450ro -p -h 172.16.57.254
```
If it prompted you for passsword, enter `csp450ro`.

🔍*Result:* Your system shell header prompt will instantly close out, dropping you into the live database engine shell. Your terminal cursor pointer line will read exactly: `MariaDB [(none)]>`

### 2. From your Client, demonstrate that you can log into another student’s MariaDB database using the read-only user that they created as per the specification.

1. Coordinate with a classmate or neighbor pod to pull their exact **Server VM IP Address**.
2. From your open Client VM terminal, run the routing mapping command:
```bash
mariadb -u csp450ro -p -h CLASSMATE_SERVER_IP
```
If it prompted you for a password, enter `csp450ro`.

🔍*Result:* You will cleanly slide past their node firewall ruleset and drop right onto their prompt line `MariaDB [(none)]>`

### 3. Display your flat file and show the custom additions you made (your unique 10 instruments).

1. Open a terminal on your **Server-VM**.
2. Authenticate locally into your database administrator shell using root privileges:
```Bash
sudo mariadb -u root
```
3. Attach your workspace session strictly to the target branch inventory space:
```bash
USE inventory;
```
4. Run a direct index-targeted row calculation check to locate your added assets:
```bash
SELECT * FROM instruments ORDER BY id DESC LIMIT 10;
```
🔍*Result:* You should see the last 10 unique instruments you added earlier.

5. Leave the interactive relational context prompt loop:
```bash
EXIT;
```
### 4. From your Client, access your database and perform a search for a `New Guitar` at a price of `$1500`.

1. Open your web browser on your **Client VM**.
2. Type your Server-VM's static network IP address directly into the address bar: `172.16.57.254`

3. Once the "**Instrument Lookup**" graphical dashboard appears, input these explicit variables into the target parameter fields:
- In the **Instrument Type** box, type: `Guitar`.
- In the **Condition dropdown**, select: `New`.
- In the **Maximum Price** box, type: `1500`.
- Click **Search Inventory**.

### 5. From your Client, access your database and perform a search that will display a unique instrument from your inventory.

1. While still inside your Client-VMs browser try searching for another one of your unique creations to prove the database filters are dynamically responsive.
- In the **Instrument Type** box, type: *a musical instrument you added yourself*
- Clear out the condition and price limits.
- Click **Search Inventory**.

🔍*Result:* You should see the specific item that item you just searched for.

### 6. From your Client, access another student’s database in the classroom and perform a search that will display a unique instrument from their inventory.

1. Still inside your **Client VM's** browser, erase your Server-VM's IP address from the address bar.
2. Input your partner's or classmate's Server-VM address *(make sure they finished Stage Two and Three)*.
		
		Note: I will input my partner jmalaqui's Server VM IP Address ( 172.16.54.126)
3. Clear all other instrument parameters and click **Search Inventory**.
🔍*Result:* You should see their own inventory, together with the 10 unique music instruments they added.

---

---

## 📋 Submission Requirements
Ensure the following files are ready for upload to GitHub:
- ✅ `index.php` (Your edited frontend script - mariaDBfrontEnd.php)
- ✅`populatedTable.csv` (The CSV with 10 unique custom entries)

*Last Updated: July 21, 2026*
