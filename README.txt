# DoTrack TASK MANAGEMENT SYSTEM

## System Installation Guide

### 1. Database Setup
- Open **phpMyAdmin**.
- Create a new database named: `dotrackdb`.
- Import the SQL file named `dotrackdb.sql` into this database.

### 2. Project Folder Setup
- Copy the entire `dotrack` folder into your web server directory.
- For XAMPP, the path should look like this:  
  `C:\xampp\htdocs\dotrack`

### 3. Access the System
- Open your web browser and enter the following URLs:
  - **User dashboard:** [http://localhost/dotrack/index.php](http://localhost/dotrack/index.php)
  - **Admin login:** [http://localhost/dotrack/auth/loginandsignup.php](http://localhost/dotrack/auth/loginandsignup.php)

- **Administrator Login Credentials:**
  - Email: `admin@gmail.com`
  - Password: `admin123`

### 4. Troubleshooting

#### If the page does not load:
- Make sure **Apache** and **MySQL** services are running in XAMPP.
- Check that the `dotrack` folder is correctly placed in the `htdocs` directory.
- Ensure the database `dotrackdb` is correctly created and imported.

#### For database connection errors:
- Check the `db.php` file to ensure the database credentials match your setup.

---

**For further support, contact:**