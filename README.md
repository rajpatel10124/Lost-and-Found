# Lost and Found Web Application

A comprehensive web application for managing lost and found items, built with PHP, MySQL, HTML, CSS, and JavaScript.

## Features

### User Features
- Report lost items with details (name, description, date, location, image, contact info)
- Report found items with similar details
- View all lost and found items
- View detailed information about specific items
- Automatic matching system between lost and found items
- Contact item reporters via email or phone

### Admin Features
- Secure admin login panel
- Dashboard with statistics and recent activity
- Approve, reject, or delete lost and found reports
- Manage all items (edit, update status, delete)
- Add new items directly from admin panel

## Installation

1. Clone the repository to your web server directory:
\`\`\`
git clone https://github.com/yourusername/lost-and-found.git
\`\`\`

2. Create a MySQL database named `lost_and_found`

3. Import the database schema from `database.sql`:
\`\`\`
mysql -u username -p lost_and_found < database.sql
\`\`\`

4. Configure the database connection in `includes/config.php`:
\`\`\`php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'lost_and_found');
\`\`\`

5. Make sure the `uploads` directory has write permissions:
\`\`\`
chmod 777 uploads
\`\`\`

6. Access the application through your web browser:
\`\`\`
http://localhost/lost-and-found
\`\`\`

## Admin Access

Default admin credentials:
- Username: admin
- Password: admin123

Access the admin panel at:
\`\`\`
http://localhost/lost-and-found/admin
\`\`\`

## Project Structure

\`\`\`
lost

## Database Setup Instructions

To set up the database for your Lost and Found application:

1. Create a MySQL database named `lost_and_found`
2. Import the `database.sql` file to create all necessary tables and the default admin user
3. Update the database connection settings in `includes/config.php` with your MySQL credentials

## How the Application Works

### User Features

1. **Reporting Items**: Users can report lost or found items through dedicated forms, including details like item name, description, date, location, and an optional image.

2. **Viewing Items**: The application displays all approved lost and found items, with search functionality and pagination.

3. **Matching System**: The system automatically matches lost items with found items based on keywords in the name and description, calculating a match score.

4. **Contact System**: Users can contact the person who reported an item via email or phone.

### Admin Features

1. **Dashboard**: Provides statistics on lost/found items and recent activity.

2. **Item Management**: Admins can approve, reject, or delete item reports, as well as edit item details.

3. **Bulk Actions**: Perform actions on multiple items at once.

### Security Features

- Password hashing for admin authentication
- Form validation to prevent invalid data
- SQL injection protection through prepared statements
- XSS protection with proper output escaping

## Deployment Instructions

1. Upload all files to your web hosting service (like 000webhost or InfinityFree)
2. Create a MySQL database on your hosting service
3. Import the database.sql file
4. Update the database connection settings in includes/config.php
5. Ensure the uploads directory has proper write permissions

The application is now ready to use!
