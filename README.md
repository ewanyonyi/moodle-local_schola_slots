# Moodle Academic & Exam Timetabler (`local_academic_timetabler`)

**`local_academic_timetabler`** is an enterprise-grade, self-contained Moodle local plugin designed to generate conflict-free schedules for both **weekly course sessions** (lectures, labs) and **examination windows** (midterms, finals).

The entire system executes **100% on-premise** within Moodle's PHP environment with zero external API calls or third-party dependencies.

---

## Uninstalling Legacy Moodle Setup (`/var/www/html/moodle`)

Before migrating your development workspace to `~/dev/moodle`, purge the previous installation from `/var/www/html` and drop the existing MariaDB database.

1. **Remove Existing Web Directory & Moodledata:**
   ```bash
   sudo rm -rf /var/www/html/moodle
   sudo rm -rf /var/www/moodledata
   ```

2. **Drop Existing Moodle Database & User:**
   ```bash
   sudo mysql -u root -e "DROP DATABASE IF EXISTS moodle; DROP USER IF EXISTS 'moodleuser'@'localhost';"
   ```

3. **Clean Up Apache Default Configurations:**
   ```bash
   sudo a2dissite 000-default.conf
   sudo systemctl reload apache2
   ```

---

## Local Development Setup (`~/dev/moodle` — Ubuntu 24.04 LTS)

This setup runs Moodle **v5.2.2** and your plugin directly out of your user development directory (`/home/ewanyonyi/dev`), eliminating all `sudo` permission conflicts during development.

### 1. Install & Configure LAMP Dependencies

Ensure Apache, MariaDB, and PHP dependencies are installed:

```bash
sudo apt update && sudo apt install -y \
    apache2 mariadb-server php php-cli php-mysql \
    php-xml php-gd php-curl php-zip php-mbstring \
    php-intl php-soap git unzip
```

Configure PHP execution limits in `/etc/php/8.3/apache2/php.ini` and `/etc/php/8.3/cli/php.ini`:

```ini
max_execution_time = 300
memory_limit = 512M
post_max_size = 100M
upload_max_filesize = 100M
max_input_vars = 5000
```

Restart Apache:
```bash
sudo systemctl restart apache2
```

---

### 2. Create MariaDB Database & User

Log into MariaDB and create a clean database for Moodle 5.2.2:

```sql
CREATE DATABASE moodle DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'moodleuser'@'localhost' IDENTIFIED BY 'Password123!';
GRANT ALL PRIVILEGES ON moodle.* TO 'moodleuser'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

### 3. Install Moodle 5.2.2 Core & Plugin Directory in `~/dev`

1. **Clone Moodle Production Release (v5.2.2):**
   ```bash
   mkdir -p ~/dev
   cd ~/dev
   git clone -b v5.2.2 git://git.moodle.org/moodle.git moodle
   mkdir -p ~/dev/moodledata
   ```

2. **Create Plugin Folder Directly in the Dev Tree:**
   ```bash
   mkdir -p ~/dev/moodle/local/academic_timetabler
   ```

---

### 4. Configure Apache Virtual Host for `~/dev/moodle`

1. **Create Virtual Host File:**
   ```bash
   sudo nano /etc/apache2/sites-available/moodle-dev.conf
   ```

2. **Insert Virtual Host Block:**
   ```apache
   <VirtualHost *:80>
       ServerName localhost
       DocumentRoot /home/ewanyonyi/dev/moodle

       <Directory /home/ewanyonyi/dev/moodle>
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>

       ErrorLog ${APACHE_LOG_DIR}/moodle_error.log
       CustomLog ${APACHE_LOG_DIR}/moodle_access.log combined
   </VirtualHost>
   ```

3. **Set Permissions & Enable Virtual Host:**
   ```bash
   # Grant directory traversal to Apache (www-data)
   chmod 755 /home/ewanyonyi
   chmod 755 /home/ewanyonyi/dev
   chmod -R 777 ~/dev/moodledata

   # Enable Virtual Host
   sudo a2ensite moodle-dev.conf
   sudo systemctl restart apache2
   ```

---

### 5. CLI Installation & Testing Workflow (No `sudo` Required)

Once web setup is complete at `http://localhost/`, manage database upgrades, CLI tasks, and coding directly as user `ewanyonyi`:

* **Run Moodle Upgrade / Schema Install:**
  ```bash
  cd ~/dev/moodle
  php admin/cli/upgrade.php
  ```

* **Execute Solver Background Task:**
  ```bash
  cd ~/dev/moodle
  php admin/cli/adhoc_task.php --execute
  ```

  # Moodle CodeSniffer (`phpcs`) Global Setup Guide

Moodle core's `composer install` does not include `phpcs` in its local `vendor/bin` directory. Follow these steps to install Moodle's official CodeSniffer tools globally on your Ubuntu machine.

---

### Step 1: Install Moodle CodeSniffer Globally

Run these commands in your terminal to download `phpcs` and the official Moodle CodeSniffer rules (`moodle-cs`):

```bash
composer global config minimum-stability dev
composer global require moodlehq/moodle-cs
```

### Step 2: Add Global Composer Binaries to Environment PATH
Ensure your terminal can execute commands installed by global Composer packages:

Open your shell configuration file:
```bash
nano ~/.bashrc
```
Add the following line at the end of the file:
```bash
export PATH="$HOME/.composer/vendor/bin:$PATH"
```

Save the file and reload your shell configuration:
```bash
source ~/.bashrc
```
Step 3: Verify & Run CodeSniffer
Confirm that phpcs is accessible globally:

```bash
phpcs --version
```

Run the Moodle standard code check against your local plugin workspace:

# Standard check
```bash
phpcs --standard=moodle ~/dev/moodle/local/academic_timetabler
```

# Extended check (optional)

```bash
phpcs --standard=moodle-extra ~/dev/moodle/local/academic_timetabler
```
